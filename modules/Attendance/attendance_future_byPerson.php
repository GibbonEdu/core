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

        //Display table for future attendance
        try {
            $dataLog = array('gibbonPersonID' => $gibbonPersonID, 'date' => "$today-0-0-0"); //"$today-23-59-59"
            $sqlLog = "SELECT gibbonAttendanceLogPersonID, date, direction, type, context, reason, comment, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, preferredName, surname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date>=:date ORDER BY date";
            $resultLog = $connection2->prepare($sqlLog);
            $resultLog->execute($dataLog);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        try {
            $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'date' => $dateSQL );
            $sqlClasses = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as classNameShort, gibbonTTColumnRow.name as columnName, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonTTDayDate.date=:date ORDER BY gibbonTTColumnRow.timeStart ASC";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        } catch (PDOException $e) {
            echo "<div class='error'>" . $e->getMessage() . '</div>';
        }

        switch($absenceType)
        {
            case "full":
                $setAttendanceForm = Form::create('attendanceSet',$_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_future_byPersonProcess.php?gibbonPersonID=' . $gibbonPersonID,'POST');
                $setAttendanceForm->addRow()->addHeading('SET FUTURE ATTENDANCE');
                $setAttendanceForm->addHiddenValue('q','/modules/'.$_SESSION[$guid]['module'].'/attendance_future_byPerson.php');
                $setAttendanceForm->addHiddenValue('absenceType','full');
                $setAttendanceForm->addHiddenValue('address','/modules/Attendance/attendance_future_byPerson.php');

                if ($resultLog->rowCount() > 0) {
                    echo '<h4>';
                        echo __($guid, 'Attendance Log');
                    echo '</h4>';

                    echo "<p><span class='emphasis small'>";
                        echo __($guid, 'The following future absences have been set for the selected student.');
                    echo '</span></p>';

                    echo '<table class="mini smallIntBorder fullWidth colorOddEven" cellspacing=0>';
                    echo '<tr class="head">';
                        echo '<th>'.__($guid, 'Date').'</th>';
                        echo '<th>'.__($guid, 'Attendance').'</th>';
                        echo '<th>'.__($guid, 'Where').'</th>';
                        echo '<th>'.__($guid, 'Recorded By').'</th>';
                        echo '<th>'.__($guid, 'On').'</th>';
                        echo '<th style="width: 50px;">'.__($guid, 'Actions').'</th>';

                    echo '</tr>';
                    while ($rowLog = $resultLog->fetch()) {

                        echo '<tr class="'.( $rowLog['direction'] == 'Out'? 'error' : 'current').'">';

                        echo '<td>'.date("M j", strtotime($rowLog['date']) ).'</td>';


                        echo '<td>';
                        echo '<b>'.$rowLog['direction'].'</b> ('.$rowLog['type']. ( !empty($rowLog['reason'])? ', '.$rowLog['reason'] : '') .')';

                        if ( !empty($rowLog['comment']) ) {
                            echo '&nbsp;<img title="'.$rowLog['comment'].'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/messageWall.png" width=16 height=16/>';
                        }
                        echo '</td>';


                        if ($rowLog['context'] != '') {
                            if (($rowLog['context'] == 'Future' || $rowLog['context'] == 'Class') && $rowLog['gibbonCourseClassID'] > 0)
                                echo '<td>'.__($guid, $rowLog['context']).' ('.$rowLog['courseName'].'.'.$rowLog['className'].')</td>';
                            else
                                echo '<td>'.__($guid, $rowLog['context']).'</td>';
                        }
                        else {
                            echo '<td>'.__($guid, 'Roll Group').'</td>';
                        }

                        echo '<td>';
                            echo formatName('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true);
                        echo '</td>';

                        echo '<td>'.date("g:i a, M j", strtotime($rowLog['timestampTaken']) ).'</td>';


                        echo '<td>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Attendance/attendance_future_byPersonDeleteProcess.php?gibbonPersonID=$gibbonPersonID&gibbonAttendanceLogPersonID=".$rowLog['gibbonAttendanceLogPersonID']."' onclick='confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                        echo '</td>';

                        echo '</tr>';
                    }
                    echo '</table><br/>';
                }
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

                echo $setAttendanceForm->getOutput();

                break;

            case "partial":
                if($resultClasses->rowCount() > 0)
                {
                    $setAttendanceForm = Form::create('attendanceSet',$_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/attendance_future_byPersonProcess.php?gibbonPersonID=' . $gibbonPersonID,'POST');
                    $setAttendanceForm->addRow()->addHeading('SET FUTURE ATTENDANCE');
                    $setAttendanceForm->addHiddenValue('q','/modules/'.$_SESSION[$guid]['module'].'/attendance_future_byPerson.php');
                    $setAttendanceForm->addHiddenValue('absenceType','full');
                    $setAttendanceForm->addHiddenValue('address','/modules/Attendance/attendance_future_byPerson.php');

                    $dateSQL = dateConvert($guid, $date);
                    $saf_periodSelect = $setAttendanceForm->addRow();
                    $saf_periodSelect->addLabel('periodSelectContainer','Periods Absent');
                    $saf_periodSelectContainer = $saf_periodSelect->addColumn('periodSelectContainer');

                    if ($resultClasses->rowCount() == 0) {
                        $periodSelectTable .=  "<div class='error'>".__($guid, 'Cannot record a partial absense. This student does not have timetabled classes for this day.').'</div>';
                    }
                    else
                    {
                        $periodSelectTable .=  '<h4 style="display:block;float:right;width:302px;">'. date('F j, Y', strtotime($dateSQL) ).'</h4>';
                        $periodSelectTable .=  '<table width="302" style="float:right;">';
                        if ($resultClasses->rowCount() > 0) {
                            $i = 0;
                            while ($class = $resultClasses->fetch()) {
                                $periodSelectTable .=  '<tr><td style="line-height:24px;">';
                                printf('<input type="checkbox" name="courses[%s]" value="%s" />&nbsp;  <span title="%s">%s - %s.%s</span>', $i, $class['gibbonCourseClassID'], $class['courseName'], $class['columnName'], $class['courseNameShort'], $class['classNameShort']);
                                $periodSelectTable .=  '</td></tr>';
                                $i++;
                            }
                        }
                        $periodSelectTable .=  '</table>';

                    }
                    $saf_periodSelect = $setAttendanceForm->addRow()->addClass('error');
                    $saf_periodSelect->addLabel('periodSelectContainerainer','Periods Absent');
                    $saf_periodSelectTable = $saf_periodSelect->addColumn('periodSelectContainer')->setContent($periodSelectTable);
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

                    echo $setAttendanceForm->getOutput();

                }
                else
                {
                    echo "<div class='error'>" . __($guid, 'Cannot record a partial absense. This student does not have timetabled classes for this day.') . '</div>';
                }
                break;

        }

        


 

    }

    ?>

	<?php

/*
    if ($gibbonPersonID != '') {
        $today = date('Y-m-d');

        //Show attendance log for future days

        




        // Get timetabled classes for this student
        if ($absenceType == 'partial') {
			$dateSQL = dateConvert($guid, $date);

	        try {
	            $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'date' => $dateSQL );
	            $sqlClasses = "SELECT DISTINCT gibbonTT.gibbonTTID, gibbonTT.name, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as classNameShort, gibbonTTColumnRow.name as columnName, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)  JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonTTDayDate.date=:date ORDER BY gibbonTTColumnRow.timeStart ASC";
	            $resultClasses = $connection2->prepare($sqlClasses);
	            $resultClasses->execute($dataClasses);
	        } catch (PDOException $e) {
	            $output .= "<div class='error'>".$e->getMessage().'</div>';
	        }

	        if ($resultClasses->rowCount() == 0) {
	        	echo "<div class='error'>".__($guid, 'Cannot record a partial absense. This student does not have timetabled classes for this day.').'</div>';
	        	return;
	        }
	    }


        //Show student form

        ?>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/attendance_future_byPersonProcess.php?gibbonPersonID=$gibbonPersonID" ?>">
			<table id="absenceDetailsRow" class='smallIntBorder fullWidth' cellspacing='0'>
				<tr class='break'>
					<td colspan=2>
						<h3>
							<?php echo __($guid, 'Set Future Attendance') ?>
						</h3>
					</td>
				</tr>

				<?php
				// Full-day Absenses
				if ($absenceType=="full") : ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Start Date') ?> *</b><br/>
						<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
					</td>
					<td class="right">
						<input name="dateStart" id="dateStart" maxlength=10 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var dateStart=new LiveValidation('dateStart');
							dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
							}
									?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?>." } );
						 	dateStart.add(Validate.Presence);
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#dateStart" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'End Date') ?></b><br/>
						<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
					</td>
					<td class="right">
						<input name="dateEnd" id="dateEnd" maxlength=10 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var dateEnd=new LiveValidation('dateEnd');
							dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
							}
									?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
								echo 'dd/mm/yyyy';
							} else {
								echo $_SESSION[$guid]['i18n']['dateFormat'];
							}
							?>." } );
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#dateEnd" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<?php

				endif;

				// Partial Absenses
				if ($absenceType=="partial") : ?>
				<input type="hidden" name="dateStart" id="dateStart" maxlength=10 value="<?php echo $date; ?>" class="standardWidth">
				<input type="hidden" name="dateEnd" id="dateEnd" maxlength=10 value="<?php echo $date; ?>" class="standardWidth">
				<tr>
					<td>
						<b><?php echo __($guid, 'Periods Absent') ?></b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<?php
							// Display table of Periods & Courses
							echo '<h4 style="display:block;float:right;width:302px;">'. date('F j, Y', strtotime($dateSQL) ).'</h4>';
					        echo '<table width="302" style="float:right;">';
					        if ($resultClasses->rowCount() > 0) {
					        	$i = 0;
					        	while ($class = $resultClasses->fetch()) {
					        		echo '<tr><td style="line-height:24px;">';
					        		printf('<input type="checkbox" name="courses[%s]" value="%s" />&nbsp;  <span title="%s">%s - %s.%s</span>', $i, $class['gibbonCourseClassID'], $class['courseName'], $class['columnName'], $class['courseNameShort'], $class['classNameShort']);
					        		echo '</td></tr>';
					        		$i++;
					        	}
					        }
					        echo '</table>';
						?>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<td style='width: 275px'>
						<b><?php echo __($guid, 'Type') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<?php echo $attendance->renderAttendanceTypeSelect('Absent', 'type', '302px', true); ?>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Reason') ?></b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<?php echo $attendance->renderAttendanceReasonSelect(); ?>
					</td>
				</tr>
				<tr>
					<td>
						<b><?php echo __($guid, 'Comment') ?></b><br/>
						<span class="emphasis small"><?php echo __($guid, '255 character limit') ?></span>
					</td>
					<td class="right">
						<?php
                        echo "<textarea name='comment' id='comment' rows=3 style='width: 300px'></textarea>";
        				?>
						<script type="text/javascript">
							var comment=new LiveValidation('comment');
							comment.add( Validate.Length, { maximum: 255 } );
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
					</td>
					<td class="right">
						<input type="hidden" name="absenceType" value="<?php echo $absenceType; ?>">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

    }*/
}
?>
