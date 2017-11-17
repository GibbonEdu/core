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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byRollGroup.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Take Attendance by Roll Group').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('warning1' => 'Your request was successful, but some data was not properly saved.', 'error3' => 'Your request failed because the specified date is not in the future, or is not a school day.'));
        }

        $attendance = new Module\Attendance\attendanceView($gibbon, $pdo);

        $gibbonRollGroupID = '';
        if (isset($_GET['gibbonRollGroupID']) == false) {
            try {
                $data = array('gibbonPersonIDTutor1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonRollGroup.*, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonRollGroupID = $row['gibbonRollGroupID'];
            }
        } else {
            $gibbonRollGroupID = $_GET['gibbonRollGroupID'];
        }

        if (isset($_GET['currentDate']) == false) {
            $currentDate = date('Y-m-d');
        } else {
            $currentDate = dateConvert($guid, $_GET['currentDate']);
        }

        $today = date('Y-m-d'); ?>

    	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
    		<table class='smallIntBorder fullWidth' cellspacing='0'>
    			<tr class='break'>
    				<td colspan=2>
    					<h3>
    					<?php echo __($guid, 'Choose Roll Group') ?>
    					</h3>
    				</td>
    			</tr>
    			<tr>
    				<td style='width: 275px'>
    					<b><?php echo __($guid, 'Roll Group') ?></b><br/>
    					<span class="emphasis small"></span>
    				</td>
    				<td class="right">
    					<select class="standardWidth" name="gibbonRollGroupID">
    						<?php
                            echo "<option value=''></option>";
    						try {
                                if ($highestAction == 'Attendance By Roll Group_all') {
                                    $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        							$sqlSelect = "SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroup.attendance = 'Y' ORDER BY LENGTH(name), name";
                                }
                                else {
                                    $dataSelect = array('gibbonPersonIDTutor1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        							$sqlSelect = "SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.attendance = 'Y' ORDER BY LENGTH(name), name";
                                }
                                $resultSelect = $connection2->prepare($sqlSelect);
    							$resultSelect->execute($dataSelect);
    						} catch (PDOException $e) {
    							echo "<div class='error'>".$e->getMessage().'</div>';
    						}

    						while ($rowSelect = $resultSelect->fetch()) {
    							if ($gibbonRollGroupID == $rowSelect['gibbonRollGroupID']) {
    								echo "<option selected value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
    							} else {
    								echo "<option value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
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
    					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/attendance_take_byRollGroup.php">
    					<input type="submit" value="Search">
    				</td>
    			</tr>
    		</table>
    	</form>
    	<?php

        if ($gibbonRollGroupID != '') {
            if ($currentDate > $today) {
                echo "<div class='error'>";
                echo __($guid, 'The specified date is in the future: it must be today or earlier.');
                echo '</div>';
            } else {
                if (isSchoolOpen($guid, $currentDate, $connection2) == false) {
                    echo "<div class='error'>";
                    echo __($guid, 'School is closed on the specified date, and so attendance information cannot be recorded.');
                    echo '</div>';
                } else {
                    $prefillAttendanceType = getSettingByScope($connection2, 'Attendance', 'prefillRollGroup');
                    $defaultAttendanceType = getSettingByScope($connection2, 'Attendance', 'defaultRollGroupAttendanceType');

                    //Check roll group
                    $rollGroupFail = false;
                    $firstDay = null;
                    $lastDay = null;
                    try {
                        $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sql = 'SELECT gibbonRollGroup.*, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonRollGroupID=:gibbonRollGroupID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $rollGroupFail = true;
                    }
                    if ($result->rowCount() != 0) {
                        $row = $result->fetch();
                        $gibbonRollGroupID = $row['gibbonRollGroupID'];
                        $firstDay = $row['firstDay'];
                        $lastDay = $row['lastDay'];
                    }
                    if ($rollGroupFail) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    else if ($row["attendance"] == 'N') {
                        print "<div class='error'>" ;
                            print __("Attendance taking has been disabled for this roll group.") ;
                        print "</div>" ;
                    } else {

                        //Show attendance log for the current day
                        try {
                            $dataLog = array('gibbonRollGroupID' => $gibbonRollGroupID, 'date' => $currentDate.'%');
                            $sqlLog = 'SELECT * FROM gibbonAttendanceLogRollGroup, gibbonPerson WHERE gibbonAttendanceLogRollGroup.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonRollGroupID=:gibbonRollGroupID AND date LIKE :date ORDER BY timestampTaken';
                            $resultLog = $connection2->prepare($sqlLog);
                            $resultLog->execute($dataLog);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultLog->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.');
                            echo '</div>';
                        } else {
                            echo "<div class='success'>";
                            echo __($guid, 'Attendance has been taken at the following times for the specified date for this group:');
                            echo '<ul>';
                            while ($rowLog = $resultLog->fetch()) {
                                echo '<li>'.sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($rowLog['timestampTaken'], 11), dateConvertBack($guid, substr($rowLog['timestampTaken'], 0, 10)), formatName('', $rowLog['preferredName'], $rowLog['surname'], 'Staff', false, true)).'</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }

                        //Show roll group grid
                        try {
                            $dataRollGroup = array('gibbonRollGroupID' => $gibbonRollGroupID, 'date' => $currentDate);
                            $sqlRollGroup = "SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) ORDER BY rollOrder, surname, preferredName";
                            $resultRollGroup = $connection2->prepare($sqlRollGroup);
                            $resultRollGroup->execute($dataRollGroup);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultRollGroup->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            $count = 0;
                            $countPresent = 0;
                            $columns = 4;

                            echo "<script type='text/javascript'>
    							function dateCheck() {
    								var date = new Date();
    								if ('".$currentDate."'<getDate()) {
    									return confirm(\"".__($guid, 'The selected date for attendance is in the past. Are you sure you want to continue?').'")
    								}
    							}
    						</script>';

                            echo "<form autocomplete=\"off\" onsubmit=\"return dateCheck()\" method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/attendance_take_byRollGroupProcess.php'>";
                            echo "<table class='smallIntBorder' cellspacing='0' style='width:100%'>";
                            ?>
    							<tr class='break'>
    								<td colspan=<?php echo $columns ?>>
    									<h3>
    										<?php echo __($guid, 'Take Attendance') ?>
    									</h3>
    								</td>
    							</tr>
    							<?php
                                while ($rowRollGroup = $resultRollGroup->fetch()) {
                                    if ($count % $columns == 0) {
                                        echo '<tr>';
                                    }

                                    $rowLog = array('type' => $defaultAttendanceType, 'reason' => '', 'comment' => '', 'context' => '');

                                    try {
                                        $dataLog = array('gibbonPersonID' => $rowRollGroup['gibbonPersonID'], 'date' => $currentDate.'%');

                                        if ($prefillAttendanceType == 'Y') {
                                            //Get any student log data
                                            $sqlLog = "SELECT * FROM gibbonAttendanceLogPerson, gibbonPerson WHERE context <> 'Class' AND gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY timestampTaken DESC LIMIT 1";
                                        } else {
                                            //Get student log data by Roll Group context only
                                            $sqlLog = "SELECT * FROM gibbonAttendanceLogPerson, gibbonPerson WHERE context='Roll Group' AND gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY timestampTaken DESC LIMIT 1";
                                        }

                                        $resultLog = $connection2->prepare($sqlLog);
                                        $resultLog->execute($dataLog);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($resultLog && $resultLog->rowCount() > 0 ) {
                                        $rowLog = $resultLog->fetch();
                                    }

                                    if ( $attendance->isTypeAbsent($rowLog["type"]) ) {
                                        echo "<td style='border: 1px solid #CC0000!important; background: none; background-color: #F6CECB; width:20%; text-align: center; vertical-align: top'>";

                                    } else {
                                        echo "<td style='border: 1px solid #ffffff; width:20%; text-align: center; vertical-align: top'>";
                                    }

                                    //User photo
                                    echo getUserPhoto($guid, $rowRollGroup['image_240'], 75);

                                    echo "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowRollGroup['gibbonPersonID']."&subpage=School Attendance'>".formatName('', htmlPrep($rowRollGroup['preferredName']), htmlPrep($rowRollGroup['surname']), 'Student', true).'</a></b></div>';
                                    echo "<div style='font-size: 90%; font-style: italic; font-weight: normal'>";
                                    if ($firstDay != null and $lastDay != null) {
                                        $absenceCount = getAbsenceCount($guid, $rowRollGroup['gibbonPersonID'], $connection2, $firstDay, $lastDay);
                                        if ($absenceCount !== false) {
                                            if ($absenceCount == 1)
                                                echo sprintf(__($guid, '%1$s Day Absent'), $absenceCount);
                                            else
                                                echo sprintf(__($guid, '%1$s Days Absent'), $absenceCount);
                                        }

                                        // List partial absences
                                        if ( !empty($rowLog["gibbonCourseClassID"]) && $rowLog["gibbonCourseClassID"] != 0 && $attendance->isTypeAbsent($rowLog["type"]) ) {
                                            printf( '<br/>'.__($guid, '%s Classes Absent'), $resultLog->rowCount() );
                                        }
                                    }
                                    echo '</div><br/>';
                                    echo "<input type='hidden' name='$count-gibbonPersonID' value='".$rowRollGroup['gibbonPersonID']."'>";

                                    echo $attendance->renderAttendanceTypeSelect( $rowLog['type'], "$count-type", '130px');
                                    echo $attendance->renderAttendanceReasonSelect( $rowLog['reason'], "$count-reason", '130px');

                                    echo "<input type='text' maxlength=255 name='$count-comment' id='$count-comment' style='float: none; width:126px; margin-bottom: 3px' value='".htmlPrep($rowLog['comment'])."'>";

                                    if ( $attendance->isTypePresent($rowLog["type"]) ) {
                                        ++$countPresent;
                                    }

                                    $attendance->renderMiniHistory( $rowRollGroup['gibbonPersonID'] );

                                    echo '</td>';

                                    if ($count % $columns == ($columns - 1)) {
                                        echo '</tr>';
                                    }
                                    ++$count;
                                }

                            for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                                echo '<td></td>';
                            }

                            if ($count % $columns != 0) {
                                echo '</tr>';
                            }

                            echo '<tr>';
                            echo "<td class='right' colspan=5>";
                            echo "<div class='success'>";
                            echo '<b>'.__($guid, 'Total students:')." $count</b><br/>";
                            if ($resultLog->rowCount() >= 1) {
                                echo "<span title='".__($guid, 'e.g. Present or Present - Late')."'>".__($guid, 'Total students present in room:')." <b>$countPresent</b><br/>";
                                echo "<span title='".__($guid, 'e.g. not Present and not Present - Late')."'>".__($guid, 'Total students absent from room:').' <b>'.($count - $countPresent).'</b><br/>';
                            }
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';

                            // Drop-downs to change the whole group at once
                            echo "<td colspan=3>";
                                echo $attendance->renderAttendanceTypeSelect( '', "set-all-type", '110px');
                                echo $attendance->renderAttendanceReasonSelect( '', "set-all-reason", '110px');
                                echo "<input type='text' maxlength=255 name='set-all-comment' id='set-all-comment' style='float: none; width:100px; margin-bottom: 3px' value='".htmlPrep($rowLog['comment'])."'>";
                                echo "<input id='set-all' type='button' value='Change All'>";
                                echo "<span id='set-all-note' class='emphasis small' style='display:none;color:#CC0000;'>".__($guid, "* Submit to save changes.")."</span>";
                            echo "</td>";

                            echo "<td class='right' colspan=2>";
                                echo "<input type='hidden' name='gibbonRollGroupID' value='$gibbonRollGroupID'>";
                                echo "<input type='hidden' name='currentDate' value='$currentDate'>";
                                echo "<input type='hidden' name='count' value='".$resultRollGroup->rowCount()."'>";
                                echo "<input type='hidden' name='address' value='".$_SESSION[$guid]['address']."'>";
                                echo "<input type='submit' value='Submit'>";
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                            echo '</form>';
                        }
                    }
                }
            }
        }
    }
}
?>
