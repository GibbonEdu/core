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

//Get alternative header names
$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;
$effortAlternativeName=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
$effortAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "effortAlternativeNameAbrev") ;

@session_start() ;

$gibbonCourseClassID=$_SESSION[$guid]["exportToExcelParams"] ;
if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$alert=getAlert($connection2, 002) ;
	
	//Count number of columns
	try {
		$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
		$sql="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	$columns=$result->rowCount() ;
	if ($columns<1) {
		print "<div class='warning'>" ;
			print __($guid, "There are no records to display.") ;
		print "</div>" ;	
	}
	else {
		//Print table header
		print "<table class='mini' cellspacing='0' style='width: 100%; margin-top: 0px'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 150px; max-width: 200px'rowspan=2>" ;
					print __($guid, "Student") ;
				print "</th>" ;
			
				$span=3 ;
				$columnID=array() ;
				$attainmentID=array() ;
				$effortID=array() ;
				for ($i=0;$i<$columns;$i++) {
					$row=$result->fetch() ;
					if ($row===FALSE) {
						$columnID[$i]=FALSE ;
					}
					else {
						$columnID[$i]=$row["gibbonMarkbookColumnID"];
						$attainmentID[$i]=$row["gibbonScaleIDAttainment"];
						$effortID[$i]=$row["gibbonScaleIDEffort"];
						$gibbonPlannerEntryID[$i]=$row["gibbonPlannerEntryID"] ;
						$gibbonRubricIDAttainment[$i]=$row["gibbonRubricIDAttainment"] ;
						$gibbonRubricIDEffort[$i]=$row["gibbonRubricIDEffort"] ;
				
					}
				
					if ($columnID[$i]==FALSE) {
						print "<th style='text-align: center' colspan=$span>" ;
					
						print "</th>" ;
					}
					else {
						print "<th style='text-align: center' colspan=$span>" ;
							print $row["name"] ; 
						print "</th>" ;
					}
				}
			print "</tr>" ;
		
			print "<tr class='head'>" ;
				for ($i=0;$i<$columns;$i++) {
					if ($columnID[$i]==FALSE) {
						print "<th style='text-align: center' colspan=$span>" ;
					
						print "</th>" ;
					}
					else {
						print "<th style='border-left: 2px solid #666; text-align: center; width: 40px'>" ;
							print "<b>" ; if ($attainmentAlternativeNameAbrev!="") { print $attainmentAlternativeNameAbrev ; } else { print __($guid, 'Att') ; } print "</b>" ;
						print "</th>" ;
						print "<th style='text-align: center; width: 40px'>" ;
							print "<b>" ; if ($effortAlternativeNameAbrev!="") { print $effortAlternativeNameAbrev ; } else { print __($guid, 'Eff') ; } print "</b>" ;
						print "</th>" ;
						print "<th style='text-align: center; width: 80px'>" ;
							print "<span title='" . __($guid, 'Comment') . "'>" . __($guid, 'Com') . "</span>" ;
						print "</th>" ;
					}
				}
			print "</tr>" ;
	
		$count=0;
		$rowNum="odd" ;
	
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
			print "<tr>" ;
				print "<td colspan=" . ($columns+1) . ">" ;
					print "<i>" . __($guid, 'There are no records to display.') . "</i>" ;
				print "</td>" ;
			print "</tr>" ;
		}
		else {
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
				
					for ($i=0;$i<$columns;$i++) {
						$row=$result->fetch() ;
							try {
								$dataEntry=array("gibbonMarkbookColumnID"=>$columnID[($i)], "gibbonPersonIDStudent"=>$rowStudents["gibbonPersonID"]); 
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
								else if ($rowEntry["attainmentConcern"]=="P") {
									$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
								}
								print "<td style='border-left: 2px solid #666; text-align: center'>" ;
									$attainment="" ;
									if ($rowEntry["attainmentValue"]!="") {
										$attainment=__($guid, $rowEntry["attainmentValue"]) ;
									}
									if ($rowEntry["attainmentValue"]=="Complete") {
										$attainment=__($guid, "Com") ;
									}
									else if ($rowEntry["attainmentValue"]=="Incomplete") {
										$attainment=__($guid, "Inc") ;
									}
									print "<div $styleAttainment title='" . htmlPrep($rowEntry["attainmentDescriptor"]) . "'>$attainment" ;
									print "</div>" ;
								print "</td>" ;
								$styleEffort="" ;
								if ($rowEntry["effortConcern"]=="Y") {
									$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
								}
								$effort="" ;
								if ($rowEntry["effortValue"]!="") {
									$effort=__($guid, $rowEntry["effortValue"]) ;
								}
								if ($rowEntry["effortValue"]=="Complete") {
									$effort=__($guid, "Com") ;
								}
								else if ($rowEntry["effortValue"]=="Incomplete") {
									$effort=__($guid, "Inc") ;
								}
								print "<td style='text-align: center;'>" ;
									print "<div $styleEffort title='" . htmlPrep($rowEntry["effortDescriptor"]) . "'>$effort" ;
									print "</div>" ;
								print "</td>" ;
									print "<td style='text-align: center;'>" ;
									$style="" ;
									if ($rowEntry["comment"]!="") {
										print "<span $style title='" . htmlPrep($rowEntry["comment"]) . "'>" . substr($rowEntry["comment"], 0, 10) . "...</span>" ;
									}
								print "</td>" ;
							}
							else {
								print "<td style='text-align: center' colspan=$span>" ;
								print "</td>" ;
							}
					}
				print "</tr>" ;
			}
		print "</table>" ;
		}
	}
		
}

$_SESSION[$guid]["exportToExcelParams"]="" ;
?>