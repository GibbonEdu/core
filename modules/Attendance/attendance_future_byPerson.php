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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\Activities\ActivityGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
require_once __DIR__ . '/src/AttendanceView.php';

// set page breadcrumb
$page->breadcrumbs->add(__('Set Future Absence'));

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_future_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->return->addReturns([
        'warning2' => __('Your request was successful, but some data was not properly saved.') .' '. __('The specified date is not in the future, or is not a school day.'),
        'error7' => __('Your request failed because the student has already been marked absent for the full day.'),
        'error8' => __('Your request failed because the selected date is not in the future.'),
    ]);

    $attendance = new AttendanceView($gibbon, $pdo, $container->get(SettingGateway::class));
    $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
    $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);
    $gibbonThemeName = $session->get('gibbonThemeName');

    $scope = (isset($_GET['scope']))? $_GET['scope'] : 'single';

    $gibbonPersonIDList = $_GET['gibbonPersonIDList'] ?? $_GET['gibbonPersonID'] ?? [];
    if (!empty($gibbonPersonIDList)) {
        $gibbonPersonIDList = is_array($gibbonPersonIDList)
            ? array_unique($gibbonPersonIDList)
            : explode(",", $gibbonPersonIDList);
    }

    $target = $_GET['target'] ?? '';
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $gibbonGroupID = $_GET['gibbonGroupID'] ?? '';
    $absenceType = $_GET['absenceType'] ?? 'full';
    $date = $_GET['date'] ?? '';
    $timeStart = $_GET['timeStart'] ?? '';
    $timeEnd = $_GET['timeEnd'] ?? '';

    $urlParams = compact('target', 'gibbonActivityID', 'gibbonGroupID', 'absenceType', 'date', 'timeStart', 'timeEnd');

    $targetDate = !empty($date) ? Format::dateConvert($date) : date('Y-m-d');
    $effectiveStart = strtotime($targetDate.' '.$timeStart);
    $effectiveEnd = strtotime($targetDate.' '.$timeEnd);

    $canTakeAdHocAttendance = isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_adHoc.php');

    // Generate choose student form
    $form = Form::create('attendanceSearch',$session->get('absoluteURL') . '/index.php','GET');
    $form->setTitle(__('Choose Student'));
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q','/modules/'.$session->get('module').'/attendance_future_byPerson.php');

    $availableScopes = [
        'single' => __('Single Student'),
        'multiple' => __('Multiple Students'),
    ];
    $row = $form->addRow();
        $row->addLabel('scope', __('Scope'));
        $row->addSelect('scope')->fromArray($availableScopes)->selected($scope);

    $form->toggleVisibilityByClass('single')->onSelect('scope')->when('single');
    $form->toggleVisibilityByClass('multiple')->onSelect('scope')->when('multiple');

    $row = $form->addRow()->addClass('single');
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->setID('gibbonPersonIDSingle')->required()->placeholder()->selected($gibbonPersonIDList[0] ?? '')->photo(true, 'small');

    if ($canTakeAdHocAttendance) {
        // Show the ad hoc attendance groups
        $targetOptions = [
            'Messenger' => __('Messenger Group'),
            'Activity'  => __('Activity Enrolment'),
            'Select'    => __('Select Students'),
        ];
        $row = $form->addRow()->addClass('multiple');
            $row->addLabel('target', __('Target'));
            $row->addSelect('target')->fromArray($targetOptions)->required()->selected($target)->placeholder();

        $form->toggleVisibilityByClass('targetActivity')->onSelect('target')->when('Activity');
        $form->toggleVisibilityByClass('targetMessenger')->onSelect('target')->when('Messenger');
        $form->toggleVisibilityByClass('targetSelect')->onSelect('target')->when('Select');

        // Activity
        $activities = $container->get(ActivityGateway::class)->selectActivitiesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
        $row = $form->addRow()->addClass('targetActivity');
            $row->addLabel('gibbonActivityID', __('Activity'));
            $row->addSelect('gibbonActivityID')->fromArray($activities)->selected($gibbonActivityID)->required()->placeholder();

        // Messenger Groups
        $groups = $container->get(GroupGateway::class)->selectGroupsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
        $row = $form->addRow()->addClass('targetMessenger');
            $row->addLabel('gibbonGroupID', __('Messenger Group'));
            $row->addSelect('gibbonGroupID')->fromArray($groups)->selected($gibbonGroupID)->required()->placeholder();

    }

    // Select Students
    $studentGateway = $container->get(StudentGateway::class);
    $studentCriteria = $studentGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName']);

    $studentList = $studentGateway->queryStudentsBySchoolYear($studentCriteria, $session->get('gibbonSchoolYearID'));
    $studentList = array_reduce($studentList->toArray(), function ($group, $student) use ($gibbonPersonIDList) {
        $list = in_array($student['gibbonPersonID'], $gibbonPersonIDList) ? 'destination' : 'source';
        $group['students'][$list][$student['gibbonPersonID']] = Format::name($student['title'], $student['preferredName'], $student['surname'], 'Student', true) . ' - ' . $student['formGroup'];
        $group['form'][$student['gibbonPersonID']] = $student['formGroup'];
        return $group;
    });

    $col = $form->addRow()->addClass($canTakeAdHocAttendance ? 'targetSelect' : 'multiple')->addColumn();
        $col->addLabel('gibbonPersonIDList', __('Students'));
        $select = $col->addMultiSelect('gibbonPersonIDList')->isRequired();
        $select->addSortableAttribute(__('Form Group'), $studentList['form']);
        $select->source()->fromArray($studentList['students']['source'] ?? []);
        $select->destination()->fromArray($studentList['students']['destination'] ?? []);

    if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byCourseClass.php')) {
        $availableAbsenceTypes = [
            'full' => __('Full Day'),
            'partial' => __('Partial'),
        ];

        $row = $form->addRow();
            $row->addLabel('absenceType', __('Absence Type'));
            $row->addSelect('absenceType')->fromArray($availableAbsenceTypes)->selected($absenceType);

        $form->toggleVisibilityByClass('partialDateRow')->onSelect('absenceType')->when('partial');
        $row = $form->addRow()->addClass('partialDateRow');
            $row->addLabel('date', __('Date'));
            $row->addDate('date')->required()->setValue($date)->minimum(date('Y-m-d'));

        $row = $form->addRow()->addClass('partialDateRow');
            $row->addLabel('timeStart', __('Start Time'));
            $row->addTime('timeStart')
                ->required()
                ->setValue($timeStart);

        $row = $form->addRow()->addClass('partialDateRow');
            $row->addLabel('timeEnd', __('End Time'));
            $row->addTime('timeEnd')
                ->required()
                ->chainedTo('timeStart')
                ->setValue($timeEnd);
    }


    $form->addRow()->addSearchSubmit($session);

    echo $form->getOutput();

    // Get list of students for selected target
    if (!empty($target)) {
        $targetID = $target == 'Activity' ? $gibbonActivityID : ($target == 'Messenger' ? $gibbonGroupID : $gibbonPersonIDList);
        $students = $attendanceLogGateway->selectAdHocAttendanceStudents($session->get('gibbonSchoolYearID'), $target, $targetID, $targetDate)->fetchAll();
        $gibbonPersonIDList = empty($gibbonPersonIDList) ? array_column($students, 'gibbonPersonID') : $gibbonPersonIDList;
    }

    if(!empty($gibbonPersonIDList)) {
        $today = date('Y-m-d');
        $attendanceLog = '';

        if (!empty($date) && Format::dateConvert($date) < $today) {
            echo Format::alert(__('The specified date is not in the future, or is not a school day.'), 'error');
            return;
        }

        $form = Form::create('attendanceSet',$session->get('absoluteURL') . '/modules/' . $session->get('module') . '/attendance_future_byPersonProcess.php');

        if ($scope == 'single') {
            // Get attendance logs
            $logs = $attendanceLogGateway->selectFutureAttendanceLogsByPersonAndDate($gibbonPersonIDList[0], $targetDate)->fetchAll();

            //Get classes for partial attendance
            $classes = $courseEnrolmentGateway->selectClassesByPersonAndDate($session->get('gibbonSchoolYearID'), $gibbonPersonIDList[0], $targetDate)->fetchAll();

            if ($absenceType == 'partial' && empty($classes)) {
                echo Format::alert(__('Cannot record a partial absence. This student does not have timetabled classes for this day.'));
                return;
            }

            // Filter only classes that are attendanceable
            $classes = array_filter($classes, function ($item) {
                return $item['attendance'] == 'Y';
            });

            // Display attendance logs
            if (!empty($logs)) {
                $table = DataTable::create('logs');
                $table->setTitle(__('Attendance Log'));
                $table->setDescription(__('The following future absences have been set for the selected student.'));

                $table->modifyRows(function ($log, $row) use (&$attendance) {
                    if ($attendance->isTypeAbsent($log['type'])) $row->addClass('error');
                    elseif ($attendance->isTypeOffsite($log['type']) || $log['direction'] == 'Out') $row->addClass('message');
                    elseif ($attendance->isTypeLate($log['type'])) $row->addClass('warning');
                    else $row->addClass('success');

                    return $row;
                });

                $table->addColumn('date', __('Date'))->format(Format::using('date', 'date'));
                $table->addColumn('attendance', __('Attendance'))
                    ->format(function($log) use ($gibbonThemeName) {
                        $output = '<b>'.__($log['direction']).'</b> ('.__($log['type']). (!empty($log['reason'])? ', '.$log['reason'] : '') .')';
                        if (!empty($log['comment']) ) {
                            $output .= '&nbsp;<img title="'.$log['comment'].'" src="./themes/'.$gibbonThemeName.'/img/messageWall.png" width=16 height=16/>';
                        }
                        return $output;
                    });
                $table->addColumn('where', __('Where'))->format(function ($log) {
                    if (($log['context'] == 'Future' || $log['context'] == 'Class') && $log['gibbonCourseClassID'] > 0) {
                        return __($log['context']).' ('.$log['courseName'].'.'.$log['className'].')';
                    } else {
                        return __($log['context']);
                    }
                });
                $table->addColumn('staff', __('Recorded By'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]));
                $table->addColumn('timestamp', __('On'))->format(Format::using('dateTimeReadable', 'timestampTaken'));

                $table->addActionColumn()
                    ->addParam('gibbonPersonID', $gibbonPersonIDList[0] ?? '')
                    ->addParam('gibbonAttendanceLogPersonID')
                    ->addParams($urlParams)
                    ->format(function ($row, $actions) {
                        $actions->addAction('deleteInstant', __('Delete'))
                            ->setIcon('garbage')
                            ->setURL('/modules/Attendance/attendance_future_byPersonDeleteProcess.php')
                            ->addConfirmation(__('Are you sure you want to delete this record? Unsaved changes will be lost.'))
                            ->directLink();
                    });

                echo $table->render($logs);
            }

        } elseif ($scope == 'multiple' && !empty($students)) {

            $form->addRow()->addHeading('Students', __('Students'));
            $grid = $form->addRow()->addGrid('attendance')->setBreakpoints('w-1/2 sm:w-1/4 md:w-1/5 lg:w-1/4');

            foreach ($students as $count => $student) {
                $cell = $grid->addCell()
                    ->setClass('text-center py-2 px-1 -mr-px -mb-px flex flex-col justify-between')
                    ->addClass($student['cellHighlight'] ?? '');

                $studentLink = './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&subpage=Attendance';
                $icon = Format::userBirthdayIcon($student['dob'], $student['preferredName']);

                $cell->addContent(Format::link($studentLink, Format::userPhoto($student['image_240'], 75)))
                    ->setClass('relative')
                    ->append($icon ?? '');
                $cell->addWebLink(Format::name('', htmlPrep($student['preferredName']), htmlPrep($student['surname']), 'Student', false))
                        ->setURL('index.php?q=/modules/Students/student_view_details.php')
                        ->addParam('gibbonPersonID', $student['gibbonPersonID'])
                        ->addParam('subpage', 'Attendance')
                        ->setClass('pt-2 font-bold underline');
                $cell->addContent($student['formGroup'])->wrap('<div class="text-xxs italic">', '</div>');

                if ($absenceType == 'partial') {
                    $col = $cell->addColumn()->addClass('mx-auto flex flex-col items-start');

                    //Get classes for partial attendance
                    $logs = $attendanceLogGateway->selectFutureAttendanceLogsByPersonAndDate($student['gibbonPersonID'], $targetDate)->fetchAll();
                    $classes = $courseEnrolmentGateway->selectClassesByPersonAndDate($session->get('gibbonSchoolYearID'), $student['gibbonPersonID'], $targetDate)->fetchAll();
                    
                    // Filter only classes that are attendanceable
                    $classes = array_filter($classes, function ($item) {
                        return $item['attendance'] == 'Y';
                    });

                    if (!empty($classes)) {
                        $classOptions = array_reduce($classes, function ($group, $class) use (&$logs, $targetDate) {
                            $name = $class['columnName'] . ' - ' . $class['courseNameShort'] . '.' . $class['classNameShort'];

                            foreach ($logs as $log) {
                                if ($log['context'] == 'Class' && $class['gibbonCourseClassID'] == $log['gibbonCourseClassID'] && $log['date'] == $targetDate) {
                                    $name = $log['type'] . ' - ' . $class['courseNameShort'] . '.' . $class['classNameShort'];
                                }
                            }

                            $group[$class['gibbonCourseClassID'].'-'.$class['gibbonTTDayRowClassID']] = $name;
                            return $group;
                        }, []);

                        // Check for overlap with this class
                        $checked = array_reduce($classes, function ($group, $class) use ($targetDate, $effectiveStart, $effectiveEnd) {
                            $classStart = strtotime($targetDate.' '.$class['timeStart']);
                            $classEnd = strtotime($targetDate.' '.$class['timeEnd']);
                            if (($classStart >= $effectiveStart && $classStart < $effectiveEnd)
                                    || ($effectiveStart >= $classStart && $effectiveStart < $classEnd)) {
                                $group[] = $class['gibbonCourseClassID'].'-'.$class['gibbonTTDayRowClassID'];
                            }

                            return $group;
                        }, []);

                        $disabled = array_reduce($classes, function ($group, $class) use (&$logs, $targetDate) {
                            foreach ($logs as $log) {
                                if ($log['context'] == 'Class' && $class['gibbonCourseClassID'] == $log['gibbonCourseClassID'] && $log['date'] == $targetDate && (empty($log['gibbonTTDayRowClassID']) || ($class['gibbonTTDayRowClassID'] == $log['gibbonTTDayRowClassID'])) ) {
                                    $group[] = $class['gibbonCourseClassID'].'-'.$class['gibbonTTDayRowClassID'];
                                }
                            }

                            return $group;
                        }, []);

                        $col->addCheckbox("courses[{$student['gibbonPersonID']}][]")
                            ->setID("classes{$student['gibbonPersonID']}")
                            ->fromArray($classOptions)
                            ->setClass('')
                            ->alignLeft()
                            ->checked($checked + $disabled)
                            ->disabled($disabled);

                    } else {
                        $col->addContent(Format::small(__('N/A')));
                    }
                }
            }

            $form->addRow()->addAlert(__('Total students:').' '. count($gibbonPersonIDList), 'success')->setClass('right')->wrap('<b>', '</b>');
        }

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('scope', $scope);
        $form->addHiddenValue('absenceType', $absenceType);
        $form->addHiddenValue('target', $target);
        $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);
        $form->addHiddenValue('gibbonGroupID', $gibbonGroupID);
        $form->addHiddenValue('date', $date);
        $form->addHiddenValue('timeStart', $timeStart);
        $form->addHiddenValue('timeEnd', $timeEnd);
        $form->addHiddenValue('gibbonPersonIDList', implode(",", $gibbonPersonIDList));

        $form->addRow()->addHeading('Set Future Attendance', __('Set Future Attendance'));

        if ($absenceType == 'full') {
            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'));
                $row->addDate('dateStart')->required()->minimum(date('Y-m-d'));

            $row = $form->addRow();
                $row->addLabel('dateEnd', __('End Date'));
                $row->addDate('dateEnd')->minimum(date('Y-m-d'));
        } else {
            $form->addHiddenValue('dateStart', $date);
            $form->addHiddenValue('dateEnd', $date);

            if ($scope == 'single') {
                $row = $form->addRow();
                $row->addLabel('periodSelectContainer', __('Periods Absent'));

                $table = $row->addTable('periodSelectContainer')->setClass('standardWidth');
                $table->addHeaderRow()->addHeading(Format::dateReadable(Format::dateConvert($date), Format::LONG));

                foreach ($classes as $class) {
                    $name = $class['columnName'] . ' - ' . $class['courseNameShort'] . '.' . $class['classNameShort'];
                    $logName = $name;

                    $classStart = strtotime($targetDate.' '.$class['timeStart']);
                    $classEnd = strtotime($targetDate.' '.$class['timeEnd']);

                    $checked = (($classStart >= $effectiveStart && $classStart < $effectiveEnd)
                            || ($effectiveStart >= $classStart && $effectiveStart < $classEnd));

                    $disabled = false;
                    foreach (array_reverse($logs) as $log) {
                        if ($log['context'] == 'Class' && $class['gibbonCourseClassID'] == $log['gibbonCourseClassID'] && $log['date'] == $targetDate) {
                            $logName = $name . ' ('.$log['type'].')';
                            break;
                        }

                        if ($log['context'] != 'Class' && $log['date'] == $targetDate) {
                            $logName = $name . ' ('.$log['type'].')';
                        }
                    }

                    $row = $table->addRow();
                    $row->addCheckbox("courses[{$gibbonPersonIDList[0]}][]")
                        ->description($logName)
                        ->setValue($class['gibbonCourseClassID'])
                        ->inline()
                        ->setClass('')
                        ->checked($checked ? $class['gibbonCourseClassID'] : '');
                }
            } else {

            }
        }

        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($attendance->getFutureAttendanceTypes())->required()->selected($scope == 'multiple' ? 'Present - Offsite' : 'Absent');

        $row = $form->addRow();
            $row->addLabel('reason', __('Reason'));
            $row->addSelect('reason')->fromArray($attendance->getAttendanceReasons());

        $row = $form->addRow();
            $row->addLabel('comment', __('Comment'))->description(__('255 character limit'));
            $row->addTextArea('comment')->setRows(3)->maxLength(255);

        $form->addRow()->addSubmit();

        echo $attendanceLog;
        echo $form->getOutput();
    }
}
?>

<script type='text/javascript'>
    $("#absenceType").change(function(){
        if ($("#scope").val() != 'multiple') {
            $("#attendanceLog").css("display","none");
            $("#attendanceSet").css("display","none");
        }
    });
    $("#scope").change(function(){
        $("#attendanceLog").css("display","none");
        $("#attendanceSet").css("display","none");
    });
</script>
