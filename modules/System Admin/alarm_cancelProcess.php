<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

include '../../gibbon.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/System Admin/alarm.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonAlarmID = $_GET['gibbonAlarmID'] ?? '';

    //Validate Inputs
    if (empty($gibbonAlarmID)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $alarmGateway = $container->get(AlarmGateway::class);
        $settingGateway = $container->get(SettingGateway::class);
        //DEAL WITH ALARM SETTING
        //Write setting to database
        $dataWhere = ['scope' => 'System', 'name' => 'alarm'];
        $settingGateway->updateWhere($dataWhere, ['value' => 'None']);
        //Write alarm to database
        $alarmGateway->update($gibbonAlarmID, ['status' => 'Past', 'timestampEnd' => date('Y-m-d H:i:s')]);

        getSystemSettings($guid, $connection2);
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
