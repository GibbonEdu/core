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

$search=$_GET["search"] ;
$gibbonFamilyID=$_GET["gibbonFamilyID"] ;
$gibbonPersonID=$_POST["gibbonPersonID2"] ;

if ($gibbonFamilyID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_edit.php")==FALSE) {
			$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonPersonID=="") {
				$URL.="&return=error1" ;
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
					$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
					$URL.="&return=error2" ;
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
							$URL.="&return=error2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				if ($resultCheck->rowCount()>0) {
					$URL.="&return=error3" ;
					header("Location: {$URL}");
				}
				else {
					//Validate Inputs
					$comment=$_POST["comment2"] ;
					$childDataAccess=$_POST["childDataAccess"] ;
					$contactPriority=$_POST["contactPriority"] ;
					if ($contactPriority==1) {
						$contactCall="Y" ;
						$contactSMS="Y" ;
						$contactEmail="Y" ;
						$contactMail="Y" ;
					}
					else {
						$contactCall=$_POST["contactCall"] ;
						$contactSMS=$_POST["contactSMS"] ;
						$contactEmail=$_POST["contactEmail"] ;
						$contactMail=$_POST["contactMail"] ;
					}
					
					//Enforce one and only one contactPriority=1 parent
					if ($contactPriority==1) {
						//Set all other parents in family who are set to 1, to 2
						try {
							$dataCP=array("gibbonPersonID"=>$gibbonPersonID, "gibbonFamilyID"=>$gibbonFamilyID); 
							$sqlCP="UPDATE gibbonFamilyAdult SET contactPriority=2 WHERE gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPersonID=:gibbonPersonID" ;
							$resultCP=$connection2->prepare($sqlCP);
							$resultCP->execute($dataCP);
						}
						catch(PDOException $e) { }
					}
					else {
						//Check to see if there is a parent set to 1 already, and if not, change this one to 1
						try {
							$dataCP=array("gibbonPersonID"=>$gibbonPersonID, "gibbonFamilyID"=>$gibbonFamilyID); 
							$sqlCP="SELECT * FROM gibbonFamilyAdult WHERE contactPriority=1 AND gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPersonID=:gibbonPersonID" ;
							$resultCP=$connection2->prepare($sqlCP);
							$resultCP->execute($dataCP);
						}
						catch(PDOException $e) { }
						if ($resultCP->rowCount()<1) {
							$contactPriority=1 ;
							$contactCall="Y" ;
							$contactSMS="Y" ;
							$contactEmail="Y" ;
							$contactMail="Y" ;
						}
					}

					//Write to database
					try {
						$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$gibbonPersonID, "comment"=>$comment, "childDataAccess"=>$childDataAccess, "contactPriority"=>$contactPriority, "contactCall"=>$contactCall, "contactSMS"=>$contactSMS, "contactEmail"=>$contactEmail, "contactMail"=>$contactMail); 
						$sql="INSERT INTO gibbonFamilyAdult SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID=:gibbonPersonID, comment=:comment, childDataAccess=:childDataAccess, contactPriority=:contactPriority, contactCall=:contactCall, contactSMS=:contactSMS, contactEmail=:contactEmail, contactMail=:contactMail" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
									$URL.="&return=error2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
							$URL.="&return=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>