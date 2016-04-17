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

//Gibbon system-wide includes
include "../../functions.php" ;
include "../../config.php" ;

//Module includes
include "./moduleFunctions.php" ;

//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$gibbonMarkbookColumnID=$_GET["gibbonMarkbookColumnID"] ;
$gibbonPersonID=$_GET["gibbonPersonID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Markbook/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID" ;

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_data.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if planner specified
	if ($gibbonPersonID=="" OR $gibbonCourseClassID=="" OR $gibbonMarkbookColumnID=="") {
		//Fail1
		$URL.="&deleteReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonMarkbookColumnID"=>$gibbonMarkbookColumnID); 
			$sql="UPDATE gibbonMarkbookEntry SET response='' WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonMarkbookColumnID=:gibbonMarkbookColumnID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		$URL.="&deleteReturn=success0" ;
		//Success 0
		header("Location: {$URL}");
	}
}
?>