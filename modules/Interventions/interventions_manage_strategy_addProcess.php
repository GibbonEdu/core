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

use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionStrategyGateway;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/interventions_manage_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage_strategy_add.php') == false) {
    // Access denied
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $interventionGateway = $container->get(INInterventionGateway::class);
    $strategyGateway = $container->get(INInterventionStrategyGateway::class);
    
    // Validate the required values are present
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $targetDate = $_POST['targetDate'] ?? '';
    $strategyStatus = $_POST['status'] ?? '';
    
    if (empty($gibbonINInterventionID) || empty($name) || empty($description) || empty($targetDate) || empty($strategyStatus)) {
        $URL = $URL.'&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check access
    $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);
    $highestAction = getHighestGroupedAction($guid, '/modules/Interventions/interventions_manage_strategy_add.php', $connection2);
    
    if (empty($intervention)) {
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $URL = $URL.'&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$interventionGateway->exists($gibbonINInterventionID)) {
        $URL = $URL.'&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $data = [
        'gibbonINInterventionID' => $gibbonINInterventionID,
        'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
        'name' => $name,
        'description' => $description,
        'targetDate' => $targetDate,
        'status' => $strategyStatus
    ];

    // Insert the record
    $gibbonINInterventionStrategyID = $strategyGateway->insert($data);

    // Update the intervention status if needed
    if ($intervention['status'] == 'Form Tutor Review' && $intervention['formTutorDecision'] == 'Try Interventions') {
        $interventionGateway->update($gibbonINInterventionID, ['status' => 'Intervention']);
    }

    $URL = $URL.'&return=success0';
    header("Location: {$URL}");
    exit;
}
