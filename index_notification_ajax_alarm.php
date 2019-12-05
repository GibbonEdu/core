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

use Gibbon\View\View;

// Gibbon system-wide includes
include './gibbon.php';

$type = $_GET['type'] ?? '';


if (!$gibbon->session->has('gibbonPersonID') || $gibbon->session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    return;
} elseif ($type == 'general' or $type == 'lockdown' or $type == 'custom') {
    $alarm = $pdo->selectOne("SELECT * FROM gibbonAlarm WHERE status='Current'");
    if (empty($alarm)) return;

    $dataConfirm = array('gibbonAlarmID' => $alarm['gibbonAlarmID'], 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
    $sqlConfirm = 'SELECT * FROM gibbonAlarmConfirm WHERE gibbonAlarmID=:gibbonAlarmID AND gibbonPersonID=:gibbonPersonID';
    $confirmed =  $pdo->selectOne($sqlConfirm, $dataConfirm);

    $canViewReport = isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php');
    if ($canViewReport) {
        $dataConfirm = ['gibbonAlarmID' => $alarm['gibbonAlarmID'], 'today' => date('Y-m-d')];
        $sqlConfirm = "SELECT gibbonPerson.gibbonPersonID, status, surname, preferredName, gibbonAlarmConfirmID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonAlarmConfirm ON (gibbonAlarmConfirm.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAlarmID=:gibbonAlarmID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ORDER BY surname, preferredName";
        $confirmationReport = $pdo->select($sqlConfirm, $dataConfirm)->fetchAll();
    }
    
    echo $container->get(View::class)->fetchFromTemplate('ui/alarmOverlay.twig.html', [
        'alarm' => $alarm,
        'confirmed' => $confirmed,
        'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'),
        'customAlarmSound' => getSettingByScope($connection2, 'System Admin', 'customAlarmSound'),
        'canViewReport' => $canViewReport,
        'confirmationReport' => $confirmationReport ?? [],
    ]);
}
