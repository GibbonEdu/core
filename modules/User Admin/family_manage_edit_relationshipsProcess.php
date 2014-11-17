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
$search=$_GET["search"] ;

if ($gibbonFamilyID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/User Admin/family_manage_edit.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Validate Inputs
		$relationships=$_POST["relationships"] ;
		$gibbonPersonID1=$_POST["gibbonPersonID1"] ;
		$gibbonPersonID2=$_POST["gibbonPersonID2"] ;

		$partialFail=FALSE ;
		
		$count=0 ;
		foreach ($relationships AS $relationship) {
			//Check for record
			try {
				$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID1"=>$gibbonPersonID1[$count], "gibbonPersonID2"=>$gibbonPersonID2[$count]); 
				$sql="SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$partialFail=TRUE ;
			}
			if ($result->rowCount()==0) {
				try {
					$data=array("gibbonFamilyID"=>$gibbonFamilyID, "gibbonPersonID1"=>$gibbonPersonID1[$count], "gibbonPersonID2"=>$gibbonPersonID2[$count], "relationship"=>$relationship); 
					$sql="INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					$partialFail=TRUE ;
				}
			}
			else if ($result->rowCount()==1) {
				$row=$result->fetch() ;
				
				if ($row["relationship"]!=$relationship) {
					try {
						$data=array("relationship"=>$relationship, "gibbonFamilyRelationshipID"=>$row["gibbonFamilyRelationshipID"]); 
						$sql="UPDATE gibbonFamilyRelationship SET relationship=:relationship WHERE gibbonFamilyRelationshipID=:gibbonFamilyRelationshipID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
				}
			}
			else {
				$partialFail=TRUE ;
			}
						
			$count++ ;
		}
		
		if ($partialFail==TRUE) {
			//Fail 3
			$URL.="&updateReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			$URL.="&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>