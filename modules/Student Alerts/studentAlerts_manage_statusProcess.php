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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonAlertID = $_POST['gibbonAlertID'] ?? '';

$URL = Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage_status')->withQueryParams(['gibbonAlertID' => $gibbonAlertID]);
$URLSuccess = Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage');

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_manage_status.php')) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
}  else {
    // Proceed!
    $data = [
        'status'               => $_POST['status'] ?? '',
        'timestampStatus'      => date('Y-m-d H:i:s'),
        'notesStatus'          => $_POST['notesStatus'] ?? '',
        'gibbonPersonIDStatus' => $session->get('gibbonPersonID') ?? '',
    ];

    // Check for required values
    if (empty($gibbonAlertID) || empty($data['status'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check if alert exists
    $alertGateway = $container->get(AlertGateway::class);
    $alert = $alertGateway->getByID($gibbonAlertID);
    if (empty($alert)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check for access to Approval status
    $canApprove = Access::get('Student Alerts', 'studentAlerts_manage')->allowsAny('Manage Student Alerts_all', 'Manage Student Alerts_headOfYear');
    if ($data['status'] == 'Approved' && !$canApprove) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Check for existence of student
    $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $alert['gibbonPersonID'])->fetch();
    if (empty($student)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check for alert creator
    $user = $container->get(UserGateway::class)->getByID($alert['gibbonPersonIDCreated'], ['preferredName', 'surname']);
    if (empty($user)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $alertGateway->update($gibbonAlertID, $data);
    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Raise a new notification event
    $notificationData = [
        'user'      => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true),
        'creator'   => Format::name('', $user['preferredName'], $user['surname'], 'Staff', false, true),
        'student'   => Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true),
        'actioned'  => strtolower(__($data['status'])),
        'formGroup' => $student['formGroup'],
        'type'      => __($alert['type']),
    ];

    $notificationDetails = [
        __('Student')    => $notificationData['student'],
        __('Type')       => __($alert['type']),
        __('Level')      => __($alert['level']) ?? __('N/A'),
        __('Status')     => __($data['status']),
        __('Created By') => $notificationData['creator'],
        __('Comment')    => $alert['comment'],
        __('Updated By') => $notificationData['user'],
        __('Notes')      => $data['notesStatus'],
    ];

    if (!empty($alert['gibbonCourseClassID'])) {
        $class = $container->get(CourseGateway::class)->getCourseClassByID($alert['gibbonCourseClassID']);
        $notificationDetails = [
            __('Class') => Format::courseClassName($class['courseNameShort'] ?? '', $class['nameShort'] ?? ''),
        ] + $notificationDetails;
    }

    if ($data['status'] == 'Approved') {
        $event = new NotificationEvent('Student Alerts', !empty($alert['gibbonCourseClassID']) ? 'New Class Alert' : 'New Global Alert');
        $event->setNotificationDetails($notificationDetails);
        $event->setNotificationText(__('{student} ({formGroup}) has a new {type} alert', $notificationData));
        $event->setActionLink(Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage_view')->withQueryParams([
            'gibbonAlertID' => $gibbonAlertID,
        ])->withPath(''));
    } else {
        $event = new NotificationEvent('Student Alerts', 'Updated Student Alert');
        $event->setNotificationDetails($notificationDetails);
        $event->setNotificationText(__('{user} has {actioned} the {type} alert for {student} ({formGroup})', $notificationData));
        $event->setActionLink(Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage_view')->withQueryParams([
            'gibbonAlertID' => $gibbonAlertID,
        ])->withPath(''));
    }

    // Form Tutors
    $formGroup = $container->get(FormGroupGateway::class)->getByID($student['gibbonFormGroupID']);
    $event->addRecipient($formGroup['gibbonPersonIDTutor'] ?? '');
    $event->addRecipient($formGroup['gibbonPersonIDTutor2'] ?? '');
    $event->addRecipient($formGroup['gibbonPersonIDTutor3'] ?? '');

    $event->addScope('gibbonPersonIDStudent',  $student['gibbonPersonID']);
    $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);

    $event->sendNotifications($pdo, $session);


    $URLSuccess .= '&return=success0';
    header("Location: {$URLSuccess}");
}




