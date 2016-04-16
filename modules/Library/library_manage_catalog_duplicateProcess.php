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

include "./moduleFunctions.php" ;

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

$gibbonLibraryItemID=$_POST["gibbonLibraryItemID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_manage_catalog_duplicate.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_edit.php")==FALSE) {
	//Fail 0
	$URL.="&duplicateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonLibraryItemID=="") {
		//Fail1
		$URL.="&duplicateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonLibraryItemID"=>$gibbonLibraryItemID); 
			$sql="SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&duplicateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			//Fields to copy
			$imageType=$row["imageType"] ;
			$imageLocation=$row["imageLocation"] ;
			$gibbonLibraryTypeID=$row["gibbonLibraryTypeID"] ;
			$status="Available" ;
				
			$partialFail=FALSE ;
			$count=$_POST["count"] ;
			for ($i=1; $i<=$count; $i++) {
				//Get general fields
				$id=$_POST["id" . $i] ;
				$name=$_POST["name" . $i] ;
				$producer=$_POST["producer" . $i] ;
				$vendor=$_POST["vendor" . $i] ;
				$purchaseDate=NULL ;
				if ($_POST["purchaseDate" . $i]!="") {
					$purchaseDate=dateConvert($guid, $_POST["purchaseDate" . $i]);
				}
				$invoiceNumber=$_POST["invoiceNumber" . $i] ;
				$gibbonSchoolYearIDReplacement=NULL ;
				if ($_POST["gibbonSchoolYearIDReplacement"]!="") {
					$gibbonSchoolYearIDReplacement=$_POST["gibbonSchoolYearIDReplacement"] ;
				}
				$replacementCost=NULL ;
				if ($_POST["replacementCost"]!="") {
					$replacementCost=$_POST["replacementCost"] ;
				}
				$comment=$_POST["comment" . $i] ;
				$gibbonSpaceID=NULL ;
				if ($_POST["gibbonSpaceID" . $i]!="") {
					$gibbonSpaceID=$_POST["gibbonSpaceID" . $i];
				}
				$locationDetail=$_POST["locationDetail" . $i];
				$ownershipType=$_POST["ownershipType" . $i] ;
				$gibbonPersonIDOwnership=NULL ;
				if ($ownershipType=="School" AND $_POST["gibbonPersonIDOwnershipSchool" . $i]!="") {
					$gibbonPersonIDOwnership=$_POST["gibbonPersonIDOwnershipSchool" . $i];
				}
				else if ($ownershipType=="Individual" AND $_POST["gibbonPersonIDOwnershipIndividual" . $i]!="") {
					$gibbonPersonIDOwnership=$_POST["gibbonPersonIDOwnershipIndividual" . $i];
				}
				$gibbonDepartmentID=NULL ;
				if ($_POST["gibbonDepartmentID" . $i]!="") {
					$gibbonDepartmentID=$_POST["gibbonDepartmentID" . $i];
				}
				$borrowable=$_POST["borrowable" . $i] ;
				
				//Get type-specific fields
				try {
					$data=array("gibbonLibraryTypeID"=>$gibbonLibraryTypeID); 
					$sql="SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				
				if ($result->rowCount()==1) {
					$row=$result->fetch() ;
					$fieldsIn=unserialize($row["fields"]) ;
					$fieldsOut=array() ;
					foreach ($fieldsIn as $field) {
						$fieldName=preg_replace("/ /", "", $field["name"]) ;
						if ($field["type"]=="Date") {
							$fieldsOut[$field["name"]]=dateConvert($guid, $_POST["field" . $fieldName . $i]) ;
						}
						else {
							$fieldsOut[$field["name"]]=$_POST["field" . $fieldName . $i] ;
						}
					}
				}
				
				if ($gibbonLibraryTypeID=="" OR $name=="" OR $id=="" OR $producer=="" OR $borrowable=="") {
					$partialFail=TRUE ;
				}
				else {
					//Check unique inputs for uniquness
					try {
						$dataUnique=array("id"=>$id); 
						$sqlUnique="SELECT * FROM gibbonLibraryItem WHERE id=:id" ;
						$resultUnique=$connection2->prepare($sqlUnique);
						$resultUnique->execute($dataUnique);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
					
					if ($resultUnique->rowCount()>0) {
						$partialFail=TRUE ;
					}
					else {
						//Write to database
						try {
							$data=array("gibbonLibraryTypeID"=>$gibbonLibraryTypeID, "id"=>$id, "name"=>$name, "producer"=>$producer, "fields"=>serialize($fieldsOut), "vendor"=>$vendor, "purchaseDate"=>$purchaseDate, "invoiceNumber"=>$invoiceNumber, "imageType"=>$imageType, "imageLocation"=>$imageLocation, "gibbonSchoolYearIDReplacement"=>$gibbonSchoolYearIDReplacement, "replacementCost"=>$replacementCost, "comment"=>$comment, "gibbonSpaceID"=>$gibbonSpaceID, "locationDetail"=>$locationDetail, "ownershipType"=>$ownershipType, "gibbonPersonIDOwnership"=>$gibbonPersonIDOwnership, "gibbonDepartmentID"=>$gibbonDepartmentID, "borrowable"=>$borrowable, "status"=>$status, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestampCreator"=>date('Y-m-d H:i:s', time())) ; 
							$sql="INSERT INTO gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, id=:id, name=:name, producer=:producer, fields=:fields, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, imageType=:imageType, imageLocation=:imageLocation, gibbonSchoolYearIDReplacement=:gibbonSchoolYearIDReplacement, replacementCost=:replacementCost, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, status=:status, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
							$failCode=$e->getMessage() ;
						}
					}
				}
			}
			
			if ($partialFail==TRUE) {
				//Fail 2
				$URL.="&duplicateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&duplicateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>