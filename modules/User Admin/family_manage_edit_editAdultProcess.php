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
$gibbonPersonID=$_GET["gibbonPersonID"] ;
$search=$_GET["search"] ;

if ($gibbonFamilyID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/family_manage_edit_editAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=$gibbonPersonID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_edit_editAdult.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonPersonID=="") {
			//Fail1
			$URL.="&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT * FROM gibbonPerson, gibbonFamily, gibbonFamilyAdult WHERE gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Validate Inputs
				$comment=$_POST["comment"] ;
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
					$data=array("comment"=>$comment, "childDataAccess"=>$childDataAccess, "contactPriority"=>$contactPriority, "contactCall"=>$contactCall, "contactSMS"=>$contactSMS, "contactEmail"=>$contactEmail, "contactMail"=>$contactMail, "gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID"=>$gibbonPersonID); 
					$sql="UPDATE gibbonFamilyAdult SET comment=:comment, childDataAccess=:childDataAccess, contactPriority=:contactPriority, contactCall=:contactCall, contactSMS=:contactSMS, contactEmail=:contactEmail, contactMail=:contactMail WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonID=:gibbonPersonID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&updateReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				//Success 0
				$URL.="&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>