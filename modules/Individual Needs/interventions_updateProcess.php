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

use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionUpdateGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/interventions_manage_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/interventions_update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if (empty($highestAction)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Proceed!
    if (empty($gibbonINInterventionID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $interventionGateway = $container->get(INInterventionGateway::class);
    $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);

    if (empty($intervention)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check access based on the highest action level
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        // Check if the current user is a contributor
        $sql = "SELECT * FROM gibbonINInterventionContributor WHERE gibbonINInterventionID=:gibbonINInterventionID AND gibbonPersonID=:gibbonPersonID";
        $result = $pdo->executeQuery(['gibbonINInterventionID' => $gibbonINInterventionID, 'gibbonPersonID' => $session->get('gibbonPersonID')], $sql);
        
        if ($result->rowCount() == 0) {
            $URL .= '&return=error0';
            header("Location: {$URL}");
            exit;
        }
    }

    // Validate Inputs
    $comment = $_POST['comment'] ?? '';

    if (empty($comment)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Add the update
    $updateGateway = $container->get(INInterventionUpdateGateway::class);
    $data = [
        'gibbonINInterventionID' => $gibbonINInterventionID,
        'gibbonPersonID' => $session->get('gibbonPersonID'),
        'comment' => $comment,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    $inserted = $updateGateway->insert($data);

    if (!$inserted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update intervention status to 'In Progress' if it's currently 'Pending'
    if ($intervention['status'] == 'Pending') {
        $interventionGateway->update($gibbonINInterventionID, ['status' => 'In Progress']);
    }

    // Send notification to the intervention creator and contributors
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = $container->get(NotificationSender::class);

    $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
    $staffName = Format::name($session->get('title'), $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);
    $notificationString = __('A new update has been added to the intervention "{name}" for {student} by {staff}.', [
        'name' => $intervention['name'],
        'student' => $studentName,
        'staff' => $staffName
    ]);
    
    // Notify the creator if not the current user
    if ($intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $notificationSender->addNotification($intervention['gibbonPersonIDCreator'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/interventions_manage_edit.php&gibbonINInterventionID=$gibbonINInterventionID");
    }
    
    // Notify contributors
    $sql = "SELECT gibbonPersonID FROM gibbonINInterventionContributor WHERE gibbonINInterventionID=:gibbonINInterventionID AND gibbonPersonID<>:gibbonPersonID";
    $result = $pdo->executeQuery(['gibbonINInterventionID' => $gibbonINInterventionID, 'gibbonPersonID' => $session->get('gibbonPersonID')], $sql);
    
    while ($contributor = $result->fetch()) {
        $notificationSender->addNotification($contributor['gibbonPersonID'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/interventions_manage_edit.php&gibbonINInterventionID=$gibbonINInterventionID");
    }
    
    $notificationSender->sendNotifications();

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
