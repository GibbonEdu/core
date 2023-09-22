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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_duty_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Duty Schedule'), 'staff_duty.php')
        ->add(__('Edit Duty Schedule'));
    
    $staffDutyGateway = $container->get(StaffDutyGateway::class);
    
    // FORM
    $form = Form::create('dutyEdit', $session->get('absoluteURL').'/modules/Staff/staff_duty_editProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__('Add Time Slot'))->addClass('addBlock');

    //Block template
    $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
        $row = $blockTemplate->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')
                ->addClass('mb-2')
                ->append('<input type="hidden" id="gibbonStaffDutyID" name="gibbonStaffDutyID" value="">');

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
            $row->addLabel('gibbonDaysOfWeekIDList', __('Weekday'));
            $row->addCheckbox('gibbonDaysOfWeekIDList')
                ->fromQuery($pdo, $sqlWeekdays)
                ->setLabelClass('w-20')
                ->setClass('my-3')
                ->inline()
                ->alignLeft();

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('dutyList', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click', 'sortable' => true))
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
?>

<script type="text/javascript">
        var time = 'input[id^="time"]';
        function setTimepicker(input) {
            input.removeClass('hasTimepicker').timepicker({
                    'scrollDefault': 'now',
                    'timeFormat': 'H:i',
                    'minTime': '00:00',
                    'maxTime': '23:59',
                    onSelect: function(){$(this).blur();},
                    onClose: function(){$(this).change();}
                });
        }

        $(document).ready(function(){
            //This is to ensure that loaded blocks have timepickers
            $(time).each(function() {
                setTimepicker($(this));
            });

            //This is needed to ensure that loaded timeEnds are properly chained to loaded timeStarts
            $('input[id^=timeEnd]').each(function() {
                var timeStart = $('#' + $(this).prop('id').replace('End', 'Start'));
                $(this).timepicker('option', {'minTime': timeStart.val(), 'timeFormat': 'H:i', 'showDuration': true});
            });
        });

        //This is needed to make chaining Times work with Custom Blocks
        $(document).on('changeTime', 'input[id^=timeStart]', function() {
            var timeEnd = $('#' + $(this).prop('id').replace('Start', 'End'));
            if (timeEnd.val() == "" || $(this).val() > timeEnd.val()) {
                timeEnd.val($(this).val());
            }
            timeEnd.timepicker('option', {'minTime': $(this).val(), 'timeFormat': 'H:i', 'showDuration': true});
        });

        //This is needed to make Time inputs have time pickers.
        $(document).on('click', '.addBlock', function () {
            setTimepicker($(time));
        });
    </script>
