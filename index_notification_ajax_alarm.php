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
use Gibbon\Domain\System\AlarmGateway;

// Gibbon system-wide includes
include './gibbon.php';

$type = $_GET['type'] ?? '';

if (!$gibbon->session->has('gibbonPersonID') || $gibbon->session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    return;
} elseif ($type == 'general' or $type == 'lockdown' or $type == 'custom') {
    $alarmGateway = $container->get(AlarmGateway::class);

    $alarm = $alarmGateway->selectBy(['status' => 'Current'])->fetch();
    if (empty($alarm)) return;

    $confirmed =  $alarmGateway->getAlarmConfirmationByPerson($alarm['gibbonAlarmID'], $gibbon->session->get('gibbonPersonID'));
    $canViewReport = isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php');
    $confirmationReport = $alarmGateway->selectAlarmConfirmation($alarm['gibbonAlarmID'])->fetchAll();
    
    echo $container->get(View::class)->fetchFromTemplate('ui/alarmOverlay.twig.html', [
        'alarm'              => $alarm,
        'confirmed'          => $confirmed,
        'gibbonPersonID'     => $gibbon->session->get('gibbonPersonID'),
        'customAlarmSound'   => getSettingByScope($connection2, 'System Admin', 'customAlarmSound'),
        'canViewReport'      => $canViewReport,
        'confirmationReport' => $canViewReport ? $confirmationReport : [],
    ]);
}
