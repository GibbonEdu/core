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
use Gibbon\Url;

//Gibbon system-wide includes
include './gibbon.php';

$gibbonAlarmID = $_GET['gibbonAlarmID'] ?? '';
$URL = Url::fromRoute();

//Proceed!
if (empty($gibbonAlarmID)) {
    header("Location: {$URL}");
} else {
    //Check alarm
    $alarmGateway = $container->get(AlarmGateway::class);

    $alarm = $alarmGateway->getByID($gibbonAlarmID);

    if (!empty($alarm)) {
        //Check confirmation of alarm
        $alarmConfirm =  $alarmGateway->getAlarmConfirmationByPerson($alarm['gibbonAlarmID'], $gibbon->session->get('gibbonPersonID'));

        if (empty($alarmConfirm)) {
            //Insert confirmation
            $dataConfirm =['gibbonAlarmID' => $alarm['gibbonAlarmID'], 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'timestamp' => date('Y-m-d H:i:s')];
            $alarmGateway->insertAlarmConfirm($dataConfirm);
        }
    }

    //Success 0
    header("Location: {$URL}");
}
