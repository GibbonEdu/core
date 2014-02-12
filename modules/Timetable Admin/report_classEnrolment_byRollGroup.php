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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/report_classEnrolment_byRollGroup.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Class Enrolment by Roll Group</div>" ;
	print "</div>" ;
	
	print "<h2>" ;
	print "Choose Roll Group" ;
	print "</h2>" ;
	
	$gibbonRollGroupID="" ;
	if (isset($_GET["gibbonRollGroupID"])) {
		$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	}
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Roll Group *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonRollGroupID">
						<?
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 	}
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
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_classEnrolment_byRollGroup.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($gibbonRollGroupID!="") {
		print "<h2>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
			$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
			
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print "Roll Group" ;
				print "</th>" ;
				print "<th>" ;
					print "Student" ;
				print "</th>" ;
				print "<th>" ;
					print "Class Count" ;
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
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["name"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=" . $row["gibbonPersonID"] . "'>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</a>" ;
					print "</td>" ;
					print "<td>" ;
						try {
							$dataCount=array("gibbonPersonID"=>$row["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlCount="SELECT * FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) WHERE gibbonPersonID=:gibbonPersonID AND role='Student' AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
							$resultCount=$connection2->prepare($sqlCount);
							$resultCount->execute($dataCount);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultCount->rowCount()>=0) {
							print $resultCount->rowCount();
						}
						else {
							print "<i>NA</i>" ;
						}
					print "</td>" ;
				print "</tr>" ;
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=2>" ;
						print "There are no records to display." ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>