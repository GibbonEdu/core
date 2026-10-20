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

use Gibbon\Domain\System\AlarmGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/alarm.php';

$gibbonAlarmID = $_POST['gibbonAlarmID'] ?? '';

if (!$session->has('gibbonPersonID') || $session->get('gibbonRoleIDCurrentCategory') != 'Staff' || isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonAlarmID)) {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $alarmGateway = $container->get(AlarmGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    // Require a current alarm sounded by this user
    $alarm = $alarmGateway->selectBy(['gibbonAlarmID' => $gibbonAlarmID, 'status' => 'Current'])->fetch();
    if (empty($alarm) || $alarm['gibbonPersonID'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // DEAL WITH ALARM SETTING
    $dataWhere = ['scope' => 'System', 'name' => 'alarm'];
    $settingGateway->updateWhere($dataWhere, ['value' => 'None']);

    // Write alarm to database
    $alarmGateway->update($alarm['gibbonAlarmID'], ['status' => 'Past', 'timestampEnd' => date('Y-m-d H:i:s')]);

    getSystemSettings($guid, $connection2);
    $URL .= '&return=success0';
    header("Location: {$URL}");
}
