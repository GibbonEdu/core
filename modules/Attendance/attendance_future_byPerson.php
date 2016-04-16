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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_future_byPerson.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Set Future Absence') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, array("warning1" => "Your request was successful, but some data was not properly saved.")); }
	
	$gibbonPersonID=NULL ;
	if (isset($_GET["gibbonPersonID"])) {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
	}		 
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr class='break'>
				<td colspan=2> 
					<h3>
						<?php print __($guid, 'Choose Student') ?>
					</h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Student') ?></b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonPersonID">
						<?php
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonPersonID==$rowSelect["gibbonPersonID"]) {
								print "<option selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/attendance_future_byPerson.php">
					<input type="submit" value="Search">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonPersonID!="") {
		$today=date("Y-m-d");
		
		//Show attendance log for future days
		
		try {
			$dataLog=array("gibbonPersonID"=>$gibbonPersonID, "date"=>"$today-23-59-59"); 
			$sqlLog="SELECT * FROM gibbonAttendanceLogPerson, gibbonPerson WHERE gibbonAttendanceLogPerson.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID AND type='Absent' AND gibbonAttendanceLogPerson.gibbonPersonID=:gibbonPersonID AND date>:date ORDER BY date" ;
			$resultLog=$connection2->prepare($sqlLog);
			$resultLog->execute($dataLog);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($resultLog->rowCount()>0) {
			print "<div class='success'>" ;
				print __($guid, 'The following future absences have been set for the selected student.') ;
				print "<ul>" ;
				while ($rowLog=$resultLog->fetch()) {
					print "<li style='line-height: 250%'><b>" . dateConvertBack($guid, substr($rowLog["date"],0,10)) . "</b> | " . sprintf(__($guid, 'Recorded at %1$s on %2$s by %3$s'), substr($rowLog["timestampTaken"],11), dateConvertBack($guid, substr($rowLog["timestampTaken"],0,10)), formatName($rowLog["title"], $rowLog["preferredName"], $rowLog["surname"], "Staff", false, true)) . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Attendance/attendance_future_byPersonDeleteProcess.php?gibbonPersonID=$gibbonPersonID&date=" .$rowLog["date"] . "' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a></li>" ;
				}
				print "</ul>" ;
			print "</div>" ;
		}
		
		//Show student form
		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/attendance_future_byPersonProcess.php?gibbonPersonID=$gibbonPersonID" ?>">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr class='break'>
					<td colspan=2> 
						<h3>
							<?php print __($guid, 'Set Future Attendance') ?>
						</h3>
					</td>
				</tr>
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'Type') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
					</td>
					<td class="right">
						<input readonly name="type" id="type" maxlength=10 value="Absent" type="text" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Start Date') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
					</td>
					<td class="right">
						<input name="dateStart" id="dateStart" maxlength=10 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var dateStart=new LiveValidation('dateStart');
							dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						 	dateStart.add(Validate.Presence);
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#dateStart" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'End Date') ?></b><br/>
						<span style="font-size: 90%"><i><?php print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
					</td>
					<td class="right">
						<input name="dateEnd" id="dateEnd" maxlength=10 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var dateEnd=new LiveValidation('dateEnd');
							dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
						</script>
						 <script type="text/javascript">
							$(function() {
								$( "#dateEnd" ).datepicker();
							});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Reason') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						print "<select style='float: none; width: 302px; margin-bottom: 10px' name='reason'>" ;
							print "<option value=''></option>" ;
							print "<option value='Pending'>" . __($guid, 'Pending') . "</option>" ;
							print "<option value='Education'>" . __($guid, 'Education') . "</option>" ;
							print "<option value='Family'>" . __($guid, 'Family') . "</option>" ;
							print "<option value='Medical'>" . __($guid, 'Medical') . "</option>" ;
							print "<option value='Other'>" . __($guid, 'Other') . "</option>" ;
						print "</select>" ;
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Comment') ?></b><br/>
						<span style="font-size: 90%"><i><?php print __($guid, '255 character limit') ?></i></span>
					</td>
					<td class="right">
						<?php
						print "<textarea name='comment' id='comment' rows=3 style='width: 300px'></textarea>" ;
						?>
						<script type="text/javascript">
							var comment=new LiveValidation('comment');
							comment.add( Validate.Length, { maximum: 255 } );
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
?>