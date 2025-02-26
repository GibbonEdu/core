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

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/interventions_manage_edit.php') == false) {
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
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Validate Inputs
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $strategies = $_POST['strategies'] ?? '';
    $targetDate = $_POST['targetDate'] ?? '';
    $newStatus = $_POST['status'] ?? '';
    $parentConsent = $_POST['parentConsent'] ?? '';

    if (empty($name) || empty($description) || empty($strategies) || empty($targetDate) || empty($newStatus) || empty($parentConsent)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check for parent consent status change
    $parentConsentDate = $intervention['parentConsentDate'];
    $gibbonPersonIDConsent = $intervention['gibbonPersonIDConsent'];
    
    if ($parentConsent != $intervention['parentConsent']) {
        if ($parentConsent == 'Consent Given' || $parentConsent == 'Consent Denied') {
            $parentConsentDate = date('Y-m-d');
            $gibbonPersonIDConsent = $session->get('gibbonPersonID');
        } else {
            $parentConsentDate = null;
            $gibbonPersonIDConsent = null;
        }
    }

    // Update the intervention
    $data = [
        'name' => $name,
        'description' => $description,
        'strategies' => $strategies,
        'targetDate' => $targetDate,
        'status' => $newStatus,
        'parentConsent' => $parentConsent,
        'parentConsentDate' => $parentConsentDate,
        'gibbonPersonIDConsent' => $gibbonPersonIDConsent,
    ];

    $updated = $interventionGateway->update($gibbonINInterventionID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Send notification if status has changed
    if ($newStatus != $intervention['status']) {
        $notificationGateway = $container->get(NotificationGateway::class);

        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
        $notificationString = __('The intervention "{name}" for {student} has been updated to {status}.', [
            'name' => $name,
            'student' => $studentName,
            'status' => $newStatus
        ]);
        
        // Notify the creator if not the current user
        if ($intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $notificationGateway->addNotification([$intervention['gibbonPersonIDCreator']], 'Individual Needs', $notificationString, 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID
            ], 'Alert');
        }
        
        // Notify contributors
        $sql = "SELECT gibbonPersonID FROM gibbonINInterventionContributor WHERE gibbonINInterventionID=:gibbonINInterventionID AND gibbonPersonID<>:gibbonPersonID";
        $result = $pdo->executeQuery(['gibbonINInterventionID' => $gibbonINInterventionID, 'gibbonPersonID' => $session->get('gibbonPersonID')], $sql);
        
        while ($contributor = $result->fetch()) {
            $notificationGateway->addNotification([$contributor['gibbonPersonID']], 'Individual Needs', $notificationString, 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID
            ], 'Alert');
        }
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
