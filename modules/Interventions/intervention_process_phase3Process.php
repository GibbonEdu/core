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
use Gibbon\Services\Format;

require_once '../../gibbon.php';

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=3';

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

// Only admin can activate support plans
if (!$isAdmin) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

// Sanitize the input
$data = $validator->sanitize($_POST);

// Validate required fields
if (empty($data['goals']) || empty($data['strategies']) || empty($data['targetDate']) || empty($data['gibbonPersonIDStaff'])) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Prepare the data to update
$updateData = [
    'goals' => $data['goals'],
    'strategies' => $data['strategies'],
    'resources' => $data['resources'] ?? '',
    'targetDate' => $data['targetDate'],
    'gibbonPersonIDStaff' => $data['gibbonPersonIDStaff'],
    'status' => 'Support Plan Active',
    'dateStart' => date('Y-m-d'),
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
$studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student');

// Notify the form tutor
if ($intervention['gibbonPersonIDFormTutor'] != $gibbonPersonID) {
    $notificationText = sprintf(__('A support plan has been activated for %1$s.'), $studentName);
    
    $notificationGateway->addNotification(
        $intervention['gibbonPersonIDFormTutor'],
        $notificationText,
        'Interventions',
        '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=4'
    );
}

// Notify the staff responsible for implementation
if ($data['gibbonPersonIDStaff'] != $gibbonPersonID) {
    $notificationText = sprintf(__('You have been assigned as the responsible staff member for %1$s\'s intervention.'), $studentName);
    
    $notificationGateway->addNotification(
        $data['gibbonPersonIDStaff'],
        $notificationText,
        'Interventions',
        '/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=4'
    );
}

// Redirect to the implementation phase
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Interventions/intervention_process.php&gibbonINInterventionID='.$gibbonINInterventionID.'&step=4&return=success0';
header("Location: {$URL}");
exit;
