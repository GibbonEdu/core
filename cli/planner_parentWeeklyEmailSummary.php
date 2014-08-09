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

require getcwd() . "/../config.php" ;
require getcwd() . "/../functions.php" ;

if ($databaseServer=="localhost") {
	$databaseServer="127.0.0.1" ;
}
//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
	echo $e->getMessage() . "\n\n";
}

@session_start() ;

getSystemSettings($guid, $connection2) ;

setCurrentSchoolYear($guid, $connection2) ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name()!="cli") { 
	print _("This script cannot be run from a browser, only via CLI.") . "\n\n" ;
}
else {
	//Get list of all current students
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name, 'Student' AS role FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { 
		 echo $e->getMessage() . "\n\n";
	}
	
	$studentCount=$result->rowCount() ;
	
	if ($studentCount<=0) { //No students to display
		print _("There are no records to display.") . "\n\n" ;
	}
	else { //Students to display so get going
		while ($row=$result->fetch()) {
			//print $row["surname"] . ", " . $row["preferredName"] . " (" . $row["name"] . ")\n" ;
			
			//get CP1 parent email
		
			//assemble email
				//get all homework for the past week
			
				//get behaviour records for the past week
		
			//send email
		}
	}
	
	//send summary email to system admin
	print "\n" . $studentCount . "\n\n" ;	
}

?>