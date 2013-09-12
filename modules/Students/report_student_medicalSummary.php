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

$_SESSION[$guid]["report_student_medicalSummary.php_choices"]="" ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Students/report_student_medicalSummary.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Student Medical Data Summary</div>" ;
	print "</div>" ;
	print "<p>" ;
	print "This report prints a summary of medical data for the selected students." ;
	print "</p>" ;
	
	print "<h2 class='top'>" ;
	print "Choose Students" ;
	print "</h2>" ;
	
	$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
	?>
	
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_student_medicalSummary.php"?>">
		<table style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
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
		$_SESSION[$guid]["report_student_medicalSummary.php_choices"]=$choices ;
		
		print "<h2 class='top'>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sqlWhere=" AND (" ;
			for ($i=0; $i<count($choices); $i++) {
				$data[$choices[$i]]=$choices[$i];
				$sqlWhere=$sqlWhere . "gibbonPerson.gibbonPersonID=:" . $choices[$i] . " OR " ;
			}
			$sqlWhere=substr($sqlWhere,0,-4) ;
			$sqlWhere=$sqlWhere . ")" ;
			$sql="SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonRollGroup.name AS name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID $sqlWhere ORDER BY surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<div class='linkTop'>" ;
		print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_student_medicalSummary_print.php'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
		print "</div>" ;
	
		print "<table style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print "Student" ;
				print "</th>" ;
				print "<th>" ;
					print "Medical<br/>Form?" ;
				print "</th>" ;
				print "<th>" ;
					print "Blood<br/>Type" ;
				print "</th>" ;
				print "<th>" ;
					print "Tetanus<br/>" ;
					print "<span style='font-size: 80%'><i>10 Years</i></span>" ;
				print "</th>" ;
				print "<th>" ;
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
					
					try {
						$dataForm=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
						$sqlForm="SELECT * FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultForm=$connection2->prepare($sqlForm);
						$resultForm->execute($dataForm);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultForm->rowCount()==1) {
						$rowForm=$resultForm->fetch() ;
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
							print "</td>" ;
							print "<td>" ;
								print "Yes" ;
							print "</td>" ;
							print "<td>" ;
								print $rowForm["bloodType"] ;
							print "</td>" ;
							print "<td>" ;
								print $rowForm["tetanusWithin10Years"] ;
							print "</td>" ;
							print "<td>" ;
								//Get details of last medical form update
								try {
									$dataMedical=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
									$sqlMedical="SELECT * FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=:gibbonPersonID AND status='Complete' ORDER BY timestamp DESC" ;
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
						
						//Long term medication
						if ($rowForm["longTermMedication"]=='Y') {
							print "<tr class=$rowNum>" ;
								print "<td></td>" ;
								print "<td colspan=4 style='border-top: 1px solid #aaa'>" ;
									print "<b><i>Long Term Medication</i></b>: " . $rowForm["longTermMedication"] . "<br/>" ;
									print "<u><i>Details</i></u>: " . $rowForm["longTermMedicationDetails"] . "<br/>" ;
								print "</td>" ;
							print "</tr>" ;
						}
						
						//Conditions
						$condCount=1 ;
						try {
							$dataConditions=array("gibbonPersonMedicalID"=>$rowForm["gibbonPersonMedicalID"]); 
							$sqlConditions="SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID" ;
							$resultConditions=$connection2->prepare($sqlConditions);
							$resultConditions->execute($dataConditions);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						while ($rowConditions=$resultConditions->fetch()) {
							$alert=getAlert($connection2, $rowConditions["gibbonAlertLevelID"]) ;
							if ($alert!=FALSE) {
								$conditionStyle="style='border-top: 2px solid #" . $alert["color"] . "'" ;
								print "<tr class=$rowNum>" ;
									print "<td></td>" ;
									print "<td colspan=4 $conditionStyle>" ;
										print "<b><i>Condition $condCount</i></b>: " . $rowConditions["name"] . "<br/>" ;
										print "<u><i>Risk</i></u>: <span style='color: #" . $alert["color"] . "; font-weight: bold'>" . $alert["name"] . "</span><br/>" ;
										if ($rowConditions["triggers"]!="") {
											print "<u><i>Triggers</i></u>: " . $rowConditions["triggers"] . "<br/>" ;
										}
										if ($rowConditions["reaction"]!="") {
											print "<u><i>Reaction</i></u>: " . $rowConditions["reaction"] . "<br/>" ;
										}
										if ($rowConditions["response"]!="") {
											print "<u><i>Response</i></u>: " . $rowConditions["response"] . "<br/>" ;
										}
										if ($rowConditions["medication"]!="") {
											print "<u><i>Medication</i></u>: " . $rowConditions["medication"] . "<br/>" ;
										}
										if ($rowConditions["lastEpisode"]!="" OR $rowConditions["lastEpisodeTreatment"]!="") {
												print "<u><i>Last Episode</i></u>: " ;
											if ($rowConditions["lastEpisode"]!="") {
												 print dateConvertBack($rowConditions["lastEpisode"]) ;
											}
											if ($rowConditions["lastEpisodeTreatment"]!="") {
												if ($rowConditions["lastEpisode"]!="") {
													print " | " ;
												}
												print $rowConditions["lastEpisodeTreatment"] ;
											}
											print "<br/>" ;
										}
										
										if ($rowConditions["comment"]!="") {
											print "<u><i>Comment</i></u>: " . $rowConditions["comment"] . "<br/>" ;
										}
									print "</td>" ;
								print "</tr>" ;
								$condCount++ ;
							}
						}
					}
					else {
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
							print "</td>" ;
							print "<td colspan=4>" ;
								print "<span style='color: #ff0000; font-weight: bold'>No</span>" ;
							print "</td>" ;
						print "</tr>" ;
					}
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