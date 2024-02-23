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

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInvestigationID = $_POST['gibbonINInvestigationID'] ?? '';
$gibbonINInvestigationContributionID = $_POST['gibbonINInvestigationContributionID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/investigations_submit_detail.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonINInvestigationContributionID=$gibbonINInvestigationContributionID";
$URLSuccess = $session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/investigations_submit.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonINInvestigationContributionID=$gibbonINInvestigationContributionID";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_submit_detail.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Validate the database records exist
    $investigationGateway = $container->get(INInvestigationGateway::class);
    $investigation = $investigationGateway->getInvestigationByID($gibbonINInvestigationID);

    $contributionsGateway = $container->get(INInvestigationContributionGateway::class);
    $contribution = $contributionsGateway->getContributionByID($gibbonINInvestigationContributionID);

    if (empty($investigation) || empty($contribution) || $contribution['gibbonPersonID'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        $data = [
            'status'            => 'Complete',
            'cognition'         => $_POST['cognition'] ?? null,
            'memory'            => (!empty($_POST['memory'])) ? serialize($_POST['memory']) : '',
            'selfManagement'    => (!empty($_POST['selfManagement'])) ? serialize($_POST['selfManagement']) : '',
            'attention'         => (!empty($_POST['attention'])) ? serialize($_POST['attention']) : '',
            'socialInteraction' => (!empty($_POST['socialInteraction'])) ? serialize($_POST['socialInteraction']) : '',
            'communication'     => (!empty($_POST['communication'])) ? serialize($_POST['communication']) : '',
            'comment'           => $_POST['comment'] ?? ''
        ];

        // Update the record
        $updated = $contributionsGateway->update($gibbonINInvestigationContributionID, $data);

        //Check for completion, update status and issue notifications
        $completion = $contributionsGateway->getInvestigationCompletion($gibbonINInvestigationID);
        if ($completion['complete'] == $completion['total']) {
            $data = [
                'status'            => 'Investigation Complete',
            ];
            $investigationGateway->update($gibbonINInvestigationID, $data);

            $notificationGateway = $container->get(NotificationGateway::class);
            $notificationSender = $container->get(NotificationSender::class);;

            $studentName = Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true);
            $notificationString = __('An Individual Needs investigation for {student} has been completed.', ['student' => $studentName]);

            //Originating teacher
            $notificationSender->addNotification($investigation['gibbonPersonIDCreator'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");

            //Form tutors
            if ($investigation['gibbonPersonIDTutor'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDTutor'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }
            if ($investigation['gibbonPersonIDTutor2'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDTutor2'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }
            if ($investigation['gibbonPersonIDTutor3'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDTutor3'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }

            //HOY
            if ($investigation['gibbonPersonIDHOY'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDHOY'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }

            //LS role
            $notificationRole = $container->get(SettingGateway::class)->getSettingByScope('Individual Needs', 'investigationNotificationRole');
            if (!empty($notificationRole)) {
                $roleGateway = $container->get(RoleGateway::class);
                $criteria = $roleGateway->newQueryCriteria();
                $users = $roleGateway->queryUsersByRole($criteria, $notificationRole);
                foreach ($users AS $user) {
                    $notificationSender->addNotification($user['gibbonPersonID'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
                }
            }

            $notificationSender->sendNotifications();
        }

        if ($updated) {
            $URLSuccess .= "&return=success0";
            header("Location: {$URLSuccess}");
        }
        else {
            $URL .= "&return=error2";
            header("Location: {$URL}");
        }
    }
}
