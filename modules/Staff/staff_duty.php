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

use Gibbon\Domain\Staff\StaffDutyGateway;
use Gibbon\Domain\Staff\StaffDutyPersonGateway;
use Gibbon\Domain\System\SettingGateway;

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
    $duty = $staffDutyGateway->selectDutyTimeSlots()->fetchAll();

    $staffDutyPersonGateway = $container->get(StaffDutyPersonGateway::class);
    $dutyRoster = $staffDutyPersonGateway->selectDutyRoster()->fetchGrouped();

    $settingGateway = $container->get(SettingGateway::class);
    $types = $settingGateway->getSettingByScope('Staff', 'staffDutyTypes');
    $types = array_filter(array_map('trim', explode(',', $types)));

    // TABS
    $tabs = array_combine($types, array_fill(0, count($types), []));

    $dutyByType = array_reduce($duty, function ($group, $item) {
        $item['type'] = empty($item['type']) ? __('Staff Duty') : $item['type'];
        $group[$item['type']][$item['weekdayName']][] = $item;
        return $group;
    }, $tabs);

    foreach ($dutyByType as $dutyType => $dutyByWeekday) {

        if (empty($dutyByWeekday)) {
            unset($tabs[$dutyType]);
            continue;
        }

        $maxCount = 0;
        foreach ($dutyByWeekday as $weekday => $dutyList) {

            $dutyByWeekday[$weekday] = array_map(function ($item) use (&$weekday, &$dutyRoster) {
                $item['roster'] = array_filter($dutyRoster[$item['gibbonStaffDutyID']] ?? [], function ($staff) use (&$weekday) {
                    return $weekday == $staff['weekdayName'];
                });
                return $item;
            }, $dutyList);

            $maxCount = max($maxCount, count($dutyList));
        }

        $tabs[$dutyType] = [
            'label'   => __($dutyType),
            'content' => $page->fetchFromTemplate('dutySchedule.twig.html', [
                'canEdit'   => $highestAction == 'Duty Schedule_edit',
                'duty'      => $dutyByWeekday,
                'maxCount'  => $maxCount,
            ])
        ];
    }

    $defaultTab = $_GET['tab'] ?? 1;

    $page->writeFromTemplate('ui/tabs.twig.html', [
        'id'       => 'dutyOuter',
        'selected' => $defaultTab ?? 1,
        'tabs'     => $tabs,
        'outset'   => false,
        'icons'    => false,
    ]);
}
