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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_studentsNotOnsite_byDate_print.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	if ($_GET["currentDate"]=="") {
	 	$currentDate=date("Y-m-d");
	}
	else {
		$currentDate=dateConvert($guid, $_GET["currentDate"]) ;	 
	}
	
	//Proceed!
	print "<h2>" ;
	print __($guid, "Students Not Onsite") . ", " . dateConvertBack($guid, $currentDate) ;
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
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		$log=array() ;
		$currentStudent="" ;
		$lastStudent="" ;
		while ($row=$result->fetch()) {
			$currentStudent=$row["gibbonPersonID"] ;
			if (($row["type"]=="Present" OR $row["type"]=="Present - Late") AND $currentStudent!=$lastStudent) {
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
				print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			print "<div class='linkTop'>" ;
			print "<a href='javascript:window.print()'>" .  __($guid, 'Print') . "<img style='margin-left: 5px' title='" . __($guid, 'Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
		
			$lastPerson="" ;
			
			print "<table class='mini' cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Roll Group") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Name") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Status") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Reason") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Comment") ;
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
									print "<i>" . __($guid, 'Unknown') . "</i>" ;
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
							print __($guid, "All students are present.") ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
}
?>