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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Services\Format;

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
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null,
        	array( 'warning2' => __('Your request was successful, but some data was not properly saved.') .' '. __('The specified date is not in the future, or is not a school day.'),
        		   'error7' => __('Your request failed because the student has already been marked absent for the full day.'),
        		   'error8' => __('Your request failed because the selected date is not in the future.'), )
        );
    }

    $attendance = new AttendanceView($gibbon, $pdo);

    $scope = (isset($_GET['scope']))? $_GET['scope'] : 'single';
    $gibbonPersonID = (isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : [];
    if (!empty($gibbonPersonID)) {
        $gibbonPersonID = is_array($gibbonPersonID)
            ? array_unique($gibbonPersonID)
            : explode(",", $gibbonPersonID);
    }
    $absenceType = (isset($_GET['absenceType']))? $_GET['absenceType'] : 'full';
    $date = (isset($_GET['date']))? $_GET['date'] : '';

    echo '<h2>'.__('Choose Student')."</h2>";

    //Generate choose student form
    $form = Form::create('attendanceSearch',$_SESSION[$guid]['absoluteURL'] . '/index.php','GET');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q','/modules/'.$_SESSION[$guid]['module'].'/attendance_future_byPerson.php');

    $availableScopes = array(
        'single' => __('Single Student'),
        'multiple' => __('Multiple Students'),
    );
    $row = $form->addRow();
        $row->addLabel('scope', __('Scope'));
        $row->addSelect('scope')->fromArray($availableScopes)->selected($scope);

    $form->toggleVisibilityByClass('student')->onSelect('scope')->when('single');
    $form->toggleVisibilityByClass('students')->onSelect('scope')->when('multiple');

    $row = $form->addRow()->addClass('student');
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'])->setID('gibbonPersonIDSingle')->required()->placeholder()->selected($gibbonPersonID[0] ?? '');

    $row = $form->addRow()->addClass('students');
        $row->addLabel('gibbonPersonID', __('Students'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array('allstudents' => true, 'byRoll' => true))->setID('gibbonPersonIDMultiple')->required()->selectMultiple()->selected($gibbonPersonID);


    if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byCourseClass.php')) {
        $availableAbsenceTypes = array(
            'full' => __('Full Day'),
            'partial' => __('Partial'),
        );

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
            $attendanceLog .= "<div id='attendanceLog'>";
                //Get attendance log
                
                    $dataLog = array('gibbonPersonID' => $gibbonPersonID[0], 'date' => $today);
                    $sqlLog = "SELECT gibbonAttendanceLogPersonID, date, direction, type, context, reason, comment, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, preferredName, surname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date>=:date ORDER BY date";
                    $resultLog = $connection2->prepare($sqlLog);
                    $resultLog->execute($dataLog);

                //Get classes for partial attendance
                try {
                    $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID[0], 'date' => !empty($date) ? Format::dateConvert($date) : date('Y-m-d'));
                    $sqlClasses = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as classNameShort, gibbonTTColumnRow.name as columnName, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonTTDayDate.date=:date AND gibbonCourseClassPerson.role='Student' ORDER BY gibbonTTColumnRow.timeStart ASC";
                    $resultClasses = $connection2->prepare($sqlClasses);
                    $resultClasses->execute($dataClasses);
                } catch (PDOException $e) {
                    echo "<div class='error'>" . $e->getMessage() . '</div>';
                }

                if ($absenceType == 'partial' && $resultClasses->rowCount() == 0) {
                    echo '<div class="error">';
                    echo __('Cannot record a partial absence. This student does not have timetabled classes for this day.');
                    echo '</div>';
                    return;
                }

                //Construct attendance log
                if ($resultLog->rowCount() > 0) {
                    $attendanceLog .= '<h4>';
                        $attendanceLog .= __('Attendance Log');
                    $attendanceLog .= '</h4>';

                    $attendanceLog .= "<p><span class='emphasis small'>";
                        $attendanceLog .= __('The following future absences have been set for the selected student.');
                    $attendanceLog .= '</span></p>';

                    $attendanceLog .= '<table class="mini smallIntBorder fullWidth colorOddEven" cellspacing=0>';
                    $attendanceLog .= '<tr class="head">';
                        $attendanceLog .= '<th>'.__('Date').'</th>';
                        $attendanceLog .= '<th>'.__('Attendance').'</th>';
                        $attendanceLog .= '<th>'.__('Where').'</th>';
                        $attendanceLog .= '<th>'.__('Recorded By').'</th>';
                        $attendanceLog .= '<th>'.__('On').'</th>';
                        $attendanceLog .= '<th style="width: 50px;">'.__('Actions').'</th>';
                    $attendanceLog .= '</tr>';

                    while ($rowLog = $resultLog->fetch()) {
                        $attendanceLog .= '<tr class="'.( $rowLog['direction'] == 'Out'? 'error' : 'current').'">';

                        $attendanceLog .= '<td>'.Format::dateReadable($rowLog['date'], '%b %d').'</td>';

                        $attendanceLog .= '<td>';
                        $attendanceLog .= '<b>'.__($rowLog['direction']).'</b> ('.__($rowLog['type']). ( !empty($rowLog['reason'])? ', '.$rowLog['reason'] : '') .')';
                        if ( !empty($rowLog['comment']) ) {
                            $attendanceLog .= '&nbsp;<img title="'.$rowLog['comment'].'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/messageWall.png" width=16 height=16/>';
                        }
                        $attendanceLog .= '</td>';

                        if (($rowLog['context'] == 'Future' || $rowLog['context'] == 'Class') && $rowLog['gibbonCourseClassID'] > 0) {
                            $attendanceLog .= '<td>'.__($rowLog['context']).' ('.$rowLog['courseName'].'.'.$rowLog['className'].')</td>';
                        } else {
                            $attendanceLog .= '<td>'.__($rowLog['context']).'</td>';
                        }

                        $attendanceLog .= '<td>';
                            $attendanceLog .= Format::name('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true);
                        $attendanceLog .= '</td>';

                        $attendanceLog .= '<td>'.Format::dateReadable($rowLog['timestampTaken'], '%R, %b %d').'</td>';

                        $attendanceLog .= '<td>';
                            $attendanceLog .= "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Attendance/attendance_future_byPersonDeleteProcess.php?gibbonPersonID=$gibbonPersonID[0]&gibbonAttendanceLogPersonID=".$rowLog['gibbonAttendanceLogPersonID']."' onclick='confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                        $attendanceLog .= '</td>';
                        $attendanceLog .= '</tr>';
                    }
                    $attendanceLog .= '</table><br/>';
                }
            $attendanceLog .= '</div>';
        }

        $form = Form::create('attendanceSet',$_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_future_byPersonProcess.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('scope', $scope);
        $form->addHiddenValue('absenceType', $absenceType);
        $form->addHiddenValue('gibbonPersonID', implode(",", $gibbonPersonID));

        $form->addRow()->addHeading(__('Set Future Attendance'));

        if ($absenceType == 'full') {
            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'));
                $row->addDate('dateStart')->required()->minimum(date('Y-m-d', strtotime('today +1 day')));

            $row = $form->addRow();
                $row->addLabel('dateEnd', __('End Date'));
                $row->addDate('dateEnd');
        } else {
            $form->addHiddenValue('dateStart', $date);
            $form->addHiddenValue('dateEnd', $date);

            $row = $form->addRow();
                $row->addLabel('periodSelectContainer', __('Periods Absent'));

                $table = $row->addTable('periodSelectContainer')->setClass('standardWidth');
                $table->addHeaderRow()->addHeading(Format::dateReadable(Format::dateConvert($date),'%B %e, %Y'));

                while ($class = $resultClasses->fetch()) {
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
            if ($item['future'] == 'Y') $group[] = $item['name'];
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
