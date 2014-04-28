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

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonPersonID=$_POST["gibbonPersonID"] ;
$search=$_GET["search"] ;

if ($gibbonSchoolYearID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/studentEnrolment_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/User Admin/studentEnrolment_manage_add.php")==FALSE) {
		//Fail 0
		$URL=$URL . "&addReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonPersonID=="") {
			//Fail1
			$URL=$URL . "&addReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonPersonID FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL=$URL . "&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}

			if ($result->rowCount()!=1) {
				//Fail 2
				$URL=$URL . "&addReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Check for existing enrolment
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$gibbonSchoolYearID); 
					$sql="SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL=$URL . "&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()>0) {
					//Fail 6
					$URL=$URL . "&addReturn=fail6" ;
					header("Location: {$URL}");
				}
				else {
					$gibbonYearGroupID=$_POST["gibbonYearGroupID"] ;
					$gibbonRollGroupID=$_POST["gibbonRollGroupID"] ;
				
					//Write to database
					try {
						$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonYearGroupID"=>$gibbonYearGroupID, "gibbonRollGroupID"=>$gibbonRollGroupID); 
						$sql="INSERT INTO gibbonStudentEnrolment SET gibbonPersonID=:gibbonPersonID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail2
						$URL=$URL . "&addReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
				
					//Success 0
					$URL=$URL . "&addReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>