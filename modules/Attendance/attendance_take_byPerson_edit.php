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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

require_once './modules/Attendance/src/attendanceView.php';


if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {

	$gibbonAttendanceLogPersonID = isset($_GET['gibbonAttendanceLogPersonID'])? $_GET['gibbonAttendanceLogPersonID'] : '';
	$gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';
	

	if ( empty($gibbonAttendanceLogPersonID) || empty($gibbonPersonID) ) {

		echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';

	} else {
	    //Proceed!
	    echo "<div class='trail'>";
	    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Attendance by Person').'</div>';
	    echo '</div>';

	    if (isset($_GET['return'])) {
	        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because the specified date is not in the future, or is not a school day.'));
	    }

	    $attendance = new Module\Attendance\attendanceView(NULL, NULL, $pdo);

	    $today = date('Y-m-d');

	    try {
			$dataPerson = array('gibbonPersonID' => $gibbonPersonID, 'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID );
			$sqlPerson = "SELECT p.preferredName, p.surname, type, reason, comment, date, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, t.preferredName as teacherPreferredName, t.surname as teacherSurname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson p ON (gibbonAttendanceLogPerson.gibbonPersonID=p.gibbonPersonID) JOIN gibbonPerson t ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=t.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND gibbonAttendanceLogPersonID=:gibbonAttendanceLogPersonID ";
			$resultPerson = $connection2->prepare($sqlPerson);
			$resultPerson->execute($dataPerson);
		} catch (PDOException $e) {
			echo "<div class='error'>".$e->getMessage().'</div>';
		}

	    if ($resultPerson->rowCount() != 1) {
	    	echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
	    } else {
	    	$row = $resultPerson->fetch();

	    	$currentDate = dateConvert($guid, $row['date']);

            if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                echo "<div class='error'>";
                echo 'School is closed on the specified date, and so attendance information cannot be recorded.';
                echo '</div>';
            } else {
                $timestamp = dateConvertToTimestamp($currentDate);
               
                ?>
				
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson_editProcess.php'; ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr class='break'>
							<td colspan=2>
								<h3>
									<?php echo __($guid, 'Edit Attendance') ?>
								</h3>
							</td
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Student') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input readonly name="student" id="student" value="<?php echo formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true); ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Date') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
								if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?></span>
							</td>
							<td class="right">
								<input readonly name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, $currentDate) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var date=new LiveValidation('date');
									date.add(Validate.Presence);
									date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Recorded By') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input readonly name="teacher" id="teacher" value="<?php echo formatName('', htmlPrep($row['teacherPreferredName']), htmlPrep($row['teacherSurname']), 'Staff', false, true ); ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Time') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input readonly name="teacher" id="teacher" value="<? echo substr($row["timestampTaken"],11).' '.dateConvertBack($guid, substr($row["timestampTaken"],0,10)); ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Where') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<input readonly name="teacher" id="teacher" value="<? echo ($row['gibbonCourseClassID'] == 0)? __($guid, 'Roll Group') : $row['courseName'].'.'.$row['className']; ?>" type="text" class="standardWidth">
							</td>
						</tr>

						<tr>
							<td> 
								<b><?php echo __($guid, 'Type') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php echo $attendance->renderAttendanceTypeSelect( $row['type'] ); ?>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Reason') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php echo $attendance->renderAttendanceReasonSelect( $row['reason'] ); ?>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Comment') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, '255 character limit') ?></span>
							</td>
							<td class="right">
								<?php
                                echo "<textarea name='comment' id='comment' rows=3 style='width: 300px'>".$row['comment']."</textarea>"; ?>
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
								<input type="hidden" name="gibbonAttendanceLogPersonID" value="<?php echo $gibbonAttendanceLogPersonID; ?>">
								<input type="hidden" name="gibbonPersonID" value="<?php echo $gibbonPersonID; ?>">
								<input type="hidden" name="currentDate" value="<?php echo $currentDate; ?>">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php


	            
	        }
	    }
	}
}
?>