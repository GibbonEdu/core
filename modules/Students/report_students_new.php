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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_new")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>New Students</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print "Choose Options" ;
	print "</h2>" ;
	
	$type=$_GET["type"] ;
	$ignoreEnrolment=$_GET["ignoreEnrolment"] ;
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<!-- FIELDS & CONTROLS FOR TYPE -->
			<script type="text/javascript">
				$(document).ready(function(){
					$("#type").change(function(){
						if ($('select.type option:selected').val() == "Date Range" ) {
							$("#startDateFromRow").slideDown("fast", $("#startDateFromRow").css("display","table-row")); 
							$("#startDateToRow").slideDown("fast", $("#startDateToRow").css("display","table-row")); 
						} else {
							$("#startDateFromRow").css("display","none");
							$("#startDateToRow").css("display","none");
						} 
					 });
				});
			</script>
			<tr>
				<td> 
					<b>Type *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="type" id="type" class="type">
						<?
						print "<option" ; if ($type=="Current School Year") { print " selected" ; } print " value='Current School Year'>Current School Year</option>" ;
						print "<option" ; if ($type=="Date Range") { print " selected" ; } print " value='Date Range'>Date Range</option>" ;
						?>
					</select>
				</td>
			</tr>
			<tr id='startDateFromRow' <? if ($type!="Date Range") { print "style='display: none'" ; } ?>>
				<td> 
					<b>From Date</b><br/>
					<span style="font-size: 90%"><i>Earlest student start date to include.<br/>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="startDateFrom" id="startDateFrom" maxlength=10 value="<? print $_GET["startDateFrom"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var startDateFrom=new LiveValidation('startDateFrom');
						startDateFrom.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#startDateFrom" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr id='startDateToRow' <? if ($type!="Date Range") { print "style='display: none'" ; } ?>>
				<td> 
					<b>To Date</b><br/>
					<span style="font-size: 90%"><i>Latest student start date to include.<br/>dd/mm/yyyy</i></span>
				</td>
				<td class="right">
					<input name="startDateTo" id="startDateTo" maxlength=10 value="<? print $_GET["startDateTo"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var startDateTo=new LiveValidation('startDateTo');
						startDateTo.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#startDateTo" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Ignore Enrolment</b><br/>
					<span style="font-size: 90%"><i>This is useful for picking up students who are set to Full, have a start date but are not yet enrolled.</span>
				</td>
				<td class="right">
					<input <? if ($ignoreEnrolment=="on") { print "checked" ; } ?> name="ignoreEnrolment" id="ignoreEnrolment" type="checkbox">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_students_new.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($type!="") {
		print "<h2>" ;
		print "Results" ;
		print "</h2>" ;
		
		$proceed=TRUE ;
		if ($type=="Date Range") {
			print "<p>" ;
				print "This report shows all students whose Start Date is on or between the indicated dates." ;
			print "</p>" ;
			
			$startDateFrom=$_GET["startDateFrom"] ;
			$startDateTo=$_GET["startDateTo"] ;
			if ($startDateFrom=="" OR $startDateTo=="") {
				$proceed=FALSE ;
			}
		}
		else if ($type=="Current School Year") {
			print "<p>" ;
				print "This report shows all students who are newly arrived in the school during the current academic year (e.g. they were not enroled in the previous academic year)." ;
			print "</p>" ;
		}
	
		
		if ($proceed==FALSE) {
			print "<div class='error'>" ;
				print "This report cannot be generated due to a problem with the options you have selected" ;
			print "</div>" ;
		}
		else {
			try {
				if ($type=="Date Range") {
					if ($ignoreEnrolment!="on") {
						$data=array("startDateFrom"=>dateConvert($startDateFrom), "startDateTo"=>dateConvert($startDateTo)); 
						$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName" ;
					}
					else {
						$data=array("startDateFrom"=>dateConvert($startDateFrom), "startDateTo"=>dateConvert($startDateTo)); 
						$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool FROM gibbonPerson WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName" ;
					}
				}
				else if ($type=="Current School Year") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username, dateStart, lastSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY rollGroup, surname, preferredName" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data); 
			}
			catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
			if ($result->rowCount()>0) {
				if ($type=="Current School Year") {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Count" ;
							print "</th>" ;
							print "<th>" ;
								print "Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Roll Group" ;
							print "</th>" ;
							print "<th>" ;
								print "Username" ;
							print "</th>" ;
							print "<th>" ;
								print "Start Date" ;
							print "</th>" ;
							print "<th>" ;
								print "Last School" ;
							print "</th>" ;
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
							try {
								$data2=array("gibbonSchoolYearID"=>getPreviousSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2), "gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sql2="SELECT surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY rollGroup, surname, preferredName" ;
								$result2=$connection2->prepare($sql2);
								$result2->execute($data2); 
							}
							catch(PDOException $e) { print "<div class='error'>" . $e->getMessage() . "</div>" ; }
							if ($result2->rowCount()==0) {
								$count++ ;
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print $count ;
									print "</td>" ;
									print "<td>" ;
										print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) ;
									print "</td>" ;
									print "<td>" ;
										print $row["rollGroup"] ;
									print "</td>" ;
									print "<td>" ;
										print $row["username"] ;
									print "</td>" ;
									print "<td>" ;
										print dateConvertBack($row["dateStart"]) ;
									print "</td>" ;
									print "<td>" ;
										print $row["lastSchool"] ;
									print "</td>" ;
								print "</tr>" ;
							}
						}
					print "</table>" ; 			
				}
				else if ($type=="Date Range") {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Count" ;
							print "</th>" ;
							print "<th>" ;
								print "Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Username" ;
							print "</th>" ;
							print "<th>" ;
								print "Start Date" ;
							print "</th>" ;
							print "<th>" ;
								print "Last School" ;
							print "</th>" ;
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
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print $count ;
								print "</td>" ;
								print "<td>" ;
									print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) ;
								print "</td>" ;
								print "<td>" ;
									print $row["username"] ;
								print "</td>" ;
								print "<td>" ;
									print dateConvertBack($row["dateStart"]) ;
								print "</td>" ;
								print "<td>" ;
									print $row["lastSchool"] ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ; 		
				}
			}
			else {
				print "<div class='warning'>" ;
					print "There are no records in this report." ;
				print "</div>" ;
			}
		}
	}
}
?>