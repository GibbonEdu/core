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

include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

session_start() ;

$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$gibbonMarkbookColumnID=$_SESSION[$guid]["exportToExcelParams"] ;
if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$alert=getAlert($connection2, 002) ;
	
	//Proceed!
	print "<table cellspacing='0'>" ;
		print "<tr>" ;
			print "<td colspan=4>" ;
				print "<h1 style='margin-bottom: 20px'>" ;
				print "Markbook Data" ;
				print "</h1>" ;
			print "</td>" ;
		print "</tr>" ;
	
		try {
			$dataStudents=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sqlStudents="SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
			$resultStudents=$connection2->prepare($sqlStudents);
			$resultStudents->execute($dataStudents);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultStudents->rowCount()<1) {
			print "<div class='error'>" ;
				print "There are no students in this class" ;
			print "</div>" ;
		}
		else {
			print "<tr>" ;
				print "<td>" ;
					print "<b>Student</b>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>Attainment</b>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>Effort</b>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>Comment</b>" ;
				print "</td>" ;
			print "</tr>" ;
		
		
			while ($rowStudents=$resultStudents->fetch()) {
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
						print formatName("", $rowStudents["preferredName"], $rowStudents["surname"], "Student", true) ;
					print "</td>" ;
					
					try {
						$dataEntry=array("gibbonMarkbookColumnID"=>$gibbonMarkbookColumnID, "gibbonPersonIDStudent"=>$rowStudents["gibbonPersonID"]); 
						$sqlEntry="SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent" ;
						$resultEntry=$connection2->prepare($sqlEntry);
						$resultEntry->execute($dataEntry);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultEntry->rowCount()==1) {
						$rowEntry=$resultEntry->fetch() ;
						$styleAttainment="" ;
						if ($rowEntry["attainmentConcern"]=="Y") {
							$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
						}
						print "<td style='text-align: center'>" ;
						$attainment=$rowEntry["attainmentValue"] ;
						if ($rowEntry["attainmentValue"]=="Complete") {
							$attainment="CO" ;
						}
						else if ($rowEntry["attainmentValue"]=="Incomplete") {
							$attainment="IC" ;
						}
						print "<span $styleAttainment title='" . htmlPrep($rowEntry["attainmentDescriptor"]) . "'>$attainment</span>" ;
						print "</td>" ;
						$styleEffort="" ;
						if ($rowEntry["effortConcern"]=="Y") {
							$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
						}
						$effort=$rowEntry["effortValue"] ;
						if ($rowEntry["effortValue"]=="Complete") {
							$effort="CO" ;
						}
						else if ($rowEntry["effortValue"]=="Incomplete") {
							$effort="IC" ;
						}
						print "<td style='text-align: center;$color'>" ;
						print "<span $styleEffort title='" . htmlPrep($rowEntry["effortDescriptor"]) . "'>$effort</span>" ;
						print "</td>" ;
						print "<td style='text-align: center;$color'>" ;
						$style="" ;
						if ($rowEntry["comment"]!="") {
							print "<span $style title='" . htmlPrep($rowEntry["comment"]) . "'>" . substr($rowEntry["comment"], 0, 10) . "...</span>" ;
						}
						print "</td>" ;
					}
					else {
						print "<td colspan=3>" ;
							print "No data." ;
						print "</td>" ;
					}
				print "</tr>" ;
			}
		}
	print "</table>" ;
}

$_SESSION[$guid]["exportToExcelParams"]="" ;
?>