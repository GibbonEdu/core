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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/report_attendance.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Attendance by Activity') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }

	print "<h2>" ;
	print __($guid, "Choose Activity") ;
	print "</h2>" ;
	
	$gibbonActivityID=NULL ;
	if (isset($_GET["gibbonActivityID"])) {
		$gibbonActivityID=$_GET["gibbonActivityID"] ;
	}
	$allColumns= (isset($_GET['allColumns']))? $_GET['allColumns'] : false;
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Activity')  ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select class="standardWidth" name="gibbonActivityID">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($gibbonActivityID==$rowSelect["gibbonActivityID"]) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowSelect["gibbonActivityID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'All Columns')  ?></b><br>
					<span class="emphasis small"><?php print __($guid, 'Include empty columns with unrecorded attendance.')  ?></span>
				</td>
				<td class="right">
					<input name="allColumns" id="allColumns" type="checkbox" <?php if ($allColumns) echo "checked";  ?> >
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_attendance.php">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	// Cancel out early if we have no gibbonActivityID
	if ( empty($gibbonActivityID) ) return;


	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$gibbonActivityID); 
		$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.status='Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName" ;
		$studentResult=$connection2->prepare($sql);
		$studentResult->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	try {
		$data=array("gibbonActivityID"=>$gibbonActivityID); 
		$sql="SELECT gibbonSchoolYearTermIDList, maxParticipants, programStart, programEnd, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID" ;
		$activityResult=$connection2->prepare($sql);
		$activityResult->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	

	if ($studentResult->rowCount()<1 || $activityResult->rowCount()<1) {
		print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
		return;
	}

		
	try {
		$data=array("gibbonActivityID"=>$gibbonActivityID ); 
		$sql="SELECT gibbonActivityAttendance.date, gibbonActivityAttendance.timestampTaken, gibbonActivityAttendance.attendance, gibbonPerson.preferredName, gibbonPerson.surname FROM gibbonActivityAttendance, gibbonPerson WHERE gibbonActivityAttendance.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonActivityAttendance.gibbonActivityID=:gibbonActivityID" ;
		$attendanceResult=$connection2->prepare($sql);
		$attendanceResult->execute($data); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}


	// Gather the existing attendance data (by date and not index, should the time slots change)
	$sessionAttendanceData = array();

	while ($attendance=$attendanceResult->fetch()) {
		$sessionAttendanceData[ $attendance['date'] ] = array(
			'data'	=> (!empty($attendance['attendance']))? unserialize($attendance['attendance']) : array(),
			'info'	=> sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($attendance["timestampTaken"],11), dateConvertBack($guid, substr($attendance["timestampTaken"],0,10)), formatName("", $attendance["preferredName"], $attendance["surname"], "Staff", false, true))
		);
	}

	$activity=$activityResult->fetch();
	$activity['participants'] = $studentResult->rowCount();

	// Get the week days that match time slots for this activity
	$activityWeekDays = getActivityWeekdays($connection2, $gibbonActivityID);

	// Get the start and end date of the activity, depending on which dateType we're using
	$activityTimespan = getActivityTimespan( $connection2, $gibbonActivityID, $activity['gibbonSchoolYearTermIDList']);

	// Use the start and end date of the activity, along with time slots, to get the activity sessions
	$activitySessions = getActivitySessions( ($allColumns)? $activityWeekDays : array(), $activityTimespan, $sessionAttendanceData);


	print "<h2>" ;
	print __($guid, "Activity") ;
	print "</h2>" ;

	print "<table class='smallIntBorder' style='width: 100%' cellspacing='0'><tbody>";
		print "<tr>";
			print "<td style='width: 33%; vertical-align: top'>";
				print "<span class='infoTitle'>". __($guid, 'Start Date') ."</span><br>";
				if (!empty($activityTimespan['start'])) {
					print date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $activityTimespan['start'] );
				}
			print "</td>";

			print "<td style='width: 33%; vertical-align: top'>";
			print "<span class='infoTitle'>". __($guid, 'End Date') ."</span><br>";
				if (!empty($activityTimespan['end'])) {
					print date($_SESSION[$guid]["i18n"]["dateFormatPHP"], $activityTimespan['end'] );
				}
			print "</td>";

			print "<td style='width: 33%; vertical-align: top'>";
				printf ("<span class='infoTitle' title=''>%s</span><br>%s", __($guid, 'Number of Sessions'), count($activitySessions) );
			print "</td>";
		print "</tr>";

		print "<tr>";
			print "<td style='width: 33%; vertical-align: top'>";
				printf ("<span class='infoTitle'>%s</span><br>%s", __($guid, 'Participants'), $activity['participants'] );
			print "</td>";

			print "<td style='width: 33%; vertical-align: top'>";
				printf ("<span class='infoTitle'>%s</span><br>%s", __($guid, 'Maximum Participants'), $activity["maxParticipants"] );
			print "</td>";

			print "<td style='width: 33%; vertical-align: top'>";
				printf ("<span class='infoTitle' title=''>%s</span><br>%s", __($guid, "Waiting"), $activity["waiting"] );
			print "</td>";
		print "</tr>";
	print "</tbody></table>";


	print "<h2>" ;
	print __($guid, "Attendance") ;
	print "</h2>" ;

	if ( $allColumns == false && $attendanceResult->rowCount()<1 ) {
		print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
		return;
	}

	if ( empty($activityWeekDays) || empty($activityTimespan) ) {
		print "<div class='error'>" ;
			print __($guid, "There are no time slots assigned to this activity, or the start and end dates are invalid. New attendance values cannot be entered until the time slots and dates are added.") ;
		print "</div>" ;
	}
	
	if ( count($activitySessions) <=0 ) {
		print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {

		if ( isActionAccessible($guid, $connection2, "/modules/Activities/report_attendanceExport.php") ) {
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/report_attendanceExport.php?gibbonActivityID=" . $gibbonActivityID . "'>" .  __($guid, 'Export to Excel') . "<img style='margin-left: 5px' title='" . __($guid, 'Export to Excel'). "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
			print "</div>" ;
		}
		
		print "<div class='doublescroll-wrapper'>";

		print "<table class='mini' cellspacing='0' style='width:100%; border: 0; margin:0;'>" ;
			print "<tr class='head' style='height:60px; '>" ;
				print "<th style='width:175px;'>" ;
					print __($guid, "Student") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Attendance")  ;
				print "</th>" ;
				print "<th class='emphasis subdued' style='text-align:right'>" ;
					printf( __($guid, 'Sessions Recorded: %s of %s'), count($sessionAttendanceData), count($activitySessions)  );
				print "</th>" ;
			print "</tr>" ;
		print "</table>" ;
		print "<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>";

		$columnCount = ($allColumns)? count($activitySessions) : count($sessionAttendanceData);
		
		print "<div class='doublescroll-container'>";
		print "<table class='mini colorOddEven' cellspacing='0' style='width: ". ($columnCount * 56) ."px'>" ;

			print "<tr style='height: 55px'>" ;
				print "<td style='vertical-align:top;height:55px;'>".__($guid, "Date")."</td>" ;

				foreach ($activitySessions as $sessionDate => $sessionTimestamp ) {
				
					if ( isset($sessionAttendanceData[$sessionDate]['data'])  ) {
						// Handle instances where the time slot has been deleted after creating an attendance record
						if ( !in_array(date('D', $sessionTimestamp), $activityWeekDays) || ($sessionTimestamp < $activityTimespan['start']) || ($sessionTimestamp > $activityTimespan['end'])  ) {
							print "<td style='vertical-align:top; width: 45px;' class='warning' title='".__($guid, 'Does not match the time slots for this activity.')."'>";
						} else {
							print "<td style='vertical-align:top; width: 45px;'>";
						}
						
						printf( "<span title='%s'>%s</span><br/>&nbsp;<br/>", $sessionAttendanceData[$sessionDate]['info'], date('D<\b\r>M j', $sessionTimestamp ) );
					} else {
						print "<td style='color: #bbb; vertical-align:top; width: 45px;'>";
						print date('D<\b\r>M j', $sessionTimestamp )."<br/>&nbsp;<br/>";
					}
					print "</td>" ;
				}

			print "</tr>" ;
			
			$count=0;

			while ($row=$studentResult->fetch()) {

				$count++ ;
				$student = $row["gibbonPersonID"];
				
				print "<tr data-student='$student'>" ;
					print "<td>" ;
						print $count . ". " . formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
					print "</td>" ;

					foreach ($activitySessions as $sessionDate => $sessionTimestamp ) {
						print "<td class='col$i'>";
						if ( isset($sessionAttendanceData[$sessionDate]['data']) ) {
							if ( isset($sessionAttendanceData[$sessionDate]['data'][$student]) ) {
								print "✓";
							}
						}
						print "</td>" ;
					}

				print "</tr>" ;
				
				$lastPerson=$row["gibbonPersonID"] ;
			}


			// Output a total attendance per column
			print "<tr>" ;
				print "<td class='right'>" ;
					print __($guid, 'Total students:');
				print "</td>" ;

				foreach ($activitySessions as $sessionDate => $sessionTimestamp ) {
					print "<td>";
					if ( isset($sessionAttendanceData[$sessionDate])  ) {
						print count($sessionAttendanceData[$sessionDate]['data']) .' / '. $activity['participants'];
					}
					print "</td>" ;
				}

			print "</tr>" ;


			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=16>" ;
						print __($guid, "There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}


				


		print "</table>" ;
		print "</div>" ;
		print "</div><br/>" ;
	}


	

	

}

?>