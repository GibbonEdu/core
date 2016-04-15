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


			// Show My Form Groups

			try {
				$data=array("gibbonPersonIDTutor1"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor2"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonIDTutor3"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonRollGroupID, gibbonRollGroup.nameShort as name, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

		 	if ($result->rowCount()>0) {

		 		print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
				print _("My Form Group") ;
				print "</h2>" ;

				print "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>" ;
					print "<tr class='head'>" ;
							print "<th style='width: 36%; font-size: 85%; text-transform: uppercase'>" ;
							print _("Group") ;
						print "</th>" ;

						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Present") ;
						print "</th>" ;

						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Absent") ;
						print "</th>" ;

						if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
							print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Taken") ;
							print "</th>" ;

							print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Attendance") ;
							print "</th>" ;
						}

					print "</tr>" ;

					while ($row=$result->fetch()) {

						
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
								print ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
							print "</td>" ;

							print "<td style='text-align: center'>" ;
								print $log["absent"];
							print "</td>" ;

							
							if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {

								
								print "<td style='text-align: center'>" ;
								// Attendance not taken
								if ($resultLog->rowCount()<1) {
									print "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . $currentDate . "'><img title='" . _('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a>" ;
								} else {
									print "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . $currentDate . "'><img title='" . $log["timestamp"] . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>" ;
								}
								print "</td>" ;


								print "<td style='text-align: center'>" ;
									print "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . $currentDate . "'><img title='" . _('Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
								print "</td>" ;

							}
							

						print "</tr>" ;


					}

				print "</table><br/>" ;
			}


			//Show My Classes
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"]);
				
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID 
				FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson 
				WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID 
				AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID 
				AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%' 
				ORDER BY course, class" ;
				
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }

			if ($result->rowCount()>0) {
				print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
				print _("My Classes") ;
				print "</h2>" ;

				print "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>" ;
					print "<tr class='head'>" ;
							print "<th style='width: 36%; font-size: 85%; text-transform: uppercase'>" ;
							print _("Class") ;
						print "</th>" ;

						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Present") ;
						print "</th>" ;

						print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print _("Absent") ;
						print "</th>" ;

						if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
							print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Taken") ;
							print "</th>" ;

							print "<th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print _("Attendance") ;
							print "</th>" ;
						}

					print "</tr>" ;

					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
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
						
						print "<tr class=$rowNum>" ;

							print "<td style='word-wrap: break-word'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'>" . $row["course"] . "." . $row["class"] . "</a>" ;
							print "</td>" ;


							print "<td style='text-align: center'>" ;
								print ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
							print "</td>" ;

							print "<td style='text-align: center'>" ;
								print $log["absent"];
							print "</td>" ;

							
							if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {

								
								print "<td style='text-align: center'>" ;
								// Attendance not taken
								if ($resultLog->rowCount()<1) {
									print "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&currentDate=" . $currentDate . "'><img title='" . _('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a>" ;
								} else {
									print "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&currentDate=" . $currentDate . "'><img title='" . $log["timestamp"] . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>" ;
								}
								print "</td>" ;


								print "<td style='text-align: center'>" ;
									print "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&currentDate=" . $currentDate . "'><img title='" . _('Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
								print "</td>" ;

							}
							

						print "</tr>" ;
					}
				print "</table>" ;
			}
		}
	}

	
}
?>