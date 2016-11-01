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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Daily Attendance') . "</div>" ;
	print "</div>" ;

	print "<h2>" ;
	print _("View Daily Attendance") ;
	print "</h2>" ;

	if (isset($_GET["currentDate"])==FALSE) {
	 	$currentDate=date("Y-m-d");
	}
	else {
		$currentDate=dateConvert($guid, $_GET["currentDate"]) ;	 
	}

	$today=date("Y-m-d");

	$lastNSchoolDays = getLastNSchoolDays($guid, $connection2, $currentDate, 10, true);

	$accessNotRegistered = isActionAccessible($guid, $connection2, "/modules/Attendance/report_rollGroupsNotRegistered_byDate.php") && isActionAccessible($guid, $connection2, "/modules/Attendance/report_courseClassesNotRegistered_byDate.php");

	$gibbonPersonID = ($accessNotRegistered && isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : $_SESSION[$guid]["gibbonPersonID"];
	?>

	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
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
			<?php if ( $accessNotRegistered ) : ?>
				<tr>
					<td>
						<b><?php echo __($guid, 'Staff') ?></b>
					</td>
					<td class="right">
						<select class="standardWidth" name="gibbonPersonID">
							<?php
							try {
		                        $dataSelect = array();
		                        $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
		                        $resultSelect = $connection2->prepare($sqlSelect);
		                        $resultSelect->execute($dataSelect);
		                    } catch (PDOException $e) {}

                            echo "<option value=''></option>";
							while ($rowSelect = $resultSelect->fetch() ) {
								$selected = ($gibbonPersonID == $rowSelect['gibbonPersonID'])? 'selected' : '';

								echo "<option value='".$rowSelect['gibbonPersonID']."' $selected>".formatName('', htmlPrep($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true).'</option>';
								
							}
							?>
						</select>
					</td>
				</tr>
				<?php endif; ?>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/attendance.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>

	<?php


	if (isset($_SESSION[$guid]["username"])) {

		if ($currentDate>$today) {
			print "<div class='error'>" ;
				print _("The specified date is in the future: it must be today or earlier.");
			print "</div>" ;
		}
		else if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
			print "<div class='error'>" ;
				print _("School is closed on the specified date, and so attendance information cannot be recorded.") ;
			print "</div>" ;
		} else {


			if ( isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php") ) {
				// Show My Form Groups
				try {
					$data=array("gibbonPersonIDTutor1"=>$gibbonPersonID, "gibbonPersonIDTutor2"=>$gibbonPersonID, "gibbonPersonIDTutor3"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonRollGroupID, gibbonRollGroup.nameShort as name, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroup.attendance = 'Y'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

			 	if ($result->rowCount()>0) {

			 		print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
					print __($guid, "My Roll Group") ;
					print "</h2>" ;

					print "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>" ;
						print "<tr class='head'>" ;
								print "<th style='width: 80px; font-size: 85%; text-transform: uppercase'>" ;
								print _("Group") ;
							print "</th>" ;

							print "<th style='width: 342px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Recent History") ;
							print "</th>" ;

							print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Today") ;
							print "</th>" ;

							print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Present") ;
							print "</th>" ;

							print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Absent") ;
							print "</th>" ;

							if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {
								
								print "<th style='width: 50px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
									print _("Actions") ;
								print "</th>" ;
							}

						print "</tr>" ;

						while ($row=$result->fetch()) {

							//Produce array of attendance data
					        try {
					            $dataAttendance = array("gibbonRollGroupID" => $row["gibbonRollGroupID"], 'dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
					            $sqlAttendance = 'SELECT date, gibbonRollGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID AND date>=:dateStart AND date<=:dateEnd ORDER BY date';
					            $resultAttendance = $connection2->prepare($sqlAttendance);
					            $resultAttendance->execute($dataAttendance);
					        } catch (PDOException $e) {
					            echo "<div class='error'>".$e->getMessage().'</div>';
					        }
					        $logHistory = array();
					        while ($rowAttendance = $resultAttendance->fetch()) {
					            $logHistory[$rowAttendance['date']] = true;
					        }

							//Grab attendance log for the group & current day
							try {
								$dataLog=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"], "date"=>$currentDate . "%"); 
								
								$sqlLog="SELECT DISTINCT gibbonAttendanceLogRollGroupID, gibbonAttendanceLogRollGroup.timestampTaken as timestamp, 
								COUNT(DISTINCT gibbonPersonID) AS total, 
								SUM(DISTINCT gibbonPersonID AND gibbonAttendanceLogPerson.direction = 'Out') AS absent 
								FROM gibbonAttendanceLogPerson 
								JOIN gibbonAttendanceLogRollGroup ON gibbonAttendanceLogRollGroup.date = gibbonAttendanceLogPerson.date 
								WHERE gibbonAttendanceLogRollGroup.gibbonRollGroupID=:gibbonRollGroupID 
								AND gibbonAttendanceLogPerson.date LIKE :date 
								AND gibbonAttendanceLogPerson.gibbonCourseClassID = 0 
								GROUP BY gibbonAttendanceLogRollGroup.gibbonAttendanceLogRollGroupID 
								ORDER BY gibbonAttendanceLogPerson.timestampTaken" ;

								$resultLog=$connection2->prepare($sqlLog);
								$resultLog->execute($dataLog);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							$log=$resultLog->fetch();

							print "<tr>" ;

								print "<td style='word-wrap: break-word'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'>" . $row["name"] . "</a>" ;
								print "</td>" ;


								print "<td style='text-align: center'>" ;
									

									echo "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
			                        echo '<tr>';
			                        $historyCount = 0;
			                        for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {

			                            $link = '';
			                            if ($i > ( count($lastNSchoolDays) - 1)) {
			                                echo "<td class='highlightNoData'>";
			                                echo '<i>'.__($guid, 'NA').'</i>';
			                                echo '</td>';
			                            } else {

			                                $currentDayTimestamp = dateConvertToTimestamp($lastNSchoolDays[$i]);

			                                $link = './index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$row['gibbonRollGroupID'].'&currentDate='.$lastNSchoolDays[$i];

			                                if (isset($logHistory[$lastNSchoolDays[$i]]) == false) {
			                                    //$class = 'highlightNoData';
			                                    $class = 'highlightAbsent';
			                                } else {
			                                    
			                                    $class = 'highlightPresent';
			                                }

			                                echo "<td class='$class' style='padding: 12px !important;'>";
			                                    echo "<a href='$link'>";
			                                    echo date('d', $currentDayTimestamp).'<br/>';
			                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
			                                    echo '</a>';
			                                echo '</td>';
			                            }

			                            // Wrap to a new line every 10 dates
			                            if (  ($historyCount+1) % 10 == 0 ) {
			                                echo '</tr><tr>';
			                            }

			                            $historyCount++;
			                        }

			                        echo '</tr>';
			                        echo '</table>';


								print "</td>" ;

								print "<td style='text-align: center'>" ;
									// Attendance not taken
									if ($resultLog->rowCount()<1) {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconCross.png"/>' ;
									} else {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconTick.png"/>' ;
									}
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print $log["absent"];
								print "</td>" ;

								
								if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {

									print "<td style='text-align: center'>" ;
										print "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . $currentDate . "'><img title='" . _('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
									print "</td>" ;

								}
								

							print "</tr>" ;


						}

					print "</table><br/>" ;
				}
			}

			if ( isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") ) {

				//Produce array of attendance data
		        try {
		            $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0]);
		            $sql = "SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date";

		            $result = $connection2->prepare($sql);
		            $result->execute($data);
		        } catch (PDOException $e) {
		            echo "<div class='error'>".$e->getMessage().'</div>';
		        }
		        $logHistory = array();
		        while ($row = $result->fetch()) {
		            $logHistory[$row['gibbonCourseClassID']][$row['date']] = true;
		        }

		        // Produce an array of scheduled classes
		        try {
		            $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
		            $sql = "SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayDate.date FROM gibbonTTDayRowClass JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) WHERE gibbonCourseClass.attendance = 'Y' AND gibbonTTDayDate.date>=:dateStart AND gibbonTTDayDate.date<=:dateEnd ORDER BY gibbonTTDayDate.date";

		            $result = $connection2->prepare($sql);
		            $result->execute($data);
		        } catch (PDOException $e) {
		            echo "<div class='error'>".$e->getMessage().'</div>';
		        }
		        $ttHistory = array();
		        while ($row = $result->fetch()) {
		            $ttHistory[$row['gibbonCourseClassID']][$row['date']] = true;
		        }

				//Show My Classes
				try {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $gibbonPersonID);
					
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID,
					(SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount 
					FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson 
					WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID 
					AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID 
					AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' 
					AND gibbonCourseClass.attendance = 'Y' 
					ORDER BY course, class" ;
					
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }

				if ($result->rowCount()>0) {
					print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
					print _("My Classes") ;
					print "</h2>" ;

					print "<table class='mini colorOddEven fullWidth' cellspacing='0' style='table-layout: fixed;'>" ;
						print "<tr class='head'>" ;
							print "<th style='width: 80px; font-size: 85%; text-transform: uppercase'>" ;
							print _("Group") ;
						print "</th>" ;

						print "<th style='width: 342px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Recent History") ;
						print "</th>" ;

						print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Today") ;
						print "</th>" ;

						print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("In") ;
						print "</th>" ;

						print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Out") ;
						print "</th>" ;

						if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
							
							print "<th style='width: 50px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Actions") ;
							print "</th>" ;
						}

					print "</tr>" ;

						$count=0;

						while ($row=$result->fetch()) {

							// Skip unscheduled courses
							//if ( isset($ttHistory[$row['gibbonCourseClassID']]) == false || count($ttHistory[$row['gibbonCourseClassID']]) == 0) continue;

							// Skip classes with no students
                			if ($row['studentCount'] <= 0) continue;

							$count++ ;

							//Grab attendance log for the class & current day
							try {
								$dataLog=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "date"=>$currentDate . "%"); 
								
								$sqlLog="SELECT gibbonAttendanceLogCourseClass.timestampTaken as timestamp, 
								COUNT(gibbonAttendanceLogPerson.gibbonPersonID) AS total, SUM(gibbonAttendanceLogPerson.direction = 'Out') AS absent 
								FROM gibbonAttendanceLogCourseClass 
								JOIN gibbonAttendanceLogPerson ON gibbonAttendanceLogPerson.gibbonCourseClassID = gibbonAttendanceLogCourseClass.gibbonCourseClassID
								WHERE gibbonAttendanceLogCourseClass.gibbonCourseClassID=:gibbonCourseClassID 
								AND gibbonAttendanceLogCourseClass.date LIKE :date AND gibbonAttendanceLogPerson.date LIKE :date 
								GROUP BY gibbonAttendanceLogCourseClass.gibbonAttendanceLogCourseClassID  
								ORDER BY gibbonAttendanceLogCourseClass.timestampTaken" ;
	 
								$resultLog=$connection2->prepare($sqlLog);
								$resultLog->execute($dataLog);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							$log=$resultLog->fetch();
							
							print "<tr>" ;

								print "<td style='word-wrap: break-word'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'>" . $row["course"] . "." . $row["class"] . "</a>" ;
								print "</td>" ;

								print "<td style='text-align: center'>" ;

								echo "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
		                        echo '<tr>';

		                        $historyCount = 0;
		                        for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {
		                            $link = '';
		                            if ($i > ( count($lastNSchoolDays) - 1)) {
		                                echo "<td class='highlightNoData'>";
		                                echo '<i>'.__($guid, 'NA').'</i>';
		                                echo '</td>';
		                            } else {

		                                $currentDayTimestamp = dateConvertToTimestamp($lastNSchoolDays[$i]);
		                                
		                                $link = './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&currentDate='.$lastNSchoolDays[$i];

		                                if (isset($logHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
		                                	$class = 'highlightPresent';
		                                } else {

		                                	if (isset($ttHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
		                                    	$class = 'highlightAbsent';
		                                    } else {
		                                    	$class = 'highlightNoData';
		                                    	$link = '';
		                                    }
		                                }

		                                echo "<td class='$class' style='padding: 12px !important;'>";
		                                if ($link != '') {
		                                    echo "<a href='$link'>";
		                                    echo date('d', $currentDayTimestamp).'<br/>';
		                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
		                                    echo '</a>';
		                                } else {
		                                    echo date('d', $currentDayTimestamp).'<br/>';
		                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
		                                }
		                                echo '</td>';

		                                // Wrap to a new line every 10 dates
		                                if (  ($historyCount+1) % 10 == 0 ) {
		                                    echo '</tr><tr>';
		                                }

		                                $historyCount++;
		                            }
		                        }

		                        echo '</tr>';
		                        echo '</table>';

								print "</td>" ;

								print "<td style='text-align: center'>" ;
								// Attendance not taken, timetabled
								if (isset($ttHistory[$row['gibbonCourseClassID']][$currentDate]) == true) {
									if ($resultLog->rowCount()<1) {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconCross.png"/>' ;
									} else {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconTick.png"/>' ;
									}
								}
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print $log["absent"];
								print "</td>" ;

								
								if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {

									print "<td style='text-align: center'>" ;
										print "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&currentDate=" . $currentDate . "'><img title='" . _('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
									print "</td>" ;

								}
								

							print "</tr>" ;
						}
					print "</table>" ;
				}
			}
		}
	}

	
}
?>