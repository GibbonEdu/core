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
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=4';

if (empty($gibbonINInterventionID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Proceed!
$interventionGateway = $container->get(INInterventionGateway::class);
$notificationGateway = $container->get(NotificationGateway::class);
$validator = $container->get(Validator::class);

// Get the intervention
$intervention = $interventionGateway->getByID($gibbonINInterventionID);

if (empty($intervention)) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

// Check permissions
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
$highestAction = getHighestGroupedAction($guid, '/modules/Interventions/intervention_process.php', $connection2);

$isAdmin = ($highestAction == 'Manage Interventions');
$isResponsibleStaff = ($intervention['gibbonPersonIDStaff'] == $gibbonPersonID);

// Only admin or responsible staff can complete implementation
if (!$isAdmin && !$isResponsibleStaff) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

// Prepare the data to update
$updateData = [
    'status' => 'Ready for Evaluation',
    'dateEnd' => date('Y-m-d'),
];

// Update the intervention
$updated = $interventionGateway->update($gibbonINInterventionID, $updateData);

if (!$updated) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

// Send notifications
// Get the student name for the notification
$studentName = formatName('', $intervention['preferredName'], $intervention['surname'], 'Student');

// Notify the form tutor
if ($intervention['gibbonPersonIDFormTutor'] != $gibbonPersonID) {
    $notificationText = sprintf(__('The implementation phase for %1$s\'s intervention is complete and ready for evaluation.'), $studentName);
    
    $notificationGateway->addNotification(
        $intervention['gibbonPersonIDFormTutor'],
        $notificationText,
        'Interventions',
        '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=5'
    );
}

// Notify the creator if different from form tutor and current user
if ($intervention['gibbonPersonIDCreator'] != $intervention['gibbonPersonIDFormTutor'] && $intervention['gibbonPersonIDCreator'] != $gibbonPersonID) {
    $notificationText = sprintf(__('The implementation phase for %1$s\'s intervention is complete and ready for evaluation.'), $studentName);
    
    $notificationGateway->addNotification(
        $intervention['gibbonPersonIDCreator'],
        $notificationText,
        'Interventions',
        '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=5'
    );
}

// Redirect to the evaluation phase
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=5&return=success0';
header("Location: {$URL}");
exit;
