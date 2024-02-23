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
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\Activities\ActivitySlotGateway;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Activities'), 'activities_manage.php')
        ->add(__('Edit Activity'));
    
    $page->return->addReturns(['error3' => __('Your request failed due to an attachment error.')]);

    //Check if gibbonActivityID specified
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    if ($gibbonActivityID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $activityGateway = $container->get(ActivityGateway::class);
        $values = $activityGateway->getByID($gibbonActivityID);

        if (empty($values)) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            //Let's go!
            $search = $_GET['search'] ?? '';
            $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? '';

            $settingGateway = $container->get(SettingGateway::class);

            $form = Form::create('activity', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/activities_manage_editProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);
            $form->addHiddenValue('search', $search);
            $form->addHiddenValue('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);

            if (!empty($search) || !empty($gibbonSchoolYearTermID)) {
                $form->addHeaderAction('back', __('Back to Search Results'))
                    ->setURL('/modules/' . $session->get('module') . '/activities_manage.php')
                    ->addParam('search', $search)
                    ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);
            }


            $form->addRow()->addHeading('Basic Information', __('Basic Information'));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')
                    ->required()
                    ->maxLength(40);

            $row = $form->addRow();
                $row->addLabel('provider', __('Provider'));
                $row->addSelect('provider')
                    ->required()
                    ->fromArray([
                        'School' => $session->get('organisationNameShort'),
                        'External' => __('External')
                    ]);

            $activityTypes = $activityGateway->selectActivityTypeOptions()->fetchKeyPair();

            if (!empty($activityTypes)) {
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromArray($activityTypes)->placeholder();
            }

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('registration', __('Registration'))->description(__('Assuming system-wide registration is open, should this activity be open for registration?'));
                $row->addYesNo('registration')->required();

            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
            $form->addHiddenValue('dateType', $dateType);
            if ($dateType != 'Date') {
                $row = $form->addRow();
                    $row->addLabel('gibbonSchoolYearTermIDList', __('Terms'))->description(__('Terms in which the activity will run.'));
                    $row->addCheckboxSchoolYearTerm('gibbonSchoolYearTermIDList', $session->get('gibbonSchoolYearID'))->loadFromCSV($values);
            } else {
                $row = $form->addRow();
                    $row->addLabel('listingStart', __('Listing Start Date'))->description(__('Default: 2 weeks before the end of the current term.'));
                    $row->addDate('listingStart')
                        ->required()
                        ->setValue(Format::date($values['listingStart']));

                $row = $form->addRow();
                    $row->addLabel('listingEnd', __('Listing End Date'))->description(__('Default: 2 weeks after the start of next term.'));
                    $row->addDate('listingEnd')
                        ->required()
                        ->setValue(Format::date($values['listingEnd']));

                $row = $form->addRow();
                    $row->addLabel('programStart', __('Program Start Date'))->description(__('Default: first day of next term.'));
                    $row->addDate('programStart')
                        ->required()
                        ->setValue(Format::date($values['programStart']));

                $row = $form->addRow();
                    $row->addLabel('programEnd', __('Program End Date'))->description(__('Default: last day of the next term.'));
                    $row->addDate('programEnd')
                        ->required()
                        ->setValue(Format::date($values['programEnd']));
            }

            $row = $form->addRow();
                $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
                $row->addCheckboxYearGroup('gibbonYearGroupIDList')
                    ->addCheckAllNone()
                    ->loadFromCSV($values);

            $row = $form->addRow();
                $row->addLabel('maxParticipants', __('Max Participants'));
                $row->addNumber('maxParticipants')
                    ->required()
                    ->maxLength(4);

            $col = $form->addRow()->addColumn();
                $col->addLabel('description', __('Description'));
                $col->addEditor('description', $guid)
                    ->setRows(10)
                    ->showMedia();

            $payment = $settingGateway->getSettingByScope('Activities', 'payment');
            if ($payment != 'None' && $payment != 'Single') {
                $form->addRow()->addHeading('Cost', __('Cost'));

                $row = $form->addRow();
                    $row->addLabel('payment', __('Cost'));
                    $row->addCurrency('payment')
                        ->required()
                        ->maxLength(9);

                $row = $form->addRow();
                    $row->addLabel('paymentType', __('Cost Type'));
                    $row->addSelect('paymentType')
                        ->required()
                        ->fromArray([
                            'Entire Programme' => __('Entire Programme'),
                            'Per Session'      => __('Per Session'),
                            'Per Week'         => __('Per Week'),
                            'Per Term'         => __('Per Term')
                        ]);

                $row = $form->addRow();
                    $row->addLabel('paymentFirmness', __('Cost Status'));
                    $row->addSelect('paymentFirmness')
                        ->required()
                        ->fromArray([
                            'Finalised' => __('Finalised'),
                            'Estimated' => __('Estimated')
                        ]);
            }

            $form->addRow()->addHeading('Time Slots', __('Time Slots'));

            //Block template
            $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek ORDER BY sequenceNumber";

            $slotBlock = $form->getFactory()->createTable()->setClass('blank');
                $row = $slotBlock->addRow();
                    $row->addLabel('gibbonDaysOfWeekID', __('Slot Day'));
                    $row->addSelect('gibbonDaysOfWeekID')
                        ->fromQuery($pdo, $sqlWeekdays)
                        ->placeholder()
                        ->addClass('floatLeft')
                        ->append('<input type="hidden" id="gibbonActivitySlotID" name="gibbonActivitySlotID" value="">');

                $row = $slotBlock->addRow();
                    $row->addLabel('timeStart', __('Slot Start Time'));
                    $row->addTime('timeStart');
                
                    $row->addLabel('timeEnd', __('Slot End Time'));
                    $row->addTime('timeEnd')
                        ->chainedTo('timeStart');

                $row = $slotBlock->addRow();
                    $row->addLabel('location', __('Location'));
                    $row->addRadio('location')
                        ->inline()
                        ->alignLeft()
                        ->fromArray([
                            'Internal' => __('Internal'),
                            'External' => __('External')
                        ]);

                $row = $slotBlock->addRow()->addClass('hideShow');
                    $row->addSelectSpace('gibbonSpaceID')
                        ->placeholder()
                        ->addClass('sm:max-w-full w-full');

                $row = $slotBlock->addRow()->addClass('showHide');
                    $row->addTextField("locationExternal")
                        ->maxLength(50)
                        ->addClass('sm:max-w-full w-full');

            //Tool Button
            $addBlockButton = $form->getFactory()
                ->createButton(__('Add Time Slot'))
                ->addClass('addBlock');

            //Custom Blocks
            $row = $form->addRow();
                $slotBlocks = $row->addCustomBlocks('timeSlots', $session)
                    ->fromTemplate($slotBlock)
                    ->settings([
                        'placeholder' => __('Time Slots will appear here...'),
                        'sortable' => true,
                    ])
                    ->addToolInput($addBlockButton);

            $slotBlocks->addPredefinedBlock("Add Time Slot", ['location' => 'Internal']);
            $activitySlotGateway = $container->get(ActivitySlotGateway::class);
            $timeSlots = $activitySlotGateway->selectBy(['gibbonActivityID' => $gibbonActivityID]);

            foreach ($timeSlots as $slot) {
                $slot['location'] = empty($slot['gibbonSpaceID']) ? 'External' : 'Internal';
                $slotBlocks->addBlock($slot['gibbonActivitySlotID'], $slot);
            }

            $form->addRow()->addHeading('Current Staff', __('Current Staff'));

            $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a member of staff, any unsaved changes to this record will be lost!'))->wrap('<i>', '</i>');

            $staffTable = $form->addRow()->addDataTable('staffTable');

            $staffTable->addColumn('name', __('Name'))
                ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', true, true]));

            $staffTable->addColumn('role', __('Role'))
                ->format(function($staff) {
                    return __($staff['role']);
                });

            $staffTable->addActionColumn()
                ->addParam('gibbonActivityStaffID')
                ->addParam('gibbonActivityID', $gibbonActivityID)
                ->addParam('search', $search)
                ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
                ->format(function ($staff, $actions) {
                    $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/Activities/activities_manage_edit_staff_delete.php');
                });

            $activityStaffGateway = $container->get(ActivityStaffGateway::class);
            $staffTable->withData($activityStaffGateway->selectActivityStaff($gibbonActivityID)->toDataSet());

            $form->addRow()->addHeading('New Staff', __('New Staff'));

            $row = $form->addRow();
                $row->addLabel('staff', __('Staff'));
                $row->addSelectUsers('staff', $session->get('gibbonSchoolYearID'), ['includeStaff' => true])->selectMultiple();
            
            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')
                    ->fromArray([
                        'Organiser' => __('Organiser'),
                        'Coach'     => __('Coach'),
                        'Assistant' => __('Assistant'),
                        'Other'     => __('Other')
                    ]);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
            ?>

            <script type="text/javascript">
                //All of this javascript is due to limitations of CustomBlocks. If these limitaions are fixed in the future, the corresponding block of code should be removed.
                var radio = 'input[type="radio"][name$="[location]"]';

                function locationSwap() {
                    var block = $(this).closest('tbody');
                    if ($(this).prop('id').startsWith('location0')) {
                        block.find('.showHide').hide();
                        block.find('.hideShow').show();
                    } else {
                        block.find('.showHide').show();
                        block.find('.hideShow').hide();
                    }
                }

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
                    //This is to ensure that loaded blocks have the correct state.
                    $(radio + ':checked').each(locationSwap);

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

                //This supplements triggers for the Internal and External Locations
                $(document).on('change', radio, locationSwap);

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

            <?php
        }
    }
}
