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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_future_byPerson.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Set Future Absence').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null,
        	array( 'warning2' => __($guid, 'Your request was successful, but some data was not properly saved.') .' '. __($guid, 'The specified date is not in the future, or is not a school day.'),
        		   'error7' => __($guid, 'Your request failed because the student has already been marked absent for the full day.'),
        		   'error8' => __($guid, 'Your request failed because the selected date is not in the future.'), )
        );
    }

    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);
    $gibbonPersonID = (isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : null;
    $absenceType = (isset($_GET['absenceType']))? $_GET['absenceType'] : 'full';
    $date = (isset($_GET['date']))? date($_GET['date']) : '';
    $dateSQL =  dateConvert($guid, $date);
    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $attType = $attendance->getAttendanceTypes();


    //Generate choose student form
    $attendanceSearchForm = Form::create('attendanceSearch',$_SESSION[$guid]['absoluteURL'] . '/index.php','GET');
    $attendanceSearchForm->addRow()->addHeading('CHOOSE STUDENT');
    $attendanceSearchForm->setFactory(DatabaseFormFactory::create($pdo));
    $attendanceSearchForm->addHiddenValue('q','/modules/'.$_SESSION[$guid]['module'].'/attendance_future_byPerson.php');
    $attendanceSearchForm->addHiddenValue('new','newform');

    $asf_gibbonPersonRow = $attendanceSearchForm->addRow();
    $asf_gibbonPersonRow->addLabel('gibbonPersonID','Student');
    $asf_gibbonPersonRow->addSelectStudent('gibbonPersonID',$_SESSION[$guid]['gibbonSchoolYearID']);

    $availableAbsenceTypes = array(
        'full' => 'Full Day',
        'partial' => 'Partial'
    );
    $asf_absenceType = $attendanceSearchForm->addRow();
    $asf_absenceType->addLabel('absenceType','Absence Type');
    $asf_absenceType->addSelect('absenceType')->fromArray($availableAbsenceTypes)->selected($absenceType);;

    $asf_partialDate = $attendanceSearchForm->addRow()->addClass('partialDateRow');
    $asf_partialDate->addLabel('date','Date');
    $asf_partialDate->addDate('date')->placeholder($date);;

    $attendanceSearchForm->addRow()->addSubmit();

    $attendanceSearchForm->toggleVisibilityByClass('partialDateRow')->onSelect('absenceType')->when('partial');

    echo $attendanceSearchForm->getOutput();

    if($gibbonPersonID != null)
    {

        $today = date('Y-m-d');

        //Get attendance log
        try {
            $dataLog = array('gibbonPersonID' => $gibbonPersonID, 'date' => "$today-0-0-0"); //"$today-23-59-59"
            $sqlLog = "SELECT gibbonAttendanceLogPersonID, date, direction, type, context, reason, comment, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, preferredName, surname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date>=:date ORDER BY date";
            $resultLog = $connection2->prepare($sqlLog);
            $resultLog->execute($dataLog);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        //Get classes for partial attendance
        try {
            $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'date' => $dateSQL );
            $sqlClasses = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as classNameShort, gibbonTTColumnRow.name as columnName, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonTTDayDate.date=:date ORDER BY gibbonTTColumnRow.timeStart ASC";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        } catch (PDOException $e) {
            echo "<div class='error'>" . $e->getMessage() . '</div>';
        }

        //Construct attendance log
        $attendanceLog = '';
        if ($resultLog->rowCount() > 0) {
            $attendanceLog .= '<h4>';
                $attendanceLog .= __($guid, 'Attendance Log');
            $attendanceLog .= '</h4>';

            $attendanceLog .= "<p><span class='emphasis small'>";
                $attendanceLog .= __($guid, 'The following future absences have been set for the selected student.');
            $attendanceLog .= '</span></p>';

            $attendanceLog .= '<table class="mini smallIntBorder fullWidth colorOddEven" cellspacing=0>';
            $attendanceLog .= '<tr class="head">';
                $attendanceLog .= '<th>'.__($guid, 'Date').'</th>';
                $attendanceLog .= '<th>'.__($guid, 'Attendance').'</th>';
                $attendanceLog .= '<th>'.__($guid, 'Where').'</th>';
                $attendanceLog .= '<th>'.__($guid, 'Recorded By').'</th>';
                $attendanceLog .= '<th>'.__($guid, 'On').'</th>';
                $attendanceLog .= '<th style="width: 50px;">'.__($guid, 'Actions').'</th>';

            $attendanceLog .= '</tr>';
            while ($rowLog = $resultLog->fetch()) {

                $attendanceLog .= '<tr class="'.( $rowLog['direction'] == 'Out'? 'error' : 'current').'">';

                $attendanceLog .= '<td>'.date("M j", strtotime($rowLog['date']) ).'</td>';


                $attendanceLog .= '<td>';
                $attendanceLog .= '<b>'.$rowLog['direction'].'</b> ('.$rowLog['type']. ( !empty($rowLog['reason'])? ', '.$rowLog['reason'] : '') .')';

                if ( !empty($rowLog['comment']) ) {
                    $attendanceLog .= '&nbsp;<img title="'.$rowLog['comment'].'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/messageWall.png" width=16 height=16/>';
                }
                $attendanceLog .= '</td>';


                if ($rowLog['context'] != '') {
                    if (($rowLog['context'] == 'Future' || $rowLog['context'] == 'Class') && $rowLog['gibbonCourseClassID'] > 0)
                        $attendanceLog .= '<td>'.__($guid, $rowLog['context']).' ('.$rowLog['courseName'].'.'.$rowLog['className'].')</td>';
                    else
                        $attendanceLog .= '<td>'.__($guid, $rowLog['context']).'</td>';
                }
                else {
                    $attendanceLog .= '<td>'.__($guid, 'Roll Group').'</td>';
                }

                $attendanceLog .= '<td>';
                    $attendanceLog .= formatName('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true);
                $attendanceLog .= '</td>';

                $attendanceLog .= '<td>'.date("g:i a, M j", strtotime($rowLog['timestampTaken']) ).'</td>';


                $attendanceLog .= '<td>';
                    $attendanceLog .= "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Attendance/attendance_future_byPersonDeleteProcess.php?gibbonPersonID=$gibbonPersonID&gibbonAttendanceLogPersonID=".$rowLog['gibbonAttendanceLogPersonID']."' onclick='confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                $attendanceLog .= '</td>';

                $attendanceLog .= '</tr>';
            }
            $attendanceLog .= '</table><br/>';
        }


        switch($absenceType)
        {
            case "full":
                $setAttendanceForm = Form::create('attendanceSet',$_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_future_byPersonProcess.php?gibbonPersonID=' . $gibbonPersonID,'POST');
                $setAttendanceForm->addRow()->addHeading('SET FUTURE ATTENDANCE');
                $setAttendanceForm->addHiddenValue('q','/modules/'.$_SESSION[$guid]['module'].'/attendance_future_byPerson.php');
                $setAttendanceForm->addHiddenValue('absenceType','full');
                $setAttendanceForm->addHiddenValue('address','/modules/Attendance/attendance_future_byPerson.php');

                $saf_sDate = $setAttendanceForm->addRow();
                $saf_sDate->addLabel('dateStart','Start Date');
                $saf_sDate->addDate('dateStart')->isRequired();

                $saf_eDate = $setAttendanceForm->addRow();
                $saf_eDate->addLabel('dateEnd','End Date');
                $saf_eDate->addDate('dateEnd');

                $saf_type = $setAttendanceForm->addRow();
                $saf_type->addLabel('type','Type');
                $saf_type->addSelect('type')->fromArray(array_keys($attendance->getAttendanceTypes()))->isRequired();

                $saf_reason = $setAttendanceForm->addRow();
                $saf_reason->addLabel('reason','Reason');
                $saf_reason->addSelect('reason')->fromArray($attendance->getAttendanceReasons());

                $saf_comment = $setAttendanceForm->addRow();
                $saf_comment->addLabel('comment','Comment')->description('255 character limit');
                $saf_comment->addTextArea('comment')->setRows(3)->maxLength(255);

                $setAttendanceForm->addRow()->addSubmit();

                echo $attendanceLog;
                echo $setAttendanceForm->getOutput();

                break;

            case "partial":
                if($resultClasses->rowCount() > 0)
                {
                    $setAttendanceForm = Form::create('attendanceSet',$_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_future_byPersonProcess.php?gibbonPersonID=' . $gibbonPersonID,'POST');
                    $setAttendanceForm->addRow()->addHeading('SET FUTURE ATTENDANCE');
                    $setAttendanceForm->addHiddenValue('q','/modules/'.$_SESSION[$guid]['module'].'/attendance_future_byPerson.php');
                    $setAttendanceForm->addHiddenValue('absenceType','partial');
                    $setAttendanceForm->addHiddenValue('dateStart',$date);
                    $setAttendanceForm->addHiddenValue('dateEnd',$date);
                    $setAttendanceForm->addHiddenValue('address','/modules/Attendance/attendance_future_byPerson.php');

                    $dateSQL = dateConvert($guid, $date);
                    $saf_periodSelect = $setAttendanceForm->addRow();
                    $saf_periodSelect->addLabel('periodSelectContainer','Periods Absent');
                    $saf_periodSelectContainer = $saf_periodSelect->addTable('periodSelectContainer')->removeClass('fullWidth')->removeClass('formTable')->addClass('standardWidth');
                    $saf_periodSelectContainer->addHeaderRow('classTableHeaderRow')->addHeading(date('F j, Y', strtotime($dateSQL) ))->addClass('classTableHeader');
                    $i = 0;
                    while ($class = $resultClasses->fetch()) {
                        $classRow = $saf_periodSelectContainer->addRow();
                        $classRow->addCheckbox('courses[' . $i . ']')->setValue($class['gibbonCourseClassID']);
                        $classRow->addLabel('courses[' . $i . ']',$class['columnName'] . ' - ' . $class['courseName'] . '.' . $class['courseNameShort'])->addClass('classDescriptor');
                        $i++;
                    }

                    $saf_type = $setAttendanceForm->addRow();
                    $saf_type->addLabel('type','Type');
                    $saf_type->addSelect('type')->fromArray(array_keys($attendance->getAttendanceTypes()))->isRequired();

                    $saf_reason = $setAttendanceForm->addRow();
                    $saf_reason->addLabel('reason','Reason');
                    $saf_reason->addSelect('reason')->fromArray($attendance->getAttendanceReasons());

                    $saf_comment = $setAttendanceForm->addRow();
                    $saf_comment->addLabel('comment','Comment')->description('255 character limit');
                    $saf_comment->addTextArea('comment')->setRows(3)->maxLength(255);

                    $setAttendanceForm->addRow()->addSubmit();
                    echo $attendanceLog;
                    echo $setAttendanceForm->getOutput();

                }
                else
                {
                    echo "<div class='error'>" . __($guid, 'Cannot record a partial absense. This student does not have timetabled classes for this day.') . '</div>';
                }
                break;

        }
    }
}
?>

<script type='text/javascript'>
    document.getElementsByClassName('classTableHeader')[0].colSpan = "2";
</script>
