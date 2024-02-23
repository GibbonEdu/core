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
use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Comms\NotificationEvent;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/investigations_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $investigationGateway = $container->get(INInvestigationGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
        'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
        'gibbonPersonIDStudent' => $_POST['gibbonPersonIDStudent'] ?? '',
        'status'                => 'Referral',
        'date'                  => Format::dateConvert($_POST['date']) ?? '',
        'reason'                => $_POST['reason'] ?? '',
        'strategiesTried'       => $_POST['strategiesTried'] ?? '',
        'parentsInformed'       => $_POST['parentsInformed'] ?? '',
        'parentsResponse'       => $_POST['parentsResponse'] ?? null
    ];

    // Validate the required values are present
    if (empty($data['gibbonPersonIDStudent']) || empty($data['date']) || empty($data['reason']) || empty($data['parentsInformed'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonINInvestigationID = $investigationGateway->insert($data);

    //Notify form tutors
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = $container->get(NotificationSender::class);

    // Raise a new notification event
    $event = new NotificationEvent('Individual Needs', 'New Investigation');
    if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
        $investigation = $investigationGateway->getInvestigationByID($gibbonINInvestigationID);
        $student = Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true);

        $event->setNotificationText(__('A new Individual Needs investigation has been created for {student}.', ['student' => $student]));
        $event->setActionLink("/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
        $event->addScope('gibbonYearGroupID', $investigation['gibbonYearGroupID']);
        $event->addScope('gibbonPersonIDStudent', $investigation['gibbonPersonID']);

        if ($investigation['gibbonPersonIDTutor'] != '') {
            $event->addRecipient($investigation['gibbonPersonIDTutor']);
        }
        if ($investigation['gibbonPersonIDTutor2'] != '') {
            $event->addRecipient($investigation['gibbonPersonIDTutor2']);
        }
        if ($investigation['gibbonPersonIDTutor3'] != '') {
            $event->addRecipient($investigation['gibbonPersonIDTutor3']);
        }

        $event->pushNotifications($notificationGateway, $notificationSender);
    }
    

    // Send all notifications
    $sendReport = $notificationSender->sendNotifications();

    $URL .= !$gibbonINInvestigationID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonINInvestigationID");
}
