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

session_start() ;

$_SESSION[$guid]["report_student_emergencySummary.php_choices"]="" ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Students/report_student_emergencySummary.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Student Emergency Data Summary</div>" ;
	print "</div>" ;
	print "<p>" ;
	print "This report prints a summary of emergency data for the selected students. In case of emergency, please try to contact parents first, and if they cannot be reached then contact the listed emergency contacts." ;
	print "</p>" ;
	
	print "<h2>" ;
	print "Choose Students" ;
	print "</h2>" ;
	
	$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
	?>
	
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_student_emergencySummary.php"?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Students *</b><br/>
				</td>
				<td class="right">
					<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
						<optgroup label='--Students by Roll Group--'>
							<?
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
							}
							?>
						</optgroup>
						<optgroup label='--Students by Name--'>
							<?
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	$choices=$_POST["Members"] ;
	
	if (count($choices)>0) {
		$_SESSION[$guid]["report_student_emergencySummary.php_choices"]=$choices ;
		
		print "<h2>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlWhere=" AND (" ;
			for ($i=0; $i<count($choices); $i++) {
				$data[$choices[$i]]=$choices[$i] ;
				$sqlWhere=$sqlWhere . "gibbonPerson.gibbonPersonID=:" . $choices[$i] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-4) ;
			$sqlWhere=$sqlWhere . ")" ;
			$sql="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRollGroup.name AS name, emergency1Name, emergency1Number1, emergency1Number2, emergency1Relationship, emergency2Name, emergency2Number1, emergency2Number2, emergency2Relationship FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		print "<div class='linkTop'>" ;
		print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_student_emergencySummary_print.php'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
		print "</div>" ;
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print "Student" ;
				print "</th>" ;
				print "<th colspan=3>" ;
					print "Last<br/>Update" ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if (is_null($log[$row["gibbonYearGroupID"]])) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;
					
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
						print "</td>" ;
						print "<td colspan=3>" ;
							//Get details of last personal data form update
							try {
								$dataMedical=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sqlMedical="SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC" ;
								$resultMedical=$connection2->prepare($sqlMedical);
								$resultMedical->execute($dataMedical);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultMedical->rowCount()>0) {
								$rowMedical=$resultMedical->fetch() ;
								//Is last update more recent than 90 days?
								if (substr($rowMedical["timestamp"],0,10)>date("Y-m-d", (time()-(90*24*60*60)))) {
									print dateConvertBack(substr($rowMedical["timestamp"],0,10)) ;
								}
								else {
									print "<span style='color: #ff0000; font-weight: bold'>" . dateConvertBack(substr($rowMedical["timestamp"],0,10)) . "</span>" ;
								}
							}
							else {
								print "<span style='color: #ff0000; font-weight: bold'>NA</span>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					
					print "<tr class=$rowNum>" ;
						print "<td></td>" ;
						print "<td style='border-top: 1px solid #aaa; vertical-align: top'>" ;
							print "<b><i>Parents</i></b><br/>" ;
							try {
								$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sqlFamily="SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID" ;
								$resultFamily=$connection2->prepare($sqlFamily);
								$resultFamily->execute($dataFamily);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowFamily=$resultFamily->fetch()) {
								try {
									$dataFamily2=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
									$sqlFamily2="SELECT * FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
									$resultFamily2=$connection2->prepare($sqlFamily2);
									$resultFamily2->execute($dataFamily2);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowFamily2=$resultFamily2->fetch()) {
									print "<u>" . formatName($rowFamily2["title"], $rowFamily2["preferredName"], $rowFamily2["surname"], "Parent") . "</u><br/>" ;
									$numbers=0 ;
									for ($i=1; $i<5; $i++) {
										if ($rowFamily2["phone" . $i]!="") {
											if ($rowFamily2["phone" . $i . "Type"]!="") {
												print "<i>" . $rowFamily2["phone" . $i . "Type"] . ":</i> " ;
											}
											if ($rowFamily2["phone" . $i . "CountryCode"]!="") {
												print "+" . $rowFamily2["phone" . $i . "CountryCode"] . " " ;
											}
											print $rowFamily2["phone" . $i] . "<br/>" ;
											$numbers++ ;
										}
									}
									if ($numbers==0) {
										print "<span style='font-size: 85%; font-style: italic'>No number available.</span><br/>" ;
									}
								}
							}
						print "</td>" ;
						print "<td style='border-top: 1px solid #aaa; vertical-align: top'>" ;
							print "<b><i>Emergency Contact 1</i></b><br/>" ;
							print "<u><i>Name</i></u>: " . $row["emergency1Name"] . "<br/>" ;
							print "<u><i>Number</i></u>: " . $row["emergency1Number1"] . "<br/>" ;
							if ($row["emergency1Number2"]!=="") {
								print "<u><i>Number 2</i></u>: " . $row["emergency1Number2"] . "<br/>" ;
							}
							if ($row["emergency1Relationship"]!=="") {
								print "<u><i>Relationship</i></u>: " . $row["emergency1Relationship"] . "<br/>" ;
							}
						print "</td>" ;
						print "<td style='border-top: 1px solid #aaa; vertical-align: top'>" ;
							print "<b><i>Emergency Contact 2</i></b><br/>" ;
							print "<u><i>Name</i></u>: " . $row["emergency2Name"] . "<br/>" ;
							print "<u><i>Number</i></u>: " . $row["emergency2Number1"] . "<br/>" ;
							if ($row["emergency2Number2"]!=="") {
								print "<u><i>Number 2</i></u>: " . $row["emergency2Number2"] . "<br/>" ;
							}
							if ($row["emergency2Relationship"]!=="") {
								print "<u><i>Relationship</i></u>: " . $row["emergency2Relationship"] . "<br/>" ;
							}
						print "</td>" ;
					print "</tr>" ;
				}
			}
			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=2>" ;
						print "There are no results in this report." ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>