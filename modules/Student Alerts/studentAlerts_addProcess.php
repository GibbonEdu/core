<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['comment' => 'HTML']);

$params = [
    'gibbonFormGroupID'   => $_POST['gibbonFormGroupID'] ?? '',
    'gibbonYearGroupID'   => $_POST['gibbonYearGroupID'] ?? '',
    'gibbonCourseClassID' => $_POST['gibbonCourseClassID'] ?? '',
    'source'              => $_POST['source'] ?? '',
];

$URL = $URLSuccess = Url::fromModuleRoute('Student Alerts', 'studentAlerts_add')->withQueryParams($params);

if (!empty($params['source']) && $params['source'] == 'class') {
    $URLSuccess = Url::fromModuleRoute('Student Alerts', 'report_alertsByClass')->withQueryParams($params);
} elseif (!empty($params['source']) && $params['source'] == 'formGroup') {
    $URLSuccess = Url::fromModuleRoute('Student Alerts', 'report_alertsByFormGroup')->withQueryParams($params);
}

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_add.php')) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $partialFail = false;
    $alertGateway = $container->get(AlertGateway::class);
    $alertLevelGateway = $container->get(AlertLevelGateway::class);
    $alertTypeGateway = $container->get(AlertTypeGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID') ?? '',
        'gibbonPersonID'        => $_POST['gibbonPersonID'] ?? '',
        'gibbonCourseClassID'   => $_POST['gibbonCourseClassID'] ?? null,
        'type'                  => $_POST['type'] ?? '',
        'level'                 => $_POST['level'] ?? null,
        'comment'               => $_POST['comment'] ?? '',
        'status'                => $_POST['status'] ?? 'Pending',
        'context'               => 'Manual',
        'dateStart'             => !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null,
        'dateEnd'               => !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null,
        'gibbonPersonIDCreated' => $session->get('gibbonPersonID') ?? '',
    ];

    // Check required values
    $alertType = $alertTypeGateway->selectBy(['name' => $data['type']])->fetch();
    if (empty($alertType) || empty($data['type']) || empty($data['gibbonPersonID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check for existence of student
    $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $data['gibbonPersonID'])->fetch();
    if (empty($student)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Ensure levels are turned off if not in use
    $data['gibbonAlertTypeID'] = $alertType['gibbonAlertTypeID'];
    if ($alertType['useLevels'] == 'N') {
        $data['gibbonAlertLevelID'] = null;
        $data['level'] = null;
    }

    // Set level ID based on level selected
    if (!empty($data['level'])) {
        if ($alertLevel = $alertLevelGateway->selectBy(['name' => $data['level']])->fetch()) {
            $data['gibbonAlertLevelID'] = $alertLevel['gibbonAlertLevelID'];
        }
    }

    // Create the alert
    $gibbonAlertID = $alertGateway->insert($data);
    if (empty($gibbonAlertID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Raise a new notification event
    $notificationData = [
        'user'      => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true),
        'student'   => Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true),
        'formGroup' => $student['formGroup'],
        'type'      => __($data['type']),
    ];

    $notificationDetails = [
        __('Student')    => $notificationData['student'],
        __('Type')       => __($data['type']),
        __('Level')      => __($data['level']) ?? __('N/A'),
        __('Created By') => $notificationData['user'],
        __('Comment')    => $data['comment'],
    ];

    if (!empty($data['gibbonCourseClassID'])) {
        $class = $container->get(CourseGateway::class)->getCourseClassByID($data['gibbonCourseClassID']);
        $notificationDetails = [
            __('Class') => Format::courseClassName($class['courseNameShort'] ?? '', $class['nameShort'] ?? ''),
        ] + $notificationDetails;
    }

    if ($data['status'] == 'Pending') {
        $event = new NotificationEvent('Student Alerts', 'Pending Student Alert');
        $event->setNotificationDetails($notificationDetails);
        $event->setNotificationText(__('{user} has added a pending {type} alert for {student} ({formGroup}), which requires approval', $notificationData));
        $event->setActionLink(Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage_status')->withQueryParams([
            'gibbonAlertID' => $gibbonAlertID,
            'status'        => 'Approved',
        ])->withPath(''));

        // Head of Year
        $yearGroup = $container->get(YearGroupGateway::class)->getByID($student['gibbonYearGroupID']);
        $event->addRecipient($yearGroup['gibbonPersonIDHOY'] ?? '');
    } else {
        $event = new NotificationEvent('Student Alerts', !empty($data['gibbonCourseClassID']) ? 'New Class Alert' : 'New Global Alert');
        $event->setNotificationDetails($notificationDetails);
        $event->setNotificationText(!empty($data['gibbonCourseClassID'])
                ? __('{user} has added a new class-level {type} alert for {student} ({formGroup})', $notificationData)
                : __('{student} ({formGroup}) has a new {type} alert', $notificationData)
            );
        $event->setActionLink(Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage_view')->withQueryParams([
            'gibbonAlertID' => $gibbonAlertID,
        ])->withPath(''));

        // Form Tutors
        $formGroup = $container->get(FormGroupGateway::class)->getByID($student['gibbonFormGroupID']);
        $event->addRecipient($formGroup['gibbonPersonIDTutor'] ?? '');
        $event->addRecipient($formGroup['gibbonPersonIDTutor2'] ?? '');
        $event->addRecipient($formGroup['gibbonPersonIDTutor3'] ?? '');
    }

    $event->addScope('gibbonPersonIDStudent',  $student['gibbonPersonID']);
    $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);

    $event->sendNotifications($pdo, $session);

    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$gibbonAlertID";

    header("Location: {$URLSuccess}");     
}
