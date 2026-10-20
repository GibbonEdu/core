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

use Gibbon\Data\Validator;
use Gibbon\Domain\System\AlarmGateway;
use Gibbon\Http\Url;

//Gibbon system-wide includes
require_once __DIR__ . '/gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonAlarmID = $_POST['gibbonAlarmID'] ?? '';
$URL = Url::fromRoute();

// Proceed!
if (!$session->has('gibbonPersonID') || $session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} elseif (empty($gibbonAlarmID)) {
    header("Location: {$URL}");
    exit;
} else {
    $alarmGateway = $container->get(AlarmGateway::class);

    // Require an existing alarm that is currently active
    $alarm = $alarmGateway->selectBy(['gibbonAlarmID' => $gibbonAlarmID, 'status' => 'Current'])->fetch();
    if (empty($alarm)) {
        header("Location: {$URL}");
        exit;
    }

    $gibbonPersonID = $session->get('gibbonPersonID');

    // Sounder does not self-confirm; they use Turn Alarm Off
    if ($gibbonPersonID == $alarm['gibbonPersonID']) {
        header("Location: {$URL}");
        exit;
    }

    // Check confirmation of alarm
    $alarmConfirm = $alarmGateway->getAlarmConfirmationByPerson($alarm['gibbonAlarmID'], $gibbonPersonID);

    if (empty($alarmConfirm)) {
        // Insert confirmation for the logged-in staff member only
        $dataConfirm = ['gibbonAlarmID' => $alarm['gibbonAlarmID'], 'gibbonPersonID' => $gibbonPersonID, 'timestamp' => date('Y-m-d H:i:s')];
        $alarmGateway->insertAlarmConfirm($dataConfirm);
    }

    // Success 0
    header("Location: {$URL}");
}
