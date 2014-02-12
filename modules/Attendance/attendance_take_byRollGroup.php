<?
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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Take Attendance by Roll Group</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Your request failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Your request was successful, but some data was not properly saved." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Your request failed because the specified date is not in the future, or is not a school day." ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage ="Your request failed because the specified date is not in the future, or is not a school day." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Your request was completed successfully." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	$gibbonRollGroupID="" ;
	if (isset($_GET["gibbonRollGroupID"])==FALSE) {
		try {
			$data=array("gibbonPersonIDTutor1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor3"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor=:gibbonPersonIDTutor3) AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	 	if ($result->rowCount()>0) {
			$row=$result->fetch() ;
			$gibbonRollGroupID=$row["gibbonRollGroupID"] ;
		}
	}
	else {
		$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;	 
	}
	
	if (isset($_GET["currentDate"])==FALSE) {
	 	$currentDate=date("Y-m-d");
	}
	else {
		$currentDate=dateConvert($_GET["currentDate"]) ;	 
	}
	
	$today=date("Y-m-d");
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2>
					<h3>
					Choose Roll Group
					</h3>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Roll Group</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonRollGroupID">
						<?
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonRollGroupID==$rowSelect["gibbonRollGroupID"]) {
								print "<option selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Date *</b><br/>
					<span style="font-size: 90%"><i>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="currentDate" id="currentDate" maxlength=10 value="<? print dateConvertBack($currentDate) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var date=new LiveValidation('date');
						date.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
						date.add(Validate.Presence);
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
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/attendance_take_byRollGroup.php">
					<input type="submit" value="Search">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($gibbonRollGroupID!="") {
		if ($currentDate>$today) {
			print "<div class='error'>" ;
				print "The specified date is in the future: it must be today or earlier.";
			print "</div>" ;
		}
		else {
			if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
				print "<div class='error'>" ;
					print "School is closed on the specified date, and so attendance information cannot be recorded.";
				print "</div>" ;
			}
			else {
				//Get last 5 school days from currentDate within the last 100
				$timestamp=dateConvertToTimestamp($currentDate) ;
				$count=0 ;
				$spin=1 ;
				$last5SchoolDays=array() ;
				while ($count<5 AND $spin<=100) {
					$date=date("Y-m-d", ($timestamp-($spin*86400))) ;
					if (isSchoolOpen($guid, $date,$connection2)) {
						$last5SchoolDays[$count]=$date ;
						$count++ ;
					}
					$spin++ ;
				}
				$last5SchoolDaysCount=$count ;
				
				//Show attendance log for the current day
				try {
					$dataLog=array("gibbonRollGroupID"=>$gibbonRollGroupID, "date"=>$currentDate . "%"); 
					$sqlLog="SELECT * FROM gibbonAttendanceLogRollGroup, gibbonPerson WHERE gibbonAttendanceLogRollGroup.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonRollGroupID=:gibbonRollGroupID AND date LIKE :date ORDER BY timestampTaken" ;
					$resultLog=$connection2->prepare($sqlLog);
					$resultLog->execute($dataLog);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($resultLog->rowCount()<1) {
					print "<div class='error'>" ;
						print "Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.";
					print "</div>" ;
				}
				else {
					print "<div class='success'>" ;
						print "Attendance has been taken at the following times for the specified date for this group:";
						print "<ul>" ;
						while ($rowLog=$resultLog->fetch()) {
							print "<li>Recorded at " . substr($rowLog["timestampTaken"],11) . " on " . dateConvertBack(substr($rowLog["timestampTaken"],0,10)) . " by " . formatName("", $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true) ."</li>" ;
						}
						print "</ul>" ;
					print "</div>" ;
				}
				
				//Show roll group grid
				try {
					$dataRollGroup=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
					$sqlRollGroup="SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
					$resultRollGroup=$connection2->prepare($sqlRollGroup);
					$resultRollGroup->execute($dataRollGroup);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultRollGroup->rowCount()<1) {
					print "<div class='error'>" ;
						print "There are no records to display.";
					print "</div>" ;
				}
				else {
					$count=0 ;
					$countPresent=0 ;
					$columns=4 ;
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/attendance_take_byRollGroupProcess.php'>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width:100%'>" ;
						?>
						<tr class='break'>
							<td colspan=<? print $columns ?>>
								<h3>
									Take Attendance
								</h3>
							</td>
						</tr>
						<?
						while ($rowRollGroup=$resultRollGroup->fetch()) {
							if ($count%$columns==0) {
								print "<tr>" ;
							}
							//Get student log data
							try {
								$dataLog=array("gibbonPersonID"=>$rowRollGroup["gibbonPersonID"], "date"=>$currentDate . "%"); 
								$sqlLog="SELECT * FROM gibbonAttendanceLogPerson, gibbonPerson WHERE gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY timestampTaken DESC" ;
								$resultLog=$connection2->prepare($sqlLog);
								$resultLog->execute($dataLog);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							$rowLog=$resultLog->fetch() ;
							
							
							if ($rowLog["type"]=="Absent") {
								print "<td style='border: 1px solid #CC0000!important; background: none; background-color: #F6CECB; width:20%; text-align: center; vertical-align: top'>" ;
							}
							else {
								print "<td style='border: 1px solid #ffffff; width:20%; text-align: center; vertical-align: top'>" ;
							}
								
								printUserPhoto($guid, $rowRollGroup["image_75"], 75) ;
								
								print "<div style='padding-top: 5px'><b>" . formatName("", htmlPrep($rowRollGroup["preferredName"]), htmlPrep($rowRollGroup["surname"]), "Student", true) . "<b></div><br/>" ;
								
								print "<input type='hidden' name='$count-gibbonPersonID' value='" . $rowRollGroup["gibbonPersonID"] . "'>" ;
								print "<select style='float: none; width:130px; margin-bottom: 3px' name='$count-type'>" ;
									print "<option " ; if ($rowLog["type"]=="Present") { print "selected " ; } ; print "value='Present'>Present</option>" ;
									print "<option " ; if ($rowLog["type"]=="Present - Late") { print "selected " ; } ; print "value='Present - Late'>Present - Late</option>" ;
									print "<option " ; if ($rowLog["type"]=="Present - Offsite") { print "selected " ; } ; print "value='Present - Offsite'>Present - Offsite</option>" ;
									print "<option " ; if ($rowLog["type"]=="Absent") { print "selected " ; } ; print "value='Absent'>Absent</option>" ;
									print "<option " ; if ($rowLog["type"]=="Left") { print "selected " ; } ; print "value='Left'>Left</option>" ;
									print "<option " ; if ($rowLog["type"]=="Left - Early") { print "selected " ; } ; print "value='Left - Early'>Left - Early</option>" ;
								print "</select>" ;
								print "<select style='float: none; width:130px; margin-bottom: 3px' name='$count-reason'>" ;
									print "<option " ; if ($rowLog["reason"]=="") { print "selected " ; } ; print "value=''></option>" ;
									print "<option " ; if ($rowLog["reason"]=="Pending") { print "selected " ; } ; print "value='Pending'>Pending</option>" ;
									print "<option " ; if ($rowLog["reason"]=="Education") { print "selected " ; } ; print "value='Education'>Education</option>" ;
									print "<option " ; if ($rowLog["reason"]=="Family") { print "selected " ; } ; print "value='Family'>Family</option>" ;
									print "<option " ; if ($rowLog["reason"]=="Medical") { print "selected " ; } ; print "value='Medical'>Medical</option>" ;
									print "<option " ; if ($rowLog["reason"]=="Other") { print "selected " ; } ; print "value='Other'>Other</option>" ;
								print "</select>" ;
								print "<input type='text' maxlength=255 name='$count-comment' id='$count-comment' style='float: none; width:126px; margin-bottom: 3px' value='" . htmlPrep($rowLog["comment"]) . "'>" ;
								
								if ($rowLog["type"]=="Present" OR $rowLog["type"]=="Present - Late") {
									$countPresent++ ;
								}	
								
								print "<table cellspacing='0' style='width:134px; margin: 0 auto 3px auto; height: 35px' >" ;
									print "<tr>" ;
										for ($i=4; $i>=0; $i--) {
											$link="" ;
											if ($i>($last5SchoolDaysCount-1)) {
												$extraStyle="color: #555; background-color: #eee;" ;
												
												print "<td style='" . $extraStyle . "height: 25px; width: 20%'>" ;
												print "<i>NA</i>" ;
												print "</td>" ;
											}
											else {
												try {
													$dataLast5SchoolDays=array("gibbonPersonID"=>$rowRollGroup["gibbonPersonID"], "date"=>date("Y-m-d", dateConvertToTimestamp($last5SchoolDays[$i])) . "%"); 
													$sqlLast5SchoolDays="SELECT * FROM gibbonAttendanceLogPerson WHERE gibbonPersonID=:gibbonPersonID AND date LIKE :date ORDER BY gibbonAttendanceLogPersonID DESC" ;
													$resultLast5SchoolDays=$connection2->prepare($sqlLast5SchoolDays);
													$resultLast5SchoolDays->execute($dataLast5SchoolDays);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($resultLast5SchoolDays->rowCount()==0) {
													$extraStyle="color: #555; background-color: #eee; " ;
												}
												else {
													$link="./index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/attendance_take_byPerson.php&gibbonPersonID=" . $rowRollGroup["gibbonPersonID"] . "&currentDate=" . date("d/m/Y", dateConvertToTimestamp($last5SchoolDays[$i])) ;
													$rowLast5SchoolDays=$resultLast5SchoolDays->fetch() ;
													if ($rowLast5SchoolDays["type"]=="Absent") {
														$color="#c00" ;
														$extraStyle="color: #c00; background-color: #F6CECB; " ;
													}
													else {
														$color="#390" ;
														$extraStyle="color: #390; background-color: #D4F6DC; " ;
													}
												}
												
												print "<td style='" . $extraStyle . "height: 25px; width: 20%'>" ;
													if ($link!="") {
														print "<a style='text-decoration: none; color: $color' href='$link'>" ;
														print date("d", dateConvertToTimestamp($last5SchoolDays[$i])) . "<br/>" ;
														print "<span style='font-size: 65%'>" . date("M", dateConvertToTimestamp($last5SchoolDays[$i])) . "</span>" ;
														print "</a>" ;
													}
													else {
														print date("d", dateConvertToTimestamp($last5SchoolDays[$i])) . "<br/>" ;
														print "<span style='font-size: 65%'>" . date("M", dateConvertToTimestamp($last5SchoolDays[$i])) . "</span>" ;
													}
												print "</td>" ;
											}
										}
									print "</tr>" ;
								print "</table>" ;
		
							print "</td>" ;
							
							if ($count%$columns==($columns-1)) {
								print "</tr>" ;
							}
							$count++ ;
						}
						
						for ($i=0;$i<$columns-($count%$columns);$i++) {
							print "<td></td>" ;
						}
						
						if ($count%$columns!=0) {
							print "</tr>" ;
						}
						
						print "<tr>" ;
							print "<td class='right' colspan=5>" ;
								print "<div class='success'>" ;
									print "<b>Total students: $count</b><br/>" ;
									if ($resultLog->rowCount()>=1) {
										print "<span title='e.g. Present or Present - Late'>Total students present in room: <b>$countPresent</b><br/>" ;
										print "<span title='e.g. not Present and not Present - Late'>Total students absent from room: <b>" . ($count-$countPresent) . "</b><br/>" ;
									}
								print "</div>" ;
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td class='right' colspan=5>" ;
								print "<input type='hidden' name='gibbonRollGroupID' value='$gibbonRollGroupID'>" ;
								print "<input type='hidden' name='currentDate' value='$currentDate'>" ;
								print "<input type='hidden' name='count' value='" . $resultRollGroup->rowCount() . "'>" ;
								print "<input type='hidden' name='address' value='" . $_SESSION[$guid]["address"] . "'>" ;
								print "<input type='submit' value='Submit'>" ;
							print "</td>" ;
						print "</tr>" ;
						print "</table>" ;	
					print "</form>" ;
				}
			}
		}
	}
}
?>