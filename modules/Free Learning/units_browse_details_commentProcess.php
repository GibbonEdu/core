<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

require_once '../../gibbon.php';

$freeLearningUnitID = $_POST['freeLearningUnitID'] ?? '';
$freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? $session->get('gibbonPersonID');
$comment = $_POST['addComment'] ?? '';
$comment = nl2br($comment);

$urlParams = [
    'freeLearningUnitStudentID' => $freeLearningUnitStudentID,
    'freeLearningUnitID'        => $freeLearningUnitID,
    'showInactive'              => $_GET['showInactive'] ?? 'N',
    'gibbonDepartmentID'        => $_REQUEST['gibbonDepartmentID'] ?? '',
    'difficulty'                => $_GET['difficulty'] ?? '',
    'name'                      => $_GET['name'] ?? '',
    'view'                      => $_GET['view'] ?? '',
    'sidebar'                   => 'true',
    'gibbonPersonID'            => $gibbonPersonID,
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details.php&tab=1&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $unitGateway = $container->get(UnitGateway::class);
    $unitStudentGateway = $container->get(UnitStudentGateway::class);
    $discussionGateway = $container->get(DiscussionGateway::class);
    $collaborativeAssessment = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'collaborativeAssessment');
    $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

    // Validate the required values
    if (empty($freeLearningUnitID) || empty($freeLearningUnitStudentID) || empty($comment)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the record exists
    $unit = $unitGateway->getByID($freeLearningUnitID);
    $values = $unitStudentGateway->getByID($freeLearningUnitStudentID);
    if (empty($values) || empty($unit)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Insert discussion records
    $data = [
        'foreignTable'         => 'freeLearningUnitStudent',
        'foreignTableID'       => $freeLearningUnitStudentID,
        'gibbonModuleID'       => getModuleIDFromName($connection2, 'Free Learning'),
        'gibbonPersonID'       => $session->get('gibbonPersonID'),
        'gibbonPersonIDTarget' => $values['gibbonPersonIDStudent'],
        'comment'              => $comment,
        'type'                 => 'Comment',
        'tag'                  => 'dull',
    ];

    if ($collaborativeAssessment == 'Y' AND !empty($values['collaborationKey'])) {
        $collaborators = $unitStudentGateway->selectBy(['collaborationKey' => $values['collaborationKey']])->fetchAll();
        foreach ($collaborators as $collaborator) {
            $data['foreignTableID'] = $collaborator['freeLearningUnitStudentID'];
            $data['gibbonPersonIDTarget'] = $collaborator['gibbonPersonIDStudent'];
            $discussionGateway->insert($data);
        }
    } else {
        $discussionGateway->insert($data);
    }

    // Raise a new notification event
    $event = new NotificationEvent('Free Learning', 'Unit Comment');

    $canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php');
    if ($canManage && $roleCategory != 'Student') {
        $event->setNotificationText(sprintf(__m('A teacher has added a comment to your current unit (%1$s).'), $unit['name']));
        $event->setActionLink("/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&sidebar=true&tab=1");
        $event->addRecipient($values['gibbonPersonIDStudent']);

        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details_approval.php&'.http_build_query($urlParams);

    } else {
        $user = $container->get(UserGateway::class)->getByID($session->get('gibbonPersonID'));
        $studentName = Format::name('', $user['preferredName'], $user['surname'], 'Student', false, true);

        $event->addScope('gibbonPersonIDStudent', $values['gibbonPersonIDStudent']);
        $event->setNotificationText(__m('{student} has added a comment to their current unit ({unit}).', ['student' => $studentName, 'unit' => $unit['name']]));
        $event->setActionLink("/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitID=$freeLearningUnitID&freeLearningUnitStudentID=$freeLearningUnitStudentID&sidebar=true");

        if ($values['enrolmentMethod'] == 'class') {
            // Attempt to notify teacher(s) of class
            $courseGateway = $container->get(CourseEnrolmentGateway::class);
            $teachers = $courseGateway->selectClassTeachersByStudent($session->get('gibbonSchoolYearID'), $values['gibbonPersonIDStudent'], $values['gibbonCourseClassID'])->fetchAll();

            foreach ($teachers as $teacher) {
                $event->addRecipient($teacher['gibbonPersonID']);
            }
        } elseif ($values['enrolmentMethod'] == 'schoolMentor' && !empty($values['gibbonPersonIDSchoolMentor'])) {
            // Attempt to notify school mentor
            $event->addRecipient($values['gibbonPersonIDSchoolMentor']);

        } elseif ($values['enrolmentMethod'] == 'externalMentor') {
            // Not available through the Mentor interface
        }
    }

    // Send any notifications
    $event->sendNotifications($pdo, $session);

    $URL .= "&return=success0";
    header("Location: {$URL}");
    exit;
}
