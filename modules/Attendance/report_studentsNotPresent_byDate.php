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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_studentsNotPresent_byDate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Students Not Present') . "</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print _("Choose Date") ;
	print "</h2>" ;
	
	if (isset($_GET["currentDate"])==FALSE) {
	 	$currentDate=date("Y-m-d");
	}
	else {
		$currentDate=dateConvert($guid, $_GET["currentDate"]) ;	 
	}
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
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/report_studentsNotPresent_byDate.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($currentDate!="") {
		print "<h2>" ;
		print _("Report Data") ;
		print "</h2>" ;
		
		//Produce array of attendance data
		try {
			$data=array("date"=>$currentDate); 
			$sql="SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date ORDER BY gibbonPersonID, gibbonAttendanceLogPersonID DESC" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			$log=array() ;
			$currentStudent="" ;
			$lastStudent="" ;
			while ($row=$result->fetch()) {
				$currentStudent=$row["gibbonPersonID"] ;
				if (($row["type"]=="Present" OR $row["type"]=="Present - Late" OR $row["type"]=="Present - Offsite") AND $currentStudent!=$lastStudent) {
					$log[$row["gibbonPersonID"]]=TRUE ;	 
				}
				$lastStudent=$currentStudent ;
			}
		
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
					print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<div class='linkTop'>" ;
				print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_studentsNotPresent_byDate_print.php&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
				print "</div>" ;
			
				$lastPerson="" ;
				
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print _("Roll Group") ;
						print "</th>" ;
						print "<th>" ;
							print _("Name") ;
						print "</th>" ;
						print "<th>" ;
							print _("Status") ;
						print "</th>" ;
						print "<th>" ;
							print _("Reason") ;
						print "</th>" ;
						print "<th>" ;
							print _("Comment") ;
						print "</th>" ;
					print "</tr>" ;
					
					$count=0;
					$rowNum="odd" ;
					while ($row=$result->fetch()) {
						if (isset($log[$row["gibbonPersonID"]])==FALSE) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									try {
										$dataRollGroup=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
										$sqlRollGroup="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
										$resultRollGroup=$connection2->prepare($sqlRollGroup);
										$resultRollGroup->execute($dataRollGroup);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultRollGroup->rowCount()<1) {
										print "<i>" . _('Unknown') . "</i>" ;
									}
									else {
										$rowRollGroup=$resultRollGroup->fetch() ;
										print $rowRollGroup["name"] ;
									}
									
								print "</td>" ;
								print "<td>" ;
									print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
								print "</td>" ;
								print "<td>" ;
									$rowRollAttendance=NULL ;
									try {
										$dataAttendance=array("date"=>$currentDate, "gibbonPersonID"=>$row["gibbonPersonID"]); 
										$sqlAttendance="SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC";
										$resultAttendance=$connection2->prepare($sqlAttendance);
										$resultAttendance->execute($dataAttendance);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultAttendance->rowCount()<1) {
										print "<i>Not registered</i>" ;
									}
									else {
										$rowRollAttendance=$resultAttendance->fetch() ;
										print $rowRollAttendance["type"] ;
									}
								print "</td>" ;
								print "<td>" ;
									print $rowRollAttendance["reason"] ;
								print "</td>" ;
								print "<td>" ;
									print $rowRollAttendance["comment"] ;
								print "</td>" ;
							print "</tr>" ;
							
							$lastPerson=$row["gibbonPersonID"] ;
						}
					}
					if ($count==0) {
						print "<tr class=$rowNum>" ;
							print "<td colspan=5>" ;
								print _("All students are present.") ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
			}
		}
	}
}
?>