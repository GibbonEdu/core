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
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonCourseID=$_GET["gibbonCourseID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/course_manage_edit.php&gibbonCourseID=" . $gibbonCourseID . "&gibbonSchoolYearID=" . $_POST["gibbonSchoolYearID"] ;

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/course_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if special day specified
	if ($gibbonCourseID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonCourseID"=>$gibbonCourseID); 
			$sql="SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Validate Inputs
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

			if ($name=="" OR $nameShort=="" OR $gibbonSchoolYearID=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check unique inputs for uniquness
				try {
					$data=array("name"=>$name, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE (name=:name AND gibbonSchoolYearID=:gibbonSchoolYearID) AND NOT (gibbonCourseID=:gibbonCourseID)" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()>0) {
					//Fail 4
					$URL.="&updateReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("gibbonDepartmentID"=>$gibbonDepartmentID, "name"=>$name, "nameShort"=>$nameShort, "orderBy"=>$orderBy, "description"=>$description, "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "gibbonCourseID"=>$gibbonCourseID); 
						$sql="UPDATE gibbonCourse SET gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, orderBy=:orderBy, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList WHERE gibbonCourseID=:gibbonCourseID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>