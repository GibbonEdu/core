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

use Gibbon\Domain\Activities\ActivitySlotGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

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

    //Check if school year specified
    $gibbonActivityID = $_GET['gibbonActivityID'];
    if ($gibbonActivityID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $search = isset($_GET['search'])? $_GET['search'] : '';
            $gibbonSchoolYearTermID = isset($_GET['gibbonSchoolYearTermID'])? $_GET['gibbonSchoolYearTermID'] : '';

            if ($search != '' || $gibbonSchoolYearTermID != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage.php&search='.$search."&gibbonSchoolYearTermID=".$gibbonSchoolYearTermID."'>".__('Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('activity', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manage_editProcess.php?gibbonActivityID='.$gibbonActivityID.'&search='.$search.'&gibbonSchoolYearTermID='.$gibbonSchoolYearTermID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('Basic Information'));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(40);

            $row = $form->addRow();
                $row->addLabel('provider', __('Provider'));
                $row->addSelect('provider')->required()->fromArray(array('School' => $_SESSION[$guid]['organisationNameShort'], 'External' => __('External')));

            $activityTypes = getSettingByScope($connection2, 'Activities', 'activityTypes');
            if (!empty($activityTypes)) {
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addSelect('type')->fromString($activityTypes)->placeholder();
            }

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('registration', __('Registration'))->description(__('Assuming system-wide registration is open, should this activity be open for registration?'));
                $row->addYesNo('registration')->required();

            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
            $form->addHiddenValue('dateType', $dateType);
            if ($dateType != 'Date') {
                $row = $form->addRow();
                    $row->addLabel('gibbonSchoolYearTermIDList', __('Terms'))->description(__('Terms in which the activity will run.'));
                    $row->addCheckboxSchoolYearTerm('gibbonSchoolYearTermIDList', $_SESSION[$guid]['gibbonSchoolYearID'])->loadFromCSV($values);
            } else {
                $row = $form->addRow();
                    $row->addLabel('listingStart', __('Listing Start Date'))->description(__('Default: 2 weeks before the end of the current term.'));
                    $row->addDate('listingStart')->required()->setValue(dateConvertBack($guid, $values['listingStart']));

                $row = $form->addRow();
                    $row->addLabel('listingEnd', __('Listing End Date'))->description(__('Default: 2 weeks after the start of next term.'));
                    $row->addDate('listingEnd')->required()->setValue(dateConvertBack($guid, $values['listingEnd']));

                $row = $form->addRow();
                    $row->addLabel('programStart', __('Program Start Date'))->description(__('Default: first day of next term.'));
                    $row->addDate('programStart')->required()->setValue(dateConvertBack($guid, $values['programStart']));

                $row = $form->addRow();
                    $row->addLabel('programEnd', __('Program End Date'))->description(__('Default: last day of the next term.'));
                    $row->addDate('programEnd')->required()->setValue(dateConvertBack($guid, $values['programEnd']));
            }

            $row = $form->addRow();
                $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
                $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFromCSV($values);

            $row = $form->addRow();
                $row->addLabel('maxParticipants', __('Max Participants'));
                $row->addNumber('maxParticipants')->required()->maxLength(4);

            $column = $form->addRow()->addColumn();
                $column->addLabel('description', __('Description'));
                $column->addEditor('description', $guid)->setRows(10)->showMedia();

            $payment = getSettingByScope($connection2, 'Activities', 'payment');
            if ($payment != 'None' && $payment != 'Single') {
                $form->addRow()->addHeading(__('Cost'));

                $row = $form->addRow();
                    $row->addLabel('payment', __('Cost'));
                    $row->addCurrency('payment')->required()->maxLength(9);

                $costTypes = array(
                    'Entire Programme' => __('Entire Programme'),
                    'Per Session'      => __('Per Session'),
                    'Per Week'         => __('Per Week'),
                    'Per Term'         => __('Per Term'),
                );

                $row = $form->addRow();
                    $row->addLabel('paymentType', __('Cost Type'));
                    $row->addSelect('paymentType')->required()->fromArray($costTypes);

                $costStatuses = array(
                    'Finalised' => __('Finalised'),
                    'Estimated' => __('Estimated'),
                );

                $row = $form->addRow();
                    $row->addLabel('paymentFirmness', __('Cost Status'));
                    $row->addSelect('paymentFirmness')->required()->fromArray($costStatuses);
            }

            $form->addRow()->addHeading(__('Time Slots'));

            //Block template
            $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek ORDER BY sequenceNumber";
            $sqlSpaces = "SELECT CAST(gibbonSpaceID AS INT) as value, name FROM gibbonSpace ORDER BY name"; //Must cast to int for select to work

            $slotBlock = $form->getFactory()->createTable()->setClass('blank');
                $row = $slotBlock->addRow();
                    $row->addLabel('gibbonDaysOfWeekID', __('Slot Day'));
                    $row->addSelect('gibbonDaysOfWeekID')
                        ->fromQuery($pdo, $sqlWeekdays)
                        ->placeholder()
                        ->addClass('floatLeft');

                $row = $slotBlock->addRow();
                    $row->addLabel('timeStart', __('Slot Start Time'));
                    $row->addTime('timeStart');
                
                $row = $slotBlock->addRow();
                    $row->addLabel('timeEnd', __('Slot End Time'));
                    $row->addTime('timeEnd')
                        ->chainedTo('timeStart');

                $row = $slotBlock->addRow();
                    $row->addLabel('location', __('Location'));
                    $row->addRadio('location')
                        ->fromArray(['Internal' => __('Internal'), 'External' => __('External')])
                        ->inline()
                        ->alignLeft();

                $row = $slotBlock->addRow()->addClass('hideShow');
                    $row->addSelect('gibbonSpaceID')
                        ->fromQuery($pdo, $sqlSpaces)
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
                $slotBlocks = $row->addCustomBlocks('timeSlots', $gibbon->session)
                    ->fromTemplate($slotBlock)
                    ->settings([
                        'placeholder' => __('Time Slots will appear here...'),
                        'sortable' => true,
                    ])
                    ->addToolInput($addBlockButton);

            $activitySlotGateway = $container->get(ActivitySlotGateway::class);
            $timeSlots = $activitySlotGateway->selectBy(['gibbonActivityID' => $gibbonActivityID]);

            foreach ($timeSlots as $slot) {
                //Must cast to int for select to work.
                $slot['gibbonSpaceID'] = intval($slot['gibbonSpaceID']);                
                $slot['location'] = empty($slot['gibbonSpaceID']) ? 'External' : 'Internal';
                $slotBlocks->addBlock($slot['gibbonActivitySlotID'], $slot);
            }

            $form->addRow()->addHeading(__('Current Staff'));

            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT preferredName, surname, gibbonActivityStaff.* FROM gibbonActivityStaff JOIN gibbonPerson ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";

            $results = $pdo->executeQuery($data, $sql);

            if ($results->rowCount() == 0) {
                $form->addRow()->addAlert(__('There are no records to display.'), 'error');
            } else {
                $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a member of staff, any unsaved changes to this record will be lost!'))->wrap('<i>', '</i>');

                $table = $form->addRow()->addTable()->addClass('colorOddEven');

                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Role'));
                $header->addContent(__('Action'));

                while ($staff = $results->fetch()) {
                    $row = $table->addRow();
                        $row->addContent(Format::name('', $staff['preferredName'], $staff['surname'], 'Staff', true, true));
            $row->addContent(__($staff['role']));
            $row->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/></a>')
                ->setURL($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manage_edit_staff_deleteProcess.php')
                ->addParam('address', $_GET['q'])
                ->addParam('gibbonActivityStaffID', $staff['gibbonActivityStaffID'])
                ->addParam('gibbonActivityID', $gibbonActivityID)
                ->addParam('search', $search)
                ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
                ->addConfirmation(__('Are you sure you wish to delete this record?'));
                }
            }

            $form->addRow()->addHeading(__('New Staff'));

            $row = $form->addRow();
                $row->addLabel('staff', __('Staff'));
                $row->addSelectUsers('staff', $_SESSION[$guid]['gibbonSchoolYearID'], array('includeStaff' => true))->selectMultiple();
            
            $staffRoles = array(
                'Organiser' => __('Organiser'),
                'Coach'     => __('Coach'),
                'Assistant' => __('Assistant'),
                'Other'     => __('Other'),
            );

            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
                $row->addSelect('role')->fromArray($staffRoles);

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
                    })
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
