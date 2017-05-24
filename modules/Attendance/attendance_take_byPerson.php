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

require_once $_SESSION[$guid]['absolutePath'].'/modules/Attendance/src/attendanceView.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Take Attendance by Person').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because the specified date is not in the future, or is not a school day.'));
    }

    $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

    $gibbonPersonID = null;
    if (isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }

    if (!(isset($_GET['currentDate']))) {
        $currentDate = date('Y-m-d');
    } else {
        $currentDate = dateConvert($guid, $_GET['currentDate']);
    }

    $today = date('Y-m-d');

    ?>

	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<tr class='break'>
				<td colspan=2>
					<h3>
						<?php echo __($guid, 'Choose Student') ?>
					</h3>
				</td
			</tr>
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Student') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonPersonID">
						<?php
                        echo "<option value=''></option>";
						try {
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $currentDate);
							$sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) ORDER BY surname, preferredName";
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
							echo "<div class='error'>".$e->getMessage().'</div>';
						}

						while ($rowSelect = $resultSelect->fetch()) {
							if ($gibbonPersonID == $rowSelect['gibbonPersonID']) {
								echo "<option selected value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
							} else {
								echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Student', true).' ('.htmlPrep($rowSelect['nameShort']).')</option>';
							}
						}
						?>
					</select>
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
					<input name="currentDate" id="currentDate" maxlength=10 value="<?php echo dateConvertBack($guid, $currentDate) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var currentDate=new LiveValidation('currentDate');
						currentDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
						currentDate.add(Validate.Presence);
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#currentDate" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/attendance_take_byPerson.php">
					<input type="submit" value="Search">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($gibbonPersonID != '') {
        if ($currentDate > $today) {
            echo "<div class='error'>";
            echo __($guid, 'The specified date is in the future: it must be today or earlier.');
            echo '</div>';
        } else {
            if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                echo "<div class='error'>";
                echo 'School is closed on the specified date, and so attendance information cannot be recorded.';
                echo '</div>';
            } else {
                $prefillAttendanceType = getSettingByScope($connection2, 'Attendance', 'prefillPerson');

                //Get last 5 school days from currentDate within the last 100
                $timestamp = dateConvertToTimestamp($currentDate);

                $lastType = '';
                $lastReason = '';
                $lastComment = '';

                //Show attendance log for the current day
			    try {
			        $dataLog = array('gibbonPersonID' => $gibbonPersonID, 'date' => "$currentDate%");
			        $sqlLog = 'SELECT gibbonAttendanceLogPersonID, direction, type, reason, context, comment, timestampTaken, gibbonAttendanceLogPerson.gibbonCourseClassID, preferredName, surname, gibbonCourseClass.nameShort as className, gibbonCourse.nameShort as courseName FROM gibbonAttendanceLogPerson JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE  gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID';
			        $resultLog = $connection2->prepare($sqlLog);
			        $resultLog->execute($dataLog);
			    } catch (PDOException $e) {
			        echo "<div class='error'>".$e->getMessage().'</div>';
			    }
			    if ($resultLog->rowCount() < 1) {
			        echo "<div class='error'>";
			        echo __($guid, 'There is currently no attendance data today for the selected student.');
			        echo '</div>';
			    } else {
			    	echo '<h4>';
			    		echo __($guid, 'Attendance Log');
			    	echo '</h4>';

			        echo "<p><span class='emphasis small'>";
			        	echo __($guid, 'The following attendance log has been recorded for the selected student today:');
			        echo '</span></p>';

			        echo '<table class="mini smallIntBorder fullWidth colorOddEven" cellspacing=0>';
			        echo '<tr class="head">';
			        	echo '<th>'.__($guid, 'Time').'</th>';
			        	echo '<th>'.__($guid, 'Attendance').'</th>';
			        	echo '<th>'.__($guid, 'Where').'</th>';
			        	echo '<th>'.__($guid, 'Recorded By').'</th>';

			        	if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == true) {
			        		echo '<th style="width: 50px;">'.__($guid, 'Actions').'</th>';
			        	}
			        echo '</tr>';
			        while ($rowLog = $resultLog->fetch()) {
			        	$logTimestamp = strtotime($rowLog['timestampTaken']);

			            echo '<tr class="'.( $rowLog['direction'] == 'Out'? 'error' : 'current').'">';

			            if (  $currentDate != substr($rowLog['timestampTaken'], 0, 10) ) {
			            	echo '<td>'.date("g:i a, M j", $logTimestamp ).'</td>';
			            } else {
							echo '<td>'.date("g:i a", $logTimestamp ).'</td>';
						}

						echo '<td>';
			            echo '<b>'.$rowLog['direction'].'</b> ('.$rowLog['type']. ( !empty($rowLog['reason'])? ', '.$rowLog['reason'] : '') .')';

			            if ( !empty($rowLog['comment']) ) {
			            	echo '&nbsp;<img title="'.$rowLog['comment'].'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/messageWall.png" width=16 height=16/>';
			        	}
			            echo '</td>';


                        if ($rowLog['context'] != '') {
                            if ($rowLog['context'] == 'Class' && $rowLog['gibbonCourseClassID'] > 0)
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

			            if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson_edit.php') == true) {
			            	echo '<td>';
					            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson_edit.php&gibbonAttendanceLogPersonID='.$rowLog['gibbonAttendanceLogPersonID']."&gibbonPersonID=$gibbonPersonID&currentDate=$currentDate'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
				            echo '</td>';
			            }


			            $lastType = ($prefillAttendanceType == 'Y')? $rowLog['type'] : '';
			            $lastReason = ($prefillAttendanceType == 'Y')? $rowLog['reason'] : '';
			            $lastComment = ($prefillAttendanceType == 'Y')? $rowLog['comment'] : '';
			            echo '</tr>';
			        }
			        echo '</table><br/>';
			    }

                //Show student form
                echo "<script type='text/javascript'>
					function dateCheck() {
						var date = new Date();
						if ('".$currentDate."'<getDate()) {
							return confirm(\"".__($guid, 'The selected date for attendance is in the past. Are you sure you want to continue?').'")
						}
					}
				</script>'; ?>

				<form autocomplete="off" onsubmit="return dateCheck()" method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/attendance_take_byPersonProcess.php?gibbonPersonID=$gibbonPersonID" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr class='break'>
							<td colspan=2>
								<h3>
									<?php echo __($guid, 'Take Attendance') ?>
								</h3>
							</td
						</tr>
						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Recent Attendance Summary') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php $attendance->renderMiniHistory( $gibbonPersonID, '160px; float:right;' ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Type') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php echo $attendance->renderAttendanceTypeSelect($lastType); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Reason') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php echo $attendance->renderAttendanceReasonSelect($lastReason); ?>
							</td>
						</tr>
						<tr>
							<td>
								<b><?php echo __($guid, 'Comment') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, '255 character limit') ?></span>
							</td>
							<td class="right">
								<?php
                                echo "<textarea name='comment' id='comment' rows=3 style='width: 300px'>$lastComment</textarea>"; ?>
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
								<?php echo "<input type='hidden' name='currentDate' value='$currentDate'>"; ?>
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
