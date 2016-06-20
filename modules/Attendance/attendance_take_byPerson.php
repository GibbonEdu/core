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
							$dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
							$sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
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
                //Get last 5 school days from currentDate within the last 100
                $timestamp = dateConvertToTimestamp($currentDate);
                $count = 0;
                $spin = 1;
                $last5SchoolDays = array();
                while ($count < 5 and $spin <= 100) {
                    $date = date('Y-m-d', ($timestamp - ($spin * 86400)));
                    if (isSchoolOpen($guid, $date, $connection2)) {
                        $last5SchoolDays[$count] = $date;
                        ++$count;
                    }
                    ++$spin;
                }
                $last5SchoolDaysCount = $count;

                $lastType = '';
                $lastReason = '';
                $lastComment = '';

                //Show attendance log for the current day
                try {
                    $dataLog = array('gibbonPersonID' => $gibbonPersonID, 'date' => "$currentDate%");
                    $sqlLog = 'SELECT * FROM gibbonAttendanceLogPerson, gibbonPerson WHERE gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID';
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
                    echo "<div class='success'>";
                    echo __($guid, 'The following attendance log has been recorded for the selected student today:');
                    echo '<ul>';
                    while ($rowLog = $resultLog->fetch()) {
                        echo '<li><b>'.$rowLog['direction'].'</b> ('.$rowLog['type'].') | '.sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($rowLog['timestampTaken'], 11), dateConvertBack($guid, substr($rowLog['timestampTaken'], 0, 10)), formatName('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true)).'</li>';
                        $lastType = $rowLog['type'];
                        $lastReason = $rowLog['reason'];
                        $lastComment = $rowLog['comment'];
                    }
                    echo '</ul>';
                    echo '</div>';
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
				
				<form onsubmit="return dateCheck()" method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/attendance_take_byPersonProcess.php?gibbonPersonID=$gibbonPersonID" ?>">
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
								<?php
                                echo "<table cellspacing='0' style='float: right; width:134px; margin: 0px 0px 0px 8px; height: 35px' >";
								echo '<tr>';
								for ($i = 4; $i >= 0; --$i) {
									$link = '';
									if ($i > ($last5SchoolDaysCount - 1)) {
										$extraStyle = 'background-color: #eee;';

										echo "<td style='".$extraStyle."height: 25px; width: 20%'>";
										echo '<i>'.__($guid, 'NA').'</i>';
										echo '</td>';
									} else {
										try {
											$dataLast5SchoolDays = array('gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d', dateConvertToTimestamp($last5SchoolDays[$i])).'%');
											$sqlLast5SchoolDays = 'SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC';
											$resultLast5SchoolDays = $connection2->prepare($sqlLast5SchoolDays);
											$resultLast5SchoolDays->execute($dataLast5SchoolDays);
										} catch (PDOException $e) {
											echo "<div class='error'>".$e->getMessage().'</div>';
										}

										if ($resultLast5SchoolDays->rowCount() == 0) {
											$extraStyle = 'color: #555; background-color: #eee; ';
										} else {
											$link = './index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendance_take_byPerson.php&gibbonPersonID='.$gibbonPersonID.'&currentDate='.date('d/m/Y', dateConvertToTimestamp($last5SchoolDays[$i]));
											$rowLast5SchoolDays = $resultLast5SchoolDays->fetch();
											if ($rowLast5SchoolDays['type'] == 'Absent') {
												$color = '#c00';
												$extraStyle = 'color: #c00; background-color: #F6CECB; ';
											} else {
												$color = '#390';
												$extraStyle = 'color: #390; background-color: #D4F6DC; ';
											}
										}

										echo "<td style='".$extraStyle."height: 25px; width: 20%'>";
										if ($link != '') {
											echo "<a style='text-decoration: none; color: $color' href='$link'>";
											echo date('d', dateConvertToTimestamp($last5SchoolDays[$i])).'<br/>';
											echo "<span style='font-size: 65%'>".date('M', dateConvertToTimestamp($last5SchoolDays[$i])).'</span>';
											echo '</a>';
										} else {
											echo date('d', dateConvertToTimestamp($last5SchoolDays[$i])).'<br/>';
											echo "<span style='font-size: 65%'>".date('M', dateConvertToTimestamp($last5SchoolDays[$i])).'</span>';
										}
										echo '</td>';
									}
								}
								echo '</tr>';
								echo '</table>';
								?>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Type') ?> *</b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php renderAttendanceTypeSelect($guid); ?>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Reason') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php renderAttendanceReasonSelect($guid); ?>
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