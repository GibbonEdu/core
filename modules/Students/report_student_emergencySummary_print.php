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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_student_emergencySummary_print.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$choices=$_SESSION[$guid]["report_student_emergencySummary.php_choices"] ;
	
	if (count($choices)>0) {
		print "<h2>" ;
		print "Student Emergency Data Summary" ;
		print "</h2>" ;
		print "<p>" ;
		print "This report prints a summary of emergency data for the selected students. In case of emergency, please try to contact parents first, and if they cannot be reached then contact the listed emergency contacts." ;
		print "</p>" ;
		
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
		print "<a href='javascript:window.print()'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
		print "</div>" ;

		print "<table class='mini' cellspacing='0' style='width: 100%'>" ;
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
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$count++ ;
				
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
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
								$sqlFamily2="SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
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