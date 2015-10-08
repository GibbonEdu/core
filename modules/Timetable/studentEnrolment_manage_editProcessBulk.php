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

$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
$gibbonCourseID=$_POST["gibbonCourseID"] ;
$action=$_POST["action"] ;

if ($gibbonCourseClassID=="" OR $gibbonCourseID=="" OR $action=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/studentEnrolment_manage_edit.php&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Timetable/studentEnrolment_manage_edit.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Check access to the course
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (role='Coordinator' OR role='Assistant Coordinator') AND gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassID=:gibbonCourseClassID" ; 	
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$people=array() ;
			$count=0 ;
			for ($i=1; $i<=$_POST["count"]; $i++) {
				if (isset($_POST["check-$i"])) {
					if ($_POST["check-$i"]=="on") {
						$people[$count][0]=$_POST["gibbonPersonID-$i"] ;
						$people[$count][1]=$_POST["role-$i"] ;
						$count++ ;
					}
				}
			}
			//Proceed!
			//Check if person specified
			if (count($people)<1) {
				//Fail4
				$URL.="&updateReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				$partialFail=false ;
				if ($action=="Delete") {
					for ($i=0; $i<count($people); $i++) {
						try {
							$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$people[$i][0]); 
							$sql="DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail==true ;
						}
					}
				}
				else {
					for ($i=0; $i<count($people); $i++) {
						if ($people[$i][1]=="Student" OR $people[$i][1]=="Teacher") {
							try {
								$data=array("role"=>$people[$i][1] . " - Left", "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$people[$i][0]); 
								$sql="UPDATE gibbonCourseClassPerson SET role=:role WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$partialFail==true ;
							}
						}
						else {
							$partialFail=true ;
						}
					}
				}
			
				if ($partialFail==TRUE) {
					$URL.="&updateReturn=fail5" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL.="&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>