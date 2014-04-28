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

$gibbonTTDayID=$_GET["gibbonTTDayID"] ;
$gibbonTTID=$_GET["gibbonTTID"] ;
$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonTTColumnRowID=$_GET["gibbonTTColumnRowID"] ;
$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$gibbonTTDayRowClassID=$_GET["gibbonTTDayRowClassID"] ;
$gibbonTTDayRowClassExceptionID=$_GET["gibbonTTDayRowClassExceptionID"] ;

if ($gibbonTTDayID=="" OR $gibbonTTID=="" OR $gibbonSchoolYearID=="" OR $gibbonTTColumnRowID=="" OR $gibbonCourseClassID=="" OR $gibbonTTDayRowClassID=="" OR $gibbonTTDayRowClassExceptionID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/tt_edit_day_edit_class_exception_delete.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID&gibbonTTDayRowClassExceptionID=$gibbonTTDayRowClassExceptionID" ;
	$URLDelete=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/tt_edit_day_edit_class_exception.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt_edit_day_edit_class_exception_delete.php")==FALSE) {
		//Fail 0
		$URL=$URL . "&deleteReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if school year specified
		if ($gibbonTTDayID=="") {
			//Fail1
			$URL=$URL . "&deleteReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonTTColumnRowID"=>$gibbonTTColumnRowID, "gibbonTTDayID"=>$gibbonTTDayID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonTTColumnRowID=$gibbonTTColumnRowID AND gibbonTTDayID=$gibbonTTDayID AND gibbonTTColumnRowID=$gibbonTTColumnRowID AND gibbonCourseClass.gibbonCourseClassID=$gibbonCourseClassID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL=$URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()<1) {
				//Fail 2
				$URL=$URL . "&deleteReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				try {
					$data=array("gibbonTTDayRowClassExceptionID"=>$gibbonTTDayRowClassExceptionID); 
					$sql="SELECT * FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassExceptionID=:gibbonTTDayRowClassExceptionID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL=$URL . "&deleteReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()<1) {
					//Fail 2
					$URL=$URL . "&deleteReturn=fail2" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("gibbonTTDayRowClassExceptionID"=>$gibbonTTDayRowClassExceptionID); 
						$sql="DELETE FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassExceptionID=:gibbonTTDayRowClassExceptionID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail2
						$URL=$URL . "&deleteReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
					
					//Success 0
					$URLDelete=$URLDelete . "&deleteReturn=success0" ;
					header("Location: {$URLDelete}");
				}
			}
		}
	}
}
?>