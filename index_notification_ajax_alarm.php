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

use Gibbon\View\View;
use Gibbon\Domain\System\AlarmGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;

// Gibbon system-wide includes
include './gibbon.php';

$type = $_GET['type'] ?? '';

if (!$session->has('gibbonPersonID') || $session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    return;
} elseif ($type == 'general' or $type == 'lockdown' or $type == 'custom') {
    $alarmGateway = $container->get(AlarmGateway::class);

    $alarm = $alarmGateway->selectBy(['status' => 'Current'])->fetch();
    if (empty($alarm)) return;

    $confirmed =  $alarmGateway->getAlarmConfirmationByPerson($alarm['gibbonAlarmID'], $session->get('gibbonPersonID'));
    $canViewReport = isActionAccessible($guid, $connection2, '/modules/System Admin/alarm.php');
    $confirmationReport = $alarmGateway->selectAlarmConfirmation($alarm['gibbonAlarmID'])->fetchAll();

    // Check for staff absent today
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $criteria = $staffAbsenceGateway->newQueryCriteria()->filterBy('date', 'Today')->filterBy('status', 'Approved');
    $absences = $staffAbsenceGateway->queryAbsencesBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));
    $absences = array_reduce($absences->toArray(), function ($group, $item) {
        if ($item['allDay'] != 'Y' && ($item['timeStart'] > date('H:i:s') || $item['timeEnd'] < date('H:i:s'))) return $group;
        $group[] = $item['gibbonPersonID'];
        return $group;
    }, []);
    
    echo $container->get(View::class)->fetchFromTemplate('ui/alarmOverlay.twig.html', [
        'alarm'              => $alarm,
        'confirmed'          => $confirmed,
        'gibbonPersonID'     => $session->get('gibbonPersonID'),
        'customAlarmSound'   => $container->get(SettingGateway::class)->getSettingByScope('System Admin', 'customAlarmSound'),
        'canViewReport'      => $canViewReport,
        'confirmationReport' => $canViewReport ? $confirmationReport : [],
        'staffAbsences'      => $absences,
    ]);
}
