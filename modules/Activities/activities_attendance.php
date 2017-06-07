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

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_attendance.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Enter Activity Attendance').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __($guid, 'Choose Activity');
    echo '</h2>';

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_attendance.php', $connection2);
    $gibbonActivityID = null;
    if (isset($_GET['gibbonActivityID'])) {
        $gibbonActivityID = $_GET['gibbonActivityID'];
    }
    ?>
	
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Activity')  ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonActivityID">
						<?php
                        echo "<option value=''></option>";
						try {
                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlSelect = "";
                            if($highestAction == "Enter Activity Attendance") {
    							$sqlSelect = "SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart";
                            } elseif($highestAction == "Enter Activity Attendance_leader") {
                                $dataSelect["gibbonPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
                                $sqlSelect = "SELECT gibbonActivity.gibbonActivityID, name, programStart FROM gibbonActivityStaff JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID = gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND (gibbonActivityStaff.role='Organiser' OR gibbonActivityStaff.role='Assistant' OR gibbonActivityStaff.role='Coach') ORDER BY name, programStart";
                            }
							$resultSelect = $connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						} catch (PDOException $e) {
						}
						while ($rowSelect = $resultSelect->fetch()) {
							$selected = '';
							if ($gibbonActivityID == $rowSelect['gibbonActivityID']) {
								$selected = 'selected';
							}
							echo "<option $selected value='".$rowSelect['gibbonActivityID']."'>".htmlPrep($rowSelect['name']).'</option>';
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/activities_attendance.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    // Cancel out early if we have no gibbonActivityID
    if (empty($gibbonActivityID)) {
        return;
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
        $studentResult = $connection2->prepare($sql);
        $studentResult->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    try {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT gibbonSchoolYearTermIDList, maxParticipants, programStart, programEnd, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID";
        $activityResult = $connection2->prepare($sql);
        $activityResult->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($studentResult->rowCount() < 1 || $activityResult->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';

        return;
    }

    try {
        $data = array('gibbonActivityID' => $gibbonActivityID);
        $sql = 'SELECT gibbonActivityAttendance.date, gibbonActivityAttendance.timestampTaken, gibbonActivityAttendance.attendance, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonActivityAttendance, gibbonPerson WHERE gibbonActivityAttendance.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonActivityAttendance.gibbonActivityID=:gibbonActivityID';
        $attendanceResult = $connection2->prepare($sql);
        $attendanceResult->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    // Gather the existing attendance data (by date and not index, should the time slots change)
    $sessionAttendanceData = array();

    while ($attendance = $attendanceResult->fetch()) {
        $sessionAttendanceData[ $attendance['date'] ] = array(
            'data' => (!empty($attendance['attendance'])) ? unserialize($attendance['attendance']) : array(),
            'info' => sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($attendance['timestampTaken'], 11), dateConvertBack($guid, substr($attendance['timestampTaken'], 0, 10)), formatName('', $attendance['preferredName'], $attendance['surname'], 'Staff', false, true)),
        );
    }

    $activity = $activityResult->fetch();
    $activity['participants'] = $studentResult->rowCount();

    // Get the week days that match time slots for this activity
    $activityWeekDays = getActivityWeekdays($connection2, $gibbonActivityID);

    // Get the start and end date of the activity, depending on which dateType we're using
    $activityTimespan = getActivityTimespan($connection2, $gibbonActivityID, $activity['gibbonSchoolYearTermIDList']);

    // Use the start and end date of the activity, along with time slots, to get the activity sessions
    $activitySessions = getActivitySessions($activityWeekDays, $activityTimespan, $sessionAttendanceData);

    echo '<h2>';
    echo __($guid, 'Activity');
    echo '</h2>';

    echo "<table class='smallIntBorder' style='width: 100%' cellspacing='0'><tbody>";
    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__($guid, 'Start Date').'</span><br>';
    if (!empty($activityTimespan['start'])) {
        echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], $activityTimespan['start']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    echo "<span class='infoTitle'>".__($guid, 'End Date').'</span><br>';
    if (!empty($activityTimespan['end'])) {
        echo date($_SESSION[$guid]['i18n']['dateFormatPHP'], $activityTimespan['end']);
    }
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle' title=''>%s</span><br>%s", __($guid, 'Number of Sessions'), count($activitySessions));
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle'>%s</span><br>%s", __($guid, 'Participants'), $activity['participants']);
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle'>%s</span><br>%s", __($guid, 'Maximum Participants'), $activity['maxParticipants']);
    echo '</td>';

    echo "<td style='width: 33%; vertical-align: top'>";
    printf("<span class='infoTitle' title=''>%s</span><br>%s", __($guid, 'Waiting'), $activity['waiting']);
    echo '</td>';
    echo '</tr>';
    echo '</tbody></table>';

    echo '<h2>';
    echo __($guid, 'Attendance');
    echo '</h2>';

    // Handle activities with no time slots or start/end, but don't return because there can still be previous records
    if (empty($activityWeekDays) || empty($activityTimespan)) {
        echo "<div class='error'>";
        echo __($guid, 'There are no time slots assigned to this activity, or the start and end dates are invalid. New attendance values cannot be entered until the time slots and dates are added.');
        echo '</div>';
    }

    if (count($activitySessions) <= 0) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendanceExport.php')) {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/report_attendanceExport.php?gibbonActivityID='.$gibbonActivityID."'>".__($guid, 'Export to Excel')."<img style='margin-left: 5px' title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
            echo '</div>';
        }

        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/activities_attendanceProcess.php?gibbonActivityID=$gibbonActivityID' >";

        echo "<div class='doublescroll-wrapper'>";
        echo "<table class='mini' cellspacing='0' style='width:100%; border: 0; margin:0;'>";
        echo "<tr class='head' style='height:60px; '>";
        echo "<th style='width:175px;'>";
        echo __($guid, 'Student');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Attendance');
        echo '</th>';
        echo "<th class='emphasis subdued' style='text-align:right'>";
        printf(__($guid, 'Sessions Recorded: %s of %s'), count($sessionAttendanceData), count($activitySessions));
        echo '</th>';
        echo '</tr>';
        echo '</table>';

        echo "<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>";

        $columnCount = count($activitySessions);

        echo "<div class='doublescroll-container'>";
        echo "<table class='mini colorOddEven' cellspacing='0' style='width: ".($columnCount * 56)."px'>";

        echo "<tr style='height: 75px'>";
        echo "<td style='vertical-align:top;height:75px;'>".__($guid, 'Date').'</td>';

        $i = 0;
        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            if (isset($sessionAttendanceData[$sessionDate]['data'])) {
                // Handle instances where the time slot has been deleted after creating an attendance record
                        if (!in_array(date('D', $sessionTimestamp), $activityWeekDays) || ($sessionTimestamp < $activityTimespan['start']) || ($sessionTimestamp > $activityTimespan['end'])) {
                            echo "<td style='vertical-align:top; width: 45px;' class='warning' title='".__($guid, 'Does not match the time slots for this activity.')."'>";
                        } else {
                            echo "<td style='vertical-align:top; width: 45px;'>";
                        }

                printf("<span title='%s'>%s</span><br/>&nbsp;<br/>", $sessionAttendanceData[$sessionDate]['info'], date('D<\b\r>M j', $sessionTimestamp));
                echo "<a class='editColumn' data-checked='' data-column='$i' data-date='".$sessionTimestamp."'><img style='margin-top: 3px' title='"._('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            } else {
                echo "<td style='color: #bbb; vertical-align:top; width: 45px;'>";
                echo date('D<\b\r>M j', $sessionTimestamp).'<br/>&nbsp;<br/>';
                echo "<a class='editColumn' data-checked='checked' data-column='$i' data-date='".$sessionTimestamp."'><img style='margin-top: 3px' title='"._('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> ";
            }
            echo "<a class='clearColumn hidden' data-column='$i'><img style='margin-top: 3px' title='"._('Clear')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            ++$i;
        }

        echo '</tr>';

        $count = 0;
        // Build an empty array of attendance count data for each session
        $attendanceCount = array_combine(array_keys($activitySessions), array_fill(0, count($activitySessions), 0));

        while ($row = $studentResult->fetch()) {
            ++$count;
            $student = $row['gibbonPersonID'];

            echo "<tr data-student='$student'>";
            echo '<td>';
            echo $count.'. '.formatName('', $row['preferredName'], $row['surname'], 'Student', true);
            echo '</td>';

            $i = 0;
            foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
                echo "<td class='col$i'>";
                if (isset($sessionAttendanceData[$sessionDate]['data'])) {
                    if (isset($sessionAttendanceData[$sessionDate]['data'][$student])) {
                        echo '✓';
                        $attendanceCount[$sessionDate]++;
                    }
                }
                echo '</td>';
                ++$i;
            }

            echo '</tr>';

            $lastPerson = $row['gibbonPersonID'];
        }

            // Output a total attendance per column
            echo '<tr>';
        echo "<td class='right'>";
        echo __($guid, 'Total students:');
        echo '</td>';

        foreach ($activitySessions as $sessionDate => $sessionTimestamp) {
            echo '<td>';
            if (!empty($attendanceCount[$sessionDate])) {
                echo $attendanceCount[$sessionDate].' / '.$activity['participants'];
            }
            echo '</td>';
        }

        echo '</tr>';

        if ($count == 0) {
            echo "<tr class=$rowNum>";
            echo '<td colspan=16>';
            echo __($guid, 'There are no records to display.');
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</div>';
        echo '</div>'; ?>
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td>
						<em><?php echo __($guid, 'All highlighted columns will be updated when you press submit.'); ?></em>
					</td>
					<td class="right">
						<input name="gibbonPersonID" id="gibbonPersonID" value="<?php echo $_SESSION[$guid]['gibbonPersonID']; ?>" type="hidden">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
					</td>
				</tr>
			</table>

		</form>
		<?php
        echo '<br/>';
    }
}

?>
