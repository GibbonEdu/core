<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Services\Format;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\NotificationSender;

require_once '../../gibbon.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonINSupportPlanID = $_POST['gibbonINSupportPlanID'] ?? '';
$gibbonINSupportPlanProgressID = $_POST['gibbonINSupportPlanProgressID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_progress_delete.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID.'&gibbonINSupportPlanProgressID='.$gibbonINSupportPlanProgressID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_progress_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    
    // Get support plan
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);
    
    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check access to this intervention
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    $isCoordinator = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_my');
    
    // Check if user is a contributor with edit rights
    $data = [
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID,
        'gibbonPersonID' => $gibbonPersonID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanContributor 
            WHERE gibbonINSupportPlanID=:gibbonINSupportPlanID 
            AND gibbonPersonID=:gibbonPersonID 
            AND canEdit='Y'";
    $resultContributor = $pdo->executeQuery($data, $sql);
    $isContributor = ($resultContributor->rowCount() > 0);
    
    if (!$isAdmin && !$isCoordinator && !$isContributor) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Get progress report details
    $data = [
        'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
        'gibbonINSupportPlanID' => $gibbonINSupportPlanID
    ];
    $sql = "SELECT * FROM gibbonINSupportPlanProgress 
            WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
            AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
    $resultProgress = $pdo->executeQuery($data, $sql);
    
    if ($resultProgress->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    $progress = $resultProgress->fetch();
    
    // Check if user is the creator of the progress report or has admin/coordinator access
    if (!$isAdmin && !$isCoordinator && $progress['gibbonPersonID'] != $gibbonPersonID) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        // Delete the progress report
        $data = [
            'gibbonINSupportPlanProgressID' => $gibbonINSupportPlanProgressID,
            'gibbonINSupportPlanID' => $gibbonINSupportPlanID
        ];
        
        $sql = "DELETE FROM gibbonINSupportPlanProgress 
                WHERE gibbonINSupportPlanProgressID=:gibbonINSupportPlanProgressID 
                AND gibbonINSupportPlanID=:gibbonINSupportPlanID";
        
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        $URLSuccess .= '&return=success0';
        header("Location: {$URLSuccess}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
