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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Domain\Staff\StaffDutyGateway;
use Gibbon\Domain\Staff\StaffDutyPersonGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_duty.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Duty Schedule'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if ($highestAction == 'Duty Schedule_edit') {
        $page->navigator->addHeaderAction('edit', __('Edit Duty Schedule'))
                        ->setURL('/modules/Staff/staff_duty_edit.php')
                        ->setIcon('config')
                        ->displayLabel();
    }
    
    $staffDutyGateway = $container->get(StaffDutyGateway::class);
    $duty = $staffDutyGateway->selectDutyTimeSlots()->fetchGrouped();

    $staffDutyPersonGateway = $container->get(StaffDutyPersonGateway::class);
    $dutyRoster = $staffDutyPersonGateway->selectDutyRoster()->fetchGrouped();

    foreach ($duty as $weekday => $dutyList) {

        $duty[$weekday] = array_map(function ($item) use (&$weekday, &$dutyRoster) {
            $item['roster'] = array_filter($dutyRoster[$item['gibbonStaffDutyID']] ?? [], function ($staff) use (&$weekday) {
                return $weekday == $staff['weekdayName'];
            });
            return $item;
        }, $dutyList);
    }

    $page->writeFromTemplate('dutySchedule.twig.html', [
        'canEdit' => $highestAction == 'Duty Schedule_edit',
        'duty'    => $duty,
    ]);
}
