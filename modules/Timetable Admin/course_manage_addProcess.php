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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

if ($_POST["gibbonDepartmentID"]!="") {
	$gibbonDepartmentID=$_POST["gibbonDepartmentID"] ;
}
else {
	$gibbonDepartmentID=NULL ;
}
$name=$_POST["name"] ;
$nameShort=$_POST["nameShort"] ;
$orderBy=$_POST["orderBy"] ;
$description=$_POST["description"] ;
$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$count=$_POST["count"] ;
$gibbonYearGroupIDList="" ;
for ($i=0; $i<$count; $i++) {
	if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
		if ($_POST["gibbonYearGroupIDCheck$i"]=="on") {
			$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST["gibbonYearGroupID$i"] . "," ;
		}
	}
}
$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/course_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID" ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/course_manage_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	if ($gibbonSchoolYearID=="" OR $name=="" OR $nameShort=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonCourse WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID)" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}

		if ($result->rowCount()>0) {
			$URL.="&return=error3" ;
			header("Location: {$URL}");
		}
		else {	
			//Write to database
			try {
				$data=array("gibbonDepartmentID"=>$gibbonDepartmentID, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "name"=>$name, "nameShort"=>$nameShort, "orderBy"=>$orderBy, "description"=>$description, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList); 
				$sql="INSERT INTO gibbonCourse SET gibbonDepartmentID=:gibbonDepartmentID, gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, orderBy=:orderBy, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			//Last insert ID
			$AI=str_pad($connection2->lastInsertID(), 8, "0", STR_PAD_LEFT) ;

			$URL.="&return=success0&editID=$AI" ;
			header("Location: {$URL}");
		}
	}
}
?>