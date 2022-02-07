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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

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
    $gibbonThemeName = $gibbon->session->get('gibbonThemeName');

    $scope = (isset($_GET['scope']))? $_GET['scope'] : 'single';
    $gibbonPersonID = (isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : [];
    if (!empty($gibbonPersonID)) {
        $gibbonPersonID = is_array($gibbonPersonID)
            ? array_unique($gibbonPersonID)
            : explode(",", $gibbonPersonID);
    }
    $absenceType = (isset($_GET['absenceType']))? $_GET['absenceType'] : 'full';
    $date = (isset($_GET['date']))? $_GET['date'] : '';

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

    $form->toggleVisibilityByClass('student')->onSelect('scope')->when('single');
    $form->toggleVisibilityByClass('students')->onSelect('scope')->when('multiple');

    $row = $form->addRow()->addClass('student');
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $gibbon->session->get('gibbonSchoolYearID'))->setID('gibbonPersonIDSingle')->required()->placeholder()->selected($gibbonPersonID[0] ?? '');

    $row = $form->addRow()->addClass('students');
        $row->addLabel('gibbonPersonID', __('Students'));
        $row->addSelectStudent('gibbonPersonID', $gibbon->session->get('gibbonSchoolYearID'), array('allstudents' => true, 'byForm' => true))->setID('gibbonPersonIDMultiple')->required()->selectMultiple()->selected($gibbonPersonID);

    if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byCourseClass.php')) {
        $availableAbsenceTypes = [
            'full' => __('Full Day'),
            'partial' => __('Partial'),
        ];

        $row = $form->addRow()->addClass('student');
            $row->addLabel('absenceType', __('Absence Type'));
            $row->addSelect('absenceType')->fromArray($availableAbsenceTypes)->selected($absenceType);

        $form->toggleVisibilityByClass('partialDateRow')->onSelect('absenceType')->when('partial');
        $row = $form->addRow()->addClass('partialDateRow');
            $row->addLabel('date', __('Date'));
            $row->addDate('date')->required()->setValue($date)->minimum(date('Y-m-d', strtotime('today +1 day')));
    }

    $form->addRow()->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if(!empty($gibbonPersonID)) {
        $today = date('Y-m-d');
        $attendanceLog = '';

        if (!empty($date) && Format::dateConvert($date) <= $today) {
            echo Format::alert(__('The specified date is not in the future, or is not a school day.'), 'error');
            return;
        }

        if ($scope == 'single') {
            // Get attendance logs
            $logs = $attendanceLogGateway->selectFutureAttendanceLogsByPersonAndDate($gibbonPersonID[0], $today)->fetchAll();

            //Get classes for partial attendance
            $classes = $courseEnrolmentGateway->selectClassesByPersonAndDate($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID[0], !empty($date) ? Format::dateConvert($date) : date('Y-m-d'));

            if ($absenceType == 'partial' && empty($classes)) {
                echo Format::alert(__('Cannot record a partial absence. This student does not have timetabled classes for this day.'));
                return;
            }

            // Display attendance logs
            if (!empty($logs)) {
                $table = DataTable::create('logs');
                $table->setTitle(__('Attendance Log'));
                $table->setDescription(__('The following future absences have been set for the selected student.'));

                $table->modifyRows(function ($log, $row) {
                    $row->addClass($log['direction'] == 'Out' ? 'error' : 'success');
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
                $table->addColumn('timestamp', __('On'))->format(Format::using('dateTimeReadable', 'timestampTaken', '%R, %b %d'));

                $table->addActionColumn()
                    ->addParam('gibbonPersonID', $gibbonPersonID[0] ?? '')
                    ->addParam('gibbonAttendanceLogPersonID')
                    ->format(function ($row, $actions) {
                        $actions->addAction('deleteInstant', __('Delete'))
                            ->setIcon('garbage')
                            ->setURL('/modules/Attendance/attendance_future_byPersonDeleteProcess.php')
                            ->addConfirmation(__('Are you sure you want to delete this record? Unsaved changes will be lost.'))
                            ->directLink();
                    });

                echo $table->render($logs);
            }

        }

        $form = Form::create('attendanceSet',$session->get('absoluteURL') . '/modules/' . $session->get('module') . '/attendance_future_byPersonProcess.php');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('scope', $scope);
        $form->addHiddenValue('absenceType', $absenceType);
        $form->addHiddenValue('gibbonPersonID', implode(",", $gibbonPersonID));

        $form->addRow()->addHeading('Set Future Attendance', __('Set Future Attendance'));

        if ($absenceType == 'full') {
            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'));
                $row->addDate('dateStart')->required()->minimum(date('Y-m-d', strtotime('today +1 day')));

            $row = $form->addRow();
                $row->addLabel('dateEnd', __('End Date'));
                $row->addDate('dateEnd')->minimum(date('Y-m-d', strtotime('today +1 day')));
        } else {
            $form->addHiddenValue('dateStart', $date);
            $form->addHiddenValue('dateEnd', $date);

            $row = $form->addRow();
                $row->addLabel('periodSelectContainer', __('Periods Absent'));

                $table = $row->addTable('periodSelectContainer')->setClass('standardWidth');
                $table->addHeaderRow()->addHeading(Format::dateReadable(Format::dateConvert($date),'%B %e, %Y'));

                foreach ($classes as $class) {
                    $row = $table->addRow();
                    $row->addCheckbox('courses[]')
                        ->description($class['columnName'] . ' - ' . $class['courseNameShort'] . '.' . $class['classNameShort'])
                        ->setValue($class['gibbonCourseClassID'])
                        ->inline()
                        ->setClass('');
                }
        }

        // Filter only attendance types with future = 'Y'
        $attendanceTypes = array_reduce($attendance->getAttendanceTypes(), function ($group, $item) {
            if ($item['future'] == 'Y') $group[$item['name']] = __($item['name']);
            return $group;
        }, array());

        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($attendanceTypes)->required()->selected('Absent');

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
