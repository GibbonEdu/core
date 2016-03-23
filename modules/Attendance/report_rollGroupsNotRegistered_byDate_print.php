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

if (isActionAccessible($guid, $connection2, "/modules/Attendance/report_rollGroupsNotRegistered_byDate_print.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	if (isset($_GET["dateStart"])==FALSE) {
	 	$dateStart=date("Y-m-d");
	}
	else {
		$dateStart=dateConvert($guid, $_GET["dateStart"]) ;	 
	}
	if (isset($_GET["dateEnd"])==FALSE) {
	 	$dateEnd=date("Y-m-d");
	}
	else {
		$dateEnd=dateConvert($guid, $_GET["dateEnd"]) ;	 
	}
	
	//Proceed!
	print "<h2>" ;
		if ($dateStart!=$dateEnd) {
			print __($guid, "Roll Groups Not Registered") . ", " . dateConvertBack($guid, $dateStart) . "-" . dateConvertBack($guid, $dateEnd) ;
		}
		else {
			print __($guid, "Roll Groups Not Registered") . ", " . dateConvertBack($guid, $dateStart) ;
		}
	print "</h2>" ;
	
	//Produce array of attendance data
	try {
		$data=array("dateStart"=>$dateStart, "dateEnd"=>$dateEnd); 
		$sql="SELECT date, gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	$log=array() ;
	while ($row=$result->fetch()) {
		$log[$row["date"]][$row["gibbonRollGroupID"]]=TRUE ;
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
			print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		//Produce array of roll groups
		$rollGroups=$result->fetchAll() ;
	
		print "<div class='linkTop'>" ;
		print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_rollGroupsNotRegistered_byDate_print.php&dateStart=" . dateConvertBack($guid, $dateStart) . "'>" .  __($guid, 'Print') . "<img style='margin-left: 5px' title='" . __($guid, 'Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
		print "</div>" ;
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Roll Group") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Date") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Tutor") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			
			//Loop through each date
			$timestampStart=dateConvertToTimestamp($dateStart) ;
			$timestampEnd=dateConvertToTimestamp($dateEnd) ;
			for ($i=$timestampStart; $i<=$timestampEnd; $i=($i+(60*60*24))) {
				if (isSchoolOpen($guid, date("Y-m-d", $i), $connection2, TRUE)) {
					//Loop through each roll group
					foreach ($rollGroups AS $row) {
						//Output row only if not registered on specified date
						if (isset($log[date("Y-m-d", $i)][$row["gibbonRollGroupID"]])==FALSE) {
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
									print dateConvertBack($guid, date("Y-m-d", $i)) ;
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
				}
			}					

			if ($count==0) {
				print "<tr class=$rowNum>" ;
					print "<td colspan=2>" ;
						print __($guid, "All roll groups have been registered.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}
?>