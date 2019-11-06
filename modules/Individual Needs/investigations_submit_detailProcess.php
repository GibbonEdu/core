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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$gibbonINInvestigationID = $_POST['gibbonINInvestigationID'] ?? '';
$gibbonINInvestigationContributionID = $_POST['gibbonINInvestigationContributionID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Individual Needs/investigations_submit_detail.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonINInvestigationContributionID=$gibbonINInvestigationContributionID";
$URLSuccess = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Individual Needs/investigations_submit.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonINInvestigationContributionID=$gibbonINInvestigationContributionID";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_submit_detail.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Validate the database records exist
    $investigationGateway = $container->get(INInvestigationGateway::class);
    $criteria = $investigationGateway->newQueryCriteria();
    $investigation = $investigationGateway->queryInvestigationsByID($criteria, $gibbonINInvestigationID, $_SESSION[$guid]['gibbonSchoolYearID']);
    $investigation = $investigation->getRow(0);

    $contributionsGateway = $container->get(INInvestigationContributionGateway::class);
    $criteria2 = $contributionsGateway->newQueryCriteria();
    $contribution = $contributionsGateway->queryContributionsByID($criteria2, $gibbonINInvestigationContributionID);
    $contribution = $contribution->getRow(0);

    if (empty($investigation) || empty($contribution) || $contribution['gibbonPersonID'] != $gibbon->session->get('gibbonPersonID')) {
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

        // Validate the required values are present
        if (empty($data['cognition'])) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        // Update the record
        $updated = $contributionsGateway->update($gibbonINInvestigationContributionID, $data);

        //Check for completion, update status and issue notifications
        $completion = $contributionsGateway->queryInvestigationCompletion($criteria2, $gibbonINInvestigationID);
        if ($completion['complete'] == $completion['total']) {
            $data = [
                'status'            => 'Investigation Complete',
            ];
            $investigationGateway->update($gibbonINInvestigationID, $data);

            $notificationGateway = new NotificationGateway($pdo);
            $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

            $notificationString = __('An Individual Needs investigation for %1$s has been completed.');

            //Originating teacher
            $notificationSender->addNotification($investigation['gibbonPersonIDCreator'], sprintf($notificationString, Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");

            //Form tutors
            if ($investigation['gibbonPersonIDTutor'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDTutor'], sprintf($notificationString, Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }
            if ($investigation['gibbonPersonIDTutor2'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDTutor2'], sprintf($notificationString, Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }
            if ($investigation['gibbonPersonIDTutor3'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDTutor3'], sprintf($notificationString, Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }

            //HOY
            if ($investigation['gibbonPersonIDHOY'] != '') {
                $notificationSender->addNotification($investigation['gibbonPersonIDHOY'], sprintf($notificationString, Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            }

            //LS role
            $notificatioRole = getSettingByScope($connection2, 'Individual Needs', 'investigationNotificationRole');
            if (!empty($notificatioRole)) {
                $roleGateway = $container->get(RoleGateway::class);
                $criteria3 = $roleGateway->newQueryCriteria();
                $users = $roleGateway->queryUsersByRole($criteria3, $notificatioRole);
                foreach ($users AS $user) {
                    $notificationSender->addNotification($user['gibbonPersonID'], sprintf($notificationString, Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
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
