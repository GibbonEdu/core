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
$gibbonINSupportPlanNoteID = $_POST['gibbonINSupportPlanNoteID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_note_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID.'&gibbonINSupportPlanNoteID='.$gibbonINSupportPlanNoteID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_support_plan_view.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonINSupportPlanID='.$gibbonINSupportPlanID;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_support_plan_note_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $supportPlanGateway = $container->get(INSupportPlanGateway::class);
    $supportPlanNoteGateway = $container->get(INSupportPlanNoteGateway::class);
    
    // Get support plan and note
    $supportPlan = $supportPlanGateway->getByID($gibbonINSupportPlanID);
    $note = $supportPlanNoteGateway->getByID($gibbonINSupportPlanNoteID);
    
    if (empty($supportPlan) || $supportPlan['gibbonINInterventionID'] != $gibbonINInterventionID ||
        empty($note) || $note['gibbonINSupportPlanID'] != $gibbonINSupportPlanID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check access to this note
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
    $isAdmin = isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php', 'Manage Interventions_all');
    
    if (!$isAdmin && $note['gibbonPersonID'] != $gibbonPersonID) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate Inputs
    $title = $_POST['title'] ?? '';
    $noteText = $_POST['note'] ?? '';
    $date = $_POST['date'] ?? '';
    
    if (empty($title) || empty($noteText) || empty($date)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    try {
        $data = [
            'title' => $title,
            'note' => $noteText,
            'date' => $date,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Update the note
        $supportPlanNoteGateway->update($gibbonINSupportPlanNoteID, $data);
        
        $URLSuccess .= '&return=success0';
        header("Location: {$URLSuccess}");
        exit;
    } catch (Exception $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
}
