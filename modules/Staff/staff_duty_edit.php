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
use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_duty_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Duty Schedule'), 'staff_duty.php')
        ->add(__('Edit Duty Schedule'));
    
    $staffDutyGateway = $container->get(StaffDutyGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $types = $settingGateway->getSettingByScope('Staff', 'staffDutyTypes');
    
    // FORM
    $form = Form::create('dutyEdit', $session->get('absoluteURL').'/modules/Staff/staff_duty_editProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->removeMeta();

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__('Add Time Slot'))->addClass('addBlock');

    //Block template
    $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
        $row = $blockTemplate->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')
                ->addClass('mb-2');

            $row->addLabel('nameShort', __('Short Name'));
            $row->addTextField('nameShort')
                ->addClass('mb-2');

        $row = $blockTemplate->addRow();
            $row->addLabel('timeStart', __('Start Time'));
            $row->addTime('timeStart');
        
            $row->addLabel('timeEnd', __('End Time'));
            $row->addTime('timeEnd')
                ->chainedTo('timeStart');

        $row = $blockTemplate->addRow();
            $row->addLabel('type', __('Type'));
                $row->addSelect('type')->fromString($types);

            $row->addLabel('gibbonDaysOfWeekIDList', __('Weekday'));
            $row->addCheckbox('gibbonDaysOfWeekIDList')
                ->fromQuery($pdo, $sqlWeekdays)
                // ->setLabelClass('w-20')
                ->inline()
                ->alignLeft();

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('dutyList', $session)
        ->fromTemplate($blockTemplate)
        ->settings([
            'inputNameStrategy' => 'object',
            'addOnEvent' => 'click',
            'sortable' => true,
            'uniqueID' => 'gibbonStaffDutyID',
        ])
        ->placeholder(__('Time Slots will appear here...'))
        ->addToolInput($addBlockButton);

    // Add existing duty
    $criteria = $staffDutyGateway->newQueryCriteria()
        ->sortBy('sequenceNumber')
        ->pageSize(0);

    $dutyList = $staffDutyGateway->queryDuty($criteria);

    foreach ($dutyList as $duty) {
        $duty['gibbonDaysOfWeekIDList'] = explode(',', $duty['gibbonDaysOfWeekIDList']);
        $customBlocks->addBlock($duty['gibbonStaffDutyID'], $duty);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

}
