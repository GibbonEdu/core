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

//Module includes
include $_SESSION[$guid]["absolutePath"] . "/modules/Activities/moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonActivityID=$_GET["gibbonActivityID"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/activities_manage_enrolment_add.php&gibbonActivityID=$gibbonActivityID&search=" . $_GET["search"] ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_enrolment_add.php")==FALSE) {
	//Fail 0
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$status=$_POST["status"] ;
	
	if ($gibbonActivityID=="" OR $status=="") {
		//Fail 3
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		//Run through each of the selected participants.
		$update=TRUE ;
		$choices=NULL ;
		if (isset($_POST["Members"])) {
			$choices=$_POST["Members"] ;
		}
		
		if (count($choices)<1) {
			//Fail 1
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
			foreach ($choices as $t) {
				//Check to see if student is already registered in this class
				try {
					$data=array("gibbonPersonID"=>$t, "gibbonActivityID"=>$gibbonActivityID); 
					$sql="SELECT * FROM gibbonActivityStudent WHERE gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				//If student not in course, add them
				if ($result->rowCount()==0) {
					try {
						$data=array("gibbonPersonID"=>$t, "gibbonActivityID"=>$gibbonActivityID, "status"=>$status, "timestamp"=>date('Y-m-d H:i:s', time())); 
						$sql="INSERT INTO gibbonActivityStudent SET gibbonPersonID=:gibbonPersonID, gibbonActivityID=:gibbonActivityID, status=:status, timestamp=:timestamp" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$update=FALSE;
					}
				}
			}
			//Write to database
			if ($update==FALSE) {
				//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&return=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>