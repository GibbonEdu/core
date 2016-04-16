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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Take Attendance by Roll Group') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=__($guid, "Your request failed because the specified date is not in the future, or is not a school day.") ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage=__($guid, "Your request failed because the specified date is not in the future, or is not a school day.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
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
			$sql="SELECT gibbonRollGroup.*, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID" ;
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
		$currentDate=dateConvert($guid, $_GET["currentDate"]) ;	 
	}
	
	$today=date("Y-m-d");
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2>
					<h3>
					<?php print __($guid, 'Choose Roll Group') ?>
					</h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Roll Group') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonRollGroupID">
						<?php
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
					<b><?php print __($guid, 'Date') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print __($guid, "Format:") . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></i></span>
				</td>
				<td class="right">
					<input name="currentDate" id="currentDate" maxlength=10 value="<?php print dateConvertBack($guid, $currentDate) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var currentDate=new LiveValidation('currentDate');
						currentDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/attendance_take_byRollGroup.php">
					<input type="submit" value="Search">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonRollGroupID!="") {
		if ($currentDate>$today) {
			print "<div class='error'>" ;
				print __($guid, "The specified date is in the future: it must be today or earlier.");
			print "</div>" ;
		}
		else {
			if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
				print "<div class='error'>" ;
					print __($guid, "School is closed on the specified date, and so attendance information cannot be recorded.") ;
				print "</div>" ;
			}
			else {
				//Check roll group
				$rollGroupFail=FALSE ;
				$firstDay=NULL ;
				$lastDay=NULL ;
				try {
					$data=array("gibbonRollGroupID"=>$gibbonRollGroupID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonRollGroup.*, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonRollGroupID=:gibbonRollGroupID AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					$rollGroupFail=TRUE ;
				}
				if ($result->rowCount()!=0) {
					$row=$result->fetch() ;
					$gibbonRollGroupID=$row["gibbonRollGroupID"] ;
					$firstDay=$row["firstDay"] ;
					$lastDay=$row["lastDay"] ;
				}
				if ($rollGroupFail) {
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
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
							print __($guid, "Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.") ;
						print "</div>" ;
					}
					else {
						print "<div class='success'>" ;
							print __($guid, "Attendance has been taken at the following times for the specified date for this group:") ;
							print "<ul>" ;
							while ($rowLog=$resultLog->fetch()) {
								print "<li>" . sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s.'), substr($rowLog["timestampTaken"],11), dateConvertBack($guid, substr($rowLog["timestampTaken"],0,10)), formatName("", $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true)) ."</li>" ;
							}
							print "</ul>" ;
						print "</div>" ;
					}
				
					//Show roll group grid
					try {
						$dataRollGroup=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
						$sqlRollGroup="SELECT * FROM gibbonStudentEnrolment INNER JOIN gibbonPerson ON gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonRollGroupID=:gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY rollOrder, surname, preferredName" ;
						$resultRollGroup=$connection2->prepare($sqlRollGroup);
						$resultRollGroup->execute($dataRollGroup);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				
					if ($resultRollGroup->rowCount()<1) {
						print "<div class='error'>" ;
							print __($guid, "There are no records to display.") ;
						print "</div>" ;
					}
					else {
						$count=0 ;
						$countPresent=0 ;
						$columns=4 ;
						
						print "<script type='text/javascript'>
							function dateCheck() {
								var date = new Date();
								if ('" . $currentDate . "'<getDate()) {
									return confirm(\"" .__($guid, 'The selected date for attendance is in the past. Are you sure you want to continue?') . "\")
								}
							}
						</script>" ;
						
						print "<form onsubmit=\"return dateCheck()\" method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/attendance_take_byRollGroupProcess.php'>" ;
							print "<table class='smallIntBorder' cellspacing='0' style='width:100%'>" ;
							?>
							<tr class='break'>
								<td colspan=<?php print $columns ?>>
									<h3>
										<?php print __($guid, 'Take Attendance') ?>
									</h3>
								</td>
							</tr>
							<?php
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
								
									print getUserPhoto($guid, $rowRollGroup["image_240"], 75) ;
								
									print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowRollGroup["gibbonPersonID"] . "&subpage=School Attendance'>" . formatName("", htmlPrep($rowRollGroup["preferredName"]), htmlPrep($rowRollGroup["surname"]), "Student", true) . "</a></b></div>" ;
									print "<div style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
										if ($firstDay!=NULL AND $lastDay!=NULL) {
											$absenceCount=getAbsenceCount($guid, $rowRollGroup["gibbonPersonID"], $connection2, $firstDay, $lastDay) ;
											if ($absenceCount!==FALSE) {
												print sprintf(__($guid, '%1$s Days Absent'), $absenceCount) ;
											}
										}
									print "</div><br/>" ;
									print "<input type='hidden' name='$count-gibbonPersonID' value='" . $rowRollGroup["gibbonPersonID"] . "'>" ;
									print "<select style='float: none; width:130px; margin-bottom: 3px' name='$count-type'>" ;
										print "<option " ; if ($rowLog["type"]=="Present") { print "selected " ; } ; print "value='Present'>" . __($guid, 'Present') . "</option>" ;
										print "<option " ; if ($rowLog["type"]=="Present - Late") { print "selected " ; } ; print "value='Present - Late'>" . __($guid, 'Present - Late') . "</option>" ;
										print "<option " ; if ($rowLog["type"]=="Present - Offsite") { print "selected " ; } ; print "value='Present - Offsite'>" . __($guid, 'Present - Offsite') . "</option>" ;
										print "<option " ; if ($rowLog["type"]=="Absent") { print "selected " ; } ; print "value='Absent'>" . __($guid, 'Absent') . "</option>" ;
										print "<option " ; if ($rowLog["type"]=="Left") { print "selected " ; } ; print "value='Left'>" . __($guid, 'Left') . "</option>" ;
										print "<option " ; if ($rowLog["type"]=="Left - Early") { print "selected " ; } ; print "value='Left - Early'>" . __($guid, 'Left - Early') . "</option>" ;
									print "</select>" ;
									print "<select style='float: none; width:130px; margin-bottom: 3px' name='$count-reason'>" ;
										print "<option " ; if ($rowLog["reason"]=="") { print "selected " ; } ; print "value=''></option>" ;
										print "<option " ; if ($rowLog["reason"]=="Pending") { print "selected " ; } ; print "value='Pending'>" . __($guid, 'Pending') . "</option>" ;
										print "<option " ; if ($rowLog["reason"]=="Education") { print "selected " ; } ; print "value='Education'>" . __($guid, 'Education') . "</option>" ;
										print "<option " ; if ($rowLog["reason"]=="Family") { print "selected " ; } ; print "value='Family'>" . __($guid, 'Family') . "</option>" ;
										print "<option " ; if ($rowLog["reason"]=="Medical") { print "selected " ; } ; print "value='Medical'>" . __($guid, 'Medical') . "</option>" ;
										print "<option " ; if ($rowLog["reason"]=="Other") { print "selected " ; } ; print "value='Other'>" . __($guid, 'Other') . "</option>" ;
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
													print "<i>" . __($guid, 'NA') . "</i>" ;
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
										print "<b>" . __($guid, 'Total students:') . " $count</b><br/>" ;
										if ($resultLog->rowCount()>=1) {
											print "<span title='" . __($guid, 'e.g. Present or Present - Late') . "'>" . __($guid, 'Total students present in room:') . " <b>$countPresent</b><br/>" ;
											print "<span title='" . __($guid, 'e.g. not Present and not Present - Late') . "'>" . __($guid, 'Total students absent from room:') . " <b>" . ($count-$countPresent) . "</b><br/>" ;
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
}
?>