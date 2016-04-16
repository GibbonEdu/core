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

$makeUnitsPublic=getSettingByScope($connection2, "Planner", "makeUnitsPublic" ) ; 
if ($makeUnitsPublic!="Y") {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > </div><div class='trailEnd'>" . __($guid, 'Learn With Us') . "</div>" ;
	print "</div>" ;
	
	$gibbonSchoolYearID="" ;
	if (isset($_GET["gibbonSchoolYearID"])) {
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	}
	if ($gibbonSchoolYearID=="") {
		try {
			$data=array(); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE status='Current'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
			
	print "<h2>" ;
		print $gibbonSchoolYearName ;
	print "</h2>" ;
	
	print "<div class='linkTop'>" ;
		//Print year picker
		if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_public.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Previous Year') . "</a> " ;
		}
		else {
			print __($guid, "Previous Year") . " " ;
		}
		print " | " ;
		if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_public.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Next Year') . "</a> " ;
		}
		else {
			print __($guid, "Next Year") . " " ;
		}
	print "</div>" ;
	
	//Fetch units
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
		$sql="SELECT gibbonUnitID, gibbonUnit.gibbonCourseID, nameShort, gibbonUnit.name, gibbonUnit.description, gibbonCourse.name AS course FROM gibbonUnit JOIN gibbonCourse ON gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND sharedPublic='Y' ORDER BY course, name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<div class='linkTop'></div>" ;

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 150px'>" ;
					print __($guid, "Course") ;
				print "</th>" ;
				print "<th style='width: 150px'>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th style='width: 450px'>" ;
					print __($guid, "Description") ;
				print "</th>" ;
				print "<th style='width: 50px'>" ;
					print __($guid, "Actions") ;
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
			
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["course"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["name"] ;
					print "</td>" ;
					print "<td style='max-width: 270px'>" ;
						print $row["description"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_public_view.php&gibbonUnitID=" . $row["gibbonUnitID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&sidebar=false'><img title='" . __($guid, 'View Details') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>" ;
					print "</td>" ;
				print "</tr>" ;
			
				$count++ ;
			}
		print "</table>" ;
	}
}		
?>