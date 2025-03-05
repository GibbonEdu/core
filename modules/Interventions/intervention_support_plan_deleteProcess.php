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

use Gibbon\Module\Interventions\Domain\INSupportPlanGateway;
use Gibbon\Module\Interventions\Domain\INSupportPlanNoteGateway;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINSupportPlanID = $_POST['gibbonINSupportPlanID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process_phase3.php&gibbonINInterventionID='.$gibbonINInterventionID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    $supportPlanNoteGateway = $container->get(INSupportPlanNoteGateway::class);
    
    // Get support plan
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);
    
    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if user is admin
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    
    if (!$isAdmin) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if support plan is active
    if ($supportPlan['status'] == 'Active') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        // Begin transaction
        $connection2->beginTransaction();
        
        // Delete all notes for this support plan
        $data = ['gibbonINSupportPlanID' => $gibbonINSupportPlanID];
        $sql = "DELETE FROM gibbonINSupportPlanNote WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID";
        $pdo->executeQuery($data, $sql);
        
        // Delete the support plan
        $supportPlanGateway->delete($gibbonINSupportPlanID);
        
        // Commit transaction
        $connection2->commit();
        
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } catch (Exception $e) {
        $connection2->rollBack();
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
