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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_byRollGroup_print.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	
	//Proceed!
	print "<h2 class='top'>" ;
	print "Students by Roll Group" ;
	print "</h2>" ;
	
	if ($gibbonRollGroupID!="") {
		if ($gibbonRollGroupID!="*") {
			try {
				$data=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
				$sql="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()==1) {
				$row=$result->fetch() ;
				print "<p style='margin-bottom: 0px'><b>Roll Group</b>: " . $row["name"] . "</p>" ;
				
				//Show Tutors
				try {
					$dataDetail=array("gibbonPersonIDTutor"=>$row["gibbonPersonIDTutor"], "gibbonPersonIDTutor2"=>$row["gibbonPersonIDTutor2"], "gibbonPersonIDTutor3"=>$row["gibbonPersonIDTutor3"]); 
					$sqlDetail="SELECT title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3" ;
					$resultDetail=$connection2->prepare($sqlDetail);
					$resultDetail->execute($dataDetail);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($resultDetail->rowCount()>0) {
					$tutorCount=0 ;
					print "<p style=''><b>Tutors</b>: " ;
					while ($rowDetail=$resultDetail->fetch()) {
						print formatName($rowDetail["title"], $rowDetail["preferredName"], $rowDetail["surname"], "Staff") ;
						$tutorCount++ ;
						if ($tutorCount<$resultDetail->rowCount()) {
							print ", " ;
						}
					}
					print "</p>" ; 
				}
			}
		}
			
			
		try {
			if ($gibbonRollGroupID=="*") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonRollGroup.nameShort, surname, preferredName" ;
			}
			else {
				$data=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
				$sql="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<div class='linkTop'>" ;
		print "<a href='javascript:window.print()'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
		print "</div>" ;
	
		print "<table style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print "Roll Group" ;
				print "</th>" ;
				print "<th>" ;
					print "Student" ;
				print "</th>" ;
				print "<th>" ;
					print "Gender" ;
				print "</th>" ;
				print "<th>" ;
					print "Age<br/>" ;
					print "<span style='font-style: italic; font-size: 85%'>DOB</span>" ;
				print "</th>" ;
				print "<th>" ;
					print "Nationality<br/>" ;
				print "</th>" ;
				print "<th>" ;
					print "Transport<br/>" ;
				print "</th>" ;
				print "<th>" ;
					print "House<br/>" ;
				print "</th>" ;
				print "<th>" ;
					print "Locker<br/>" ;
				print "</th>" ;
				print "<th>" ;
					print "Medical<br/>" ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if (is_null($log[$row["gibbonRollGroupID"]])) {
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
							print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
						print "</td>" ;
						print "<td>" ;
							print $row["gender"] ;
						print "</td>" ;
						print "<td>" ;
							if (is_null($row["dob"])==FALSE AND $row["dob"]!="0000-00-00") {
								print getAge(dateConvertToTimestamp($row["dob"]), TRUE) . "<br/>" ;
								print "<span style='font-style: italic; font-size: 85%'>" . dateConvertBack($row["dob"]) . "</span>" ;
							}
						print "</td>" ;
						print "<td>" ;
							if ($row["citizenship1"]!="") {
								print $row["citizenship1"] . "<br/>" ;
							}
							if ($row["citizenship2"]!="") {
								print $row["citizenship2"] . "<br/>" ;
							}
						print "</td>" ;
						print "<td>" ;
							print $row["transport"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["gibbonHouseID"]!="") {
								try {
									$dataHouse=array("gibbonHouseID"=>$row["gibbonHouseID"]); 
									$sqlHouse="SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID" ;
									$resultHouse=$connection2->prepare($sqlHouse);
									$resultHouse->execute($dataHouse);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultHouse->rowCount()==1) {
									$rowHouse=$resultHouse->fetch() ;
									print $rowHouse["name"] ;
								}
							}
						print "</td>" ;
						print "<td>" ;
							print $row["lockerNumber"] ;
						print "</td>" ;
						print "<td>" ;
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
								if ($rowForm["longTermMedication"]=='Y') {
									print "<b><i>Long Term Medication</i></b>: " . $rowForm["longTermMedicationDetails"] . "<br/>" ;
								}
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
									print "<b><i>Condition $condCount</i></b> " ;
									print ": " . $rowConditions["name"] ;
									
									$alert=getAlert($connection2, $rowConditions["gibbonAlertLevelID"]) ;
									if ($alert!=FALSE) {
										print " <span style='color: #" . $alert["color"] . "; font-weight: bold'>(" . $alert["name"] . " Risk)</span>" ;
										print "<br/>" ;									
										$condCount++ ;
									}
								}
							}
							else {
								print "<i>No medical data</i>" ;
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