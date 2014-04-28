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

$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
$gibbonPersonID=$_POST["gibbonPersonID"] ;
$search=$_GET["search"] ;

if ($gibbonFamilyID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_edit.php")==FALSE) {
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
				$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
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
				//Check for an existing child or parent record in this family
				try {
					$dataCheck=array("gibbonPersonID1"=>$gibbonPersonID, "gibbonFamilyID1"=>$gibbonFamilyID, "gibbonPersonID2"=>$gibbonPersonID, "gibbonFamilyID2"=>$gibbonFamilyID); 
					$sqlCheck="SELECT gibbonPersonID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyID=:gibbonFamilyID2 UNION SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID2" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($resultCheck->rowCount()>0) {
					//Fail 4
					$URL=$URL . "&addReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Validate Inputs
					$comment=$_POST["comment"] ;
	
					//Write to database
					try {
						$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$gibbonPersonID, "comment"=>$comment); 
						$sql="INSERT INTO gibbonFamilyChild SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID=:gibbonPersonID, comment=:comment" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
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