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

use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Interventions/interventions_manage.php&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage_delete.php') == false) {
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

    // Delete the intervention
    $deleted = $interventionGateway->delete($gibbonINInterventionID);

    if (!$deleted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Send notification about deletion
    $notificationGateway = $container->get(NotificationGateway::class);

    $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
    $notificationString = __('The intervention "{name}" for {student} has been deleted.', [
        'name' => $intervention['name'],
        'student' => $studentName
    ]);
    
    // Notify the creator if not the current user
    if ($intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $notificationGateway->addNotification([$intervention['gibbonPersonIDCreator']], 'Intervention', $notificationString, 'interventions_manage.php', [], 'Alert');
    }
    
    // Notify contributors
    $sql = "SELECT gibbonPersonIDContributor FROM gibbonINInterventionContributor WHERE gibbonINInterventionID=:gibbonINInterventionID AND gibbonPersonIDContributor<>:gibbonPersonID";
    $result = $pdo->select($sql, ['gibbonINInterventionID' => $gibbonINInterventionID, 'gibbonPersonID' => $session->get('gibbonPersonID')]);
    
    while ($contributor = $result->fetch()) {
        $notificationGateway->addNotification([$contributor['gibbonPersonIDContributor']], 'Intervention', $notificationString, 'interventions_manage.php', [], 'Alert');
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
