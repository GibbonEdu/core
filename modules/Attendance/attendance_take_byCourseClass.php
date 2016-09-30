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

require_once './modules/Attendance/src/attendanceView.php';

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else if ( getSettingByScope($connection2, 'Attendance', 'attendanceEnableByClass')  == 'N') {
	//Disabled
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Take Attendance by Class') . "</div>" ;
	print "</div>" ;

	if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array( 'error3' => __($guid, 'Your request failed because the specified date is not in the future, or is not a school day.')) );
    }

    $attendance = new Module\Attendance\attendanceView(NULL, NULL, $pdo);
	
	$gibbonCourseClassID="" ;
	if (isset($_GET["gibbonCourseClassID"])==FALSE) {
		try {
			$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			
			$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonSchoolYear.firstDay, gibbonSchoolYear.lastDay 
			FROM gibbonCourse 
			JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
			JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
			JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
			WHERE gibbonPersonID=:gibbonPersonID 
			AND gibbonCourseClass.attendance='Y' 
			AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID " ;

			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	 	if ($result->rowCount()>0) {
			$row=$result->fetch() ;
			$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
		}
	}
	else {
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;	 
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
					<?php print _('Choose Class') ?>
					</h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Class') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonCourseClassID">
						<?php
						print "<option value=''>" . _('Please select...') . "</option>" ;

						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.attendance='Y' ORDER BY course, class" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }

						if ($resultSelect->rowCount() > 0) {
							print "<optgroup label='--" . _('My Classes') . "--'>" ;

							while ($rowSelect=$resultSelect->fetch()) {

								if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
									print "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
								}
								else {
									print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
								}
							}

							print "</optgroup>" ;
						}


						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance='Y' ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						print "<optgroup label='--" . _('All Classes') . "--'>" ;

						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
								print "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
							}						}

						print "</optgroup>" ;
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Date') ?> *</b><br/>
					<span style="font-size: 90%"><i><?php print _("Format:") . " " . $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
				</td>
				<td class="right">
					<input name="currentDate" id="currentDate" maxlength=10 value="<?php print dateConvertBack($guid, $currentDate) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var date=new LiveValidation('date');
						date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/attendance_take_byCourseClass.php">
					<input type="submit" value="Search">
				</td>
			</tr>
		</table>
	</form>

	
	<?php
	
	if ($gibbonCourseClassID!="") {
		if ($currentDate>$today) {
			print "<div class='error'>" ;
				print _("The specified date is in the future: it must be today or earlier.");
			print "</div>" ;
		}
		else {
			if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
				print "<div class='error'>" ;
					print _("School is closed on the specified date, and so attendance information cannot be recorded.") ;
				print "</div>" ;
			}
			else {
				//Check roll group
				$CourseClassFail=FALSE ;
				$firstDay=NULL ;
				$lastDay=NULL ;
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonCourseClass.*, gibbonCourse.gibbonSchoolYearID,firstDay, lastDay, 
					gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse 
					JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
					JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
					WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID" ;

					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					$CourseClassFail=TRUE ;
				}
				if ($result->rowCount()!=0) {
					$row=$result->fetch() ;
					$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
					$firstDay=$row["firstDay"] ;
					$lastDay=$row["lastDay"] ;
					$gibbonCourseClassName = htmlPrep($row["course"]) . "." . htmlPrep($row["class"]);
				}
				if ($CourseClassFail) {
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				else if ($row["attendance"] == 'N') {
					print "<div class='error'>" ;
						print _("Attendance taking has been disabled for this class.") ;
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
						$dataLog=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$currentDate . "%"); 
						$sqlLog="SELECT * FROM gibbonAttendanceLogCourseClass, gibbonPerson WHERE gibbonAttendanceLogCourseClass.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND date LIKE :date ORDER BY timestampTaken" ;
						$resultLog=$connection2->prepare($sqlLog);
						$resultLog->execute($dataLog);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultLog->rowCount()<1) {
						print "<div class='error'>" ;
							print _("Attendance has not been taken for this group yet for the specified date. The entries below are a best-guess based on defaults and information put into the system in advance, not actual data.") ;
						print "</div>" ;
					}
					else {
						print "<div class='success'>" ;
							print _("Attendance has been taken at the following times for the specified date for this group:") ;
							print "<ul>" ;
							while ($rowLog=$resultLog->fetch()) {
								print "<li>" . sprintf(_('Recorded at %1$s on %2$s by %3$s.'), substr($rowLog["timestampTaken"],11), dateConvertBack($guid, substr($rowLog["timestampTaken"],0,10)), formatName("", $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true)) ."</li>" ;
							}
							print "</ul>" ;
						print "</div>" ;
					}
				
					//Show roll group grid
					try {
						$dataCourseClass=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
						$sqlCourseClass="SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND role='Student' ORDER BY surname, preferredName" ;
						$resultCourseClass=$connection2->prepare($sqlCourseClass);
						$resultCourseClass->execute($dataCourseClass);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				
					if ($resultCourseClass->rowCount()<1) {
						print "<div class='error'>" ;
							print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						$count=0 ;
						$countPresent=0 ;
						$columns=4 ;
						print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/attendance_take_byCourseClassProcess.php'>" ;
							print "<table class='smallIntBorder' cellspacing='0' style='width:100%'>" ;
							?>
							<tr class='break'>
								<td colspan=<?php print $columns ?>>
									<h3>
										<?php print _('Take Attendance') .": ". $gibbonCourseClassName; ?>
									</h3>
								</td>
							</tr>
							<?php
							while ($rowCourseClass=$resultCourseClass->fetch()) {
								if ($count%$columns==0) {
									print "<tr>" ;
								}
								//Get student log data
								try {
									$dataLog=array("gibbonPersonID"=>$rowCourseClass["gibbonPersonID"], "date"=>$currentDate . "%", 'gibbonCourseClassID' => $gibbonCourseClassID); 
									$sqlLog="SELECT * FROM gibbonAttendanceLogPerson, gibbonPerson WHERE gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND (gibbonCourseClassID=0 OR gibbonCourseClassID=:gibbonCourseClassID) AND date LIKE :date ORDER BY timestampTaken DESC" ;
									$resultLog=$connection2->prepare($sqlLog);
									$resultLog->execute($dataLog);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
							
								$rowLog=$resultLog->fetch() ;
							
							
								if ( $attendance->isTypeAbsent($rowLog["type"]) ) {
									// Orange/warning background for partial absense
									if ($rowLog["gibbonCourseClassID"] == $gibbonCourseClassID) {
										print "<td style='border: 1px solid #D65602!important; background: none; background-color: #FFD2A9; width:20%; text-align: center; vertical-align: top'>" ;
									} else {
										print "<td style='border: 1px solid #CC0000!important; background: none; background-color: #F6CECB; width:20%; text-align: center; vertical-align: top'>" ;
									}
								}
								else {
									print "<td style='border: 1px solid #ffffff; width:20%; text-align: center; vertical-align: top'>" ;
								}
								
									//Alerts, if permission allows
        							echo getAlertBar($guid, $connection2, $rowCourseClass['gibbonPersonID'], $rowCourseClass['privacy']);

        							//User photo
									print getUserPhoto($guid, $rowCourseClass["image_240"], 75) ;
								
									print "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowCourseClass["gibbonPersonID"] . "&subpage=School Attendance'>" . formatName("", htmlPrep($rowCourseClass["preferredName"]), htmlPrep($rowCourseClass["surname"]), "Student", false) . "</a></b></div>" ;
									print "<div style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
										if ($firstDay!=NULL AND $lastDay!=NULL) {
											$absenceCount=getAbsenceCount($guid, $rowCourseClass["gibbonPersonID"], $connection2, $firstDay, $lastDay) ;
											if ($absenceCount!==FALSE) {
												print sprintf(_('%1$s Days Absent'), $absenceCount) ;
											}

											// List partial absences
		                                    if ($rowLog["gibbonCourseClassID"] == $gibbonCourseClassID && $attendance->isTypeAbsent($rowLog["type"]) ) {
		                                        printf( '<br/>'.__($guid, 'Recorded absence for this class'), $resultLog->rowCount() );
		                                    }
										}
									print "</div><br/>" ;
									print "<input type='hidden' name='$count-gibbonPersonID' value='" . $rowCourseClass["gibbonPersonID"] . "'>" ;

									echo $attendance->renderAttendanceTypeSelect( $rowLog['type'], "$count-type", '130px');
                                	echo $attendance->renderAttendanceReasonSelect( $rowLog['reason'], "$count-reason", '130px');

									print "<input type='text' maxlength=255 name='$count-comment' id='$count-comment' style='float: none; width:126px; margin-bottom: 3px' value='" . htmlPrep($rowLog["comment"]) . "'>" ;
								
									if ( $attendance->isTypePresent($rowLog["type"]) ) {
										$countPresent++ ;
									}	
								
									$attendance->renderMiniHistory( $rowCourseClass['gibbonPersonID'] );
									
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
										print "<b>" . _('Total students:') . " $count</b><br/>" ;
										if ($resultLog->rowCount()>=1) {
											print "<span title='" . _('e.g. Present or Present - Late') . "'>" . _('Total students present in room:') . " <b>$countPresent</b><br/>" ;
											print "<span title='" . _('e.g. not Present and not Present - Late') . "'>" . _('Total students absent from room:') . " <b>" . ($count-$countPresent) . "</b><br/>" ;
										}
									print "</div>" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								// Drop-downs to change the whole group at once
			                    echo "<td colspan=3>";
			                        echo $attendance->renderAttendanceTypeSelect( '', "set-all-type", '110px');
			                        echo $attendance->renderAttendanceReasonSelect( '', "set-all-reason", '110px');
			                        echo "<input type='text' maxlength=255 name='set-all-comment' id='set-all-comment' style='float: none; width:100px; margin-bottom: 3px' value='".htmlPrep($rowLog['comment'])."'>";
			                        echo "<input id='set-all' type='button' value='Change All'>";
			                        echo "<span id='set-all-note' class='emphasis small' style='display:none;color:#CC0000;'>".__($guid, "* Submit to save changes.")."</span>";
			                    echo "</td>";

								print "<td class='right' colspan=2>" ;
									print "<input type='hidden' name='gibbonCourseClassID' value='$gibbonCourseClassID'>" ;
									print "<input type='hidden' name='currentDate' value='$currentDate'>" ;
									print "<input type='hidden' name='count' value='" . $resultCourseClass->rowCount() . "'>" ;
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