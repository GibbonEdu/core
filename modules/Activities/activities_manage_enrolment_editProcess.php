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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonActivityID=$_GET["gibbonActivityID"] ;
$gibbonPersonID=$_GET["gibbonPersonID"] ;

if ($gibbonActivityID=="" OR $gibbonPersonID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/activities_manage_enrolment_edit.php&gibbonPersonID=$gibbonPersonID&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_enrolment_edit.php")==FALSE) {
		//Fail 0
		$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if school year specified
		$status=$_POST["status"] ;
		if ($status=="") {
			//Fail1
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonActivityID"=>$gibbonActivityID, "gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonActivity.*, gibbonActivityStudent.*, surname, preferredName FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
		
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {
				//Write to database
				try {
					$data=array("gibbonActivityID"=>$gibbonActivityID, "gibbonPersonID"=>$gibbonPersonID, "status"=>$status); 
					$sql="UPDATE gibbonActivityStudent SET status=:status WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&return=error2" ;
					header("Location: {$URL}");
					exit() ;
				}

				//Success 0
				$URL.="&return=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>