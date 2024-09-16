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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_class_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $search = $_GET['search'] ?? '';
    $urlParams = compact('gibbonSchoolYearID', 'search');

    $page->breadcrumbs
        ->add(__('Manage Courses & Classes'), 'course_manage.php', $urlParams)
        ->add(__('Edit Course & Classes'), 'course_manage_edit.php', $urlParams + ['gibbonCourseID' => $gibbonCourseID])
        ->add(__('Add Class'));

    if (!empty($search)) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Timetable Admin', 'course_manage.php')->withQueryParams($urlParams));
    }

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL') . '/index.php?q=/modules/Timetable Admin/course_manage_class_edit.php&gibbonCourseClassID=' . $_GET['editID'] . '&gibbonCourseID=' . $gibbonCourseID . '&gibbonSchoolYearID=' . $gibbonSchoolYearID;
    }

    $page->return->setEditLink($editLink);

    if ($gibbonSchoolYearID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = 'SELECT gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            $values = $result->fetch();

            $settingGateway = $container->get(SettingGateway::class);

            $form = Form::create('action', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/course_manage_class_addProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
            $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);

            $row = $form->addRow()->addHeading('Basic Details', __('Basic Details'));

            $row = $form->addRow();
            $row->addLabel('schoolYearName', __('School Year'));
            $row->addTextField('schoolYearName')->required()->readonly()->setValue($values['yearName']);

            $row = $form->addRow();
            $row->addLabel('courseName', __('Course'));
            $row->addTextField('courseName')->required()->readonly()->setValue($values['courseName']);

            $row = $form->addRow();
            $row->addLabel('name', __('Name'))->description(__('Must be unique for this course.'));
            $row->addTextField('name')->required()->maxLength(30);

            $row = $form->addRow();
            $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique for this course.'));
            $row->addTextField('nameShort')->required()->maxLength(8);

            $row = $form->addRow();
            $row->addLabel('reportable', __('Reportable?'))->description(__('Should this class show in reports?'));
            $row->addYesNo('reportable');

            if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
                $row = $form->addRow();
                $row->addLabel('attendance', __('Track Attendance?'))->description(__('Should this class allow attendance to be taken?'));
                $row->addYesNo('attendance');
            }

            $row = $form->addRow()->addHeading('Advanced Options', __('Advanced Options'));

            $enrolmentMinDefault = $settingGateway->getSettingByScope('Timetable Admin', 'enrolmentMinDefault');
            $row = $form->addRow();
            $row->addLabel('enrolmentMin', __('Minimum Enrolment'))->description(__('Class should not run below this number of students.'));
            $row->addNumber('enrolmentMin')->onlyInteger(true)->minimum(1)->maximum(9999)->maxLength(4)->setValue(is_numeric($enrolmentMinDefault) ? $enrolmentMinDefault : '');

            $enrolmentMaxDefault = $settingGateway->getSettingByScope('Timetable Admin', 'enrolmentMaxDefault');
            $row = $form->addRow();
            $row->addLabel('enrolmentMax', __('Maximum Enrolment'))->description(__('Enrolment should not exceed this number of students.'));
            $row->addNumber('enrolmentMax')->onlyInteger(true)->minimum(1)->maximum(9999)->maxLength(4)->setValue(is_numeric($enrolmentMaxDefault) ? $enrolmentMaxDefault : '');


            //Block template
            $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek ORDER BY sequenceNumber";

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

            $row->addLabel('timeEnd', __('Slot End Time'));
            $row->addTime('timeEnd')
                ->chainedTo('timeStart');

            $row = $slotBlock->addRow();
            $row->addLabel('location', __('Location'));

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

            // Custom Fields
            $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Class', []);

            $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

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
                        onSelect: function() {
                            $(this).blur();
                        },
                        onClose: function() {
                            $(this).change();
                        }
                    });
                }

                $(document).ready(function() {
                    //This is to ensure that loaded blocks have the correct state.
                    $(radio + ':checked').each(locationSwap);

                    //This is to ensure that loaded blocks have timepickers
                    $(time).each(function() {
                        setTimepicker($(this));
                    });

                    //This is needed to ensure that loaded timeEnds are properly chained to loaded timeStarts
                    $('input[id^=timeEnd]').each(function() {
                        var timeStart = $('#' + $(this).prop('id').replace('End', 'Start'));
                        $(this).timepicker('option', {
                            'minTime': timeStart.val(),
                            'timeFormat': 'H:i',
                            'showDuration': true
                        });
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
                    timeEnd.timepicker('option', {
                        'minTime': $(this).val(),
                        'timeFormat': 'H:i',
                        'showDuration': true
                    });
                });

                //This is needed to make Time inputs have time pickers.
                $(document).on('click', '.addBlock', function() {
                    setTimepicker($(time));
                });
            </script>
<?php
        }
    }
}
