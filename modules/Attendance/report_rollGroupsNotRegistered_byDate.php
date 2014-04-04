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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_rollGroupsNotRegistered_byDate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Roll Groups Not Registered</div>" ;
	print "</div>" ;
	print "<h2>" ;
	print "Choose Date" ;
	print "</h2>" ;
	
	
	if (isset($_GET["currentDate"])==FALSE) {
	 	$currentDate=date("Y-m-d");
	}
	else {
		$currentDate=dateConvert($guid, $_GET["currentDate"]) ;	 
	}
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Date *</b><br/>
					<span style="font-size: 90%"><i><? print $_SESSION[$guid]["i18n"]["dateFormat"]  ?></i></span>
				</td>
				<td class="right">
					<input name="currentDate" id="currentDate" maxlength=10 value="<? print dateConvertBack($guid, $currentDate) ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var date=new LiveValidation('date');
						date.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
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
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_rollGroupsNotRegistered_byDate.php">
					<input type="submit" value="<? print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($currentDate!="") {
		print "<h2>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		//Produce array of attendance data
		try {
			$data=array("date"=>$currentDate); 
			$sql="SELECT gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date=:date" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		$log=array() ;
		while ($row=$result->fetch()) {
			$log[$row["gibbonRollGroupID"]]=TRUE ;
		}
	
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"] ); 
			$sql="SELECT gibbonRollGroupID, name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print "There is no data to display." ;
			print "</div>" ;
		}
		else {
			print "<div class='linkTop'>" ;
			print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_rollGroupsNotRegistered_byDate_print.php&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Roll Group" ;
					print "</th>" ;
					print "<th>" ;
						print "Tutor" ;
					print "</th>" ;
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if (isset($log[$row["gibbonRollGroupID"]])==FALSE) {
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
								print $row["name"] ;
							print "</td>" ;
							print "<td>" ;
								if ($row["gibbonPersonIDTutor"]=="" AND $row["gibbonPersonIDTutor2"]=="" AND $row["gibbonPersonIDTutor3"]=="") {
									print "<i>Not set</i>" ;
								}
								else {
									try {
										$dataTutor=array("gibbonPersonID1"=>$row["gibbonPersonIDTutor"], "gibbonPersonID2"=>$row["gibbonPersonIDTutor2"], "gibbonPersonID3"=>$row["gibbonPersonIDTutor3"]); 
										$sqlTutor="SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3" ;
										$resultTutor=$connection2->prepare($sqlTutor);
										$resultTutor->execute($dataTutor);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									while ($rowTutor=$resultTutor->fetch()) {
										print formatName("", $rowTutor["preferredName"], $rowTutor["surname"], "Staff", true, true) . "<br/>" ;
									}
								}
							print "</td>" ;
						print "</tr>" ;
					}
				}
				if ($count==0) {
					print "<tr class=$rowNum>" ;
						print "<td colspan=2>" ;
							print "All roll groups have been registered." ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
}
?>