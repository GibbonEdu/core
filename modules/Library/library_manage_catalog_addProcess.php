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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_manage_catalog_add.php&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] . "&typeSpecificFields=" . $_GET["typeSpecificFields"] ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_add.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Get general fields
	$gibbonLibraryTypeID=$_POST["type"] ;
	$id=$_POST["id"] ;
	$name=$_POST["name"] ;
	$producer=$_POST["producer"] ;
	$vendor=$_POST["vendor"] ;
	$purchaseDate=NULL ;
	if ($_POST["purchaseDate"]!="") {
		$purchaseDate=dateConvert($guid, $_POST["purchaseDate"]);
	}
	$invoiceNumber=$_POST["invoiceNumber"] ;
	$imageType=$_POST["imageType"] ;
	if ($imageType=="Link") {
		$imageLocation=$_POST["imageLink"] ;
	}
	else {
		$imageLocation="" ;
	}
	$replacement=$_POST["replacement"] ;
	$gibbonSchoolYearIDReplacement=NULL ;
	$replacementCost=NULL ;
	if ($replacement=="Y") {
		if ($_POST["gibbonSchoolYearIDReplacement"]!="") {
			$gibbonSchoolYearIDReplacement=$_POST["gibbonSchoolYearIDReplacement"] ;
		}
		if ($_POST["replacementCost"]!="") {
			$replacementCost=$_POST["replacementCost"] ;
		}
	}
	else {
		$replacement=="N" ;
	}
	$comment=$_POST["comment"] ;
	$gibbonSpaceID=NULL ;
	if ($_POST["gibbonSpaceID"]!="") {
		$gibbonSpaceID=$_POST["gibbonSpaceID"];
	}
	$locationDetail=$_POST["locationDetail"];
	$ownershipType=$_POST["ownershipType"] ;
	$gibbonPersonIDOwnership=NULL ;
	if ($ownershipType=="School" AND $_POST["gibbonPersonIDOwnershipSchool"]!="") {
		$gibbonPersonIDOwnership=$_POST["gibbonPersonIDOwnershipSchool"];
	}
	else if ($ownershipType=="Individual" AND $_POST["gibbonPersonIDOwnershipIndividual"]!="") {
		$gibbonPersonIDOwnership=$_POST["gibbonPersonIDOwnershipIndividual"];
	}
	$gibbonDepartmentID=NULL ;
	if ($_POST["gibbonDepartmentID"]!="") {
		$gibbonDepartmentID=$_POST["gibbonDepartmentID"];
	}
	$bookable=$_POST["bookable"] ;
	$borrowable=$_POST["borrowable"] ;
	$status=$_POST["status"] ;
	$physicalCondition=$_POST["physicalCondition"] ;
	
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
				$fieldsOut[$field["name"]]=dateConvert($guid, $_POST["field" . $fieldName]) ;
			}
			else {
				$fieldsOut[$field["name"]]=$_POST["field" . $fieldName] ;
			}
		}
	}
	
				
	if ($gibbonLibraryTypeID=="" OR $name=="" OR $id=="" OR $producer=="" OR $bookable=="" OR $borrowable==""  OR $status=="" OR $replacement=="") {
		$URL.="&return=error1" ;
		header("Location: {$URL}");
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
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($resultUnique->rowCount()>0) {
			$URL.="&return=error3" ;
			header("Location: {$URL}");
		}
		else {
			//Move attached image  file, if there is one
			if (isset($_FILES["imageFile"])) {
				if ($_FILES["imageFile"]["tmp_name"]!="" AND $imageType=="File") {
					//Move attached file, if there is one
					if ($_FILES["imageFile"]["tmp_name"]!="") {
						$time=time() ;
						//Check for folder in uploads based on today's date
						$path=$_SESSION[$guid]["absolutePath"] ; ;
						if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
							mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
						}
						$unique=FALSE;
						$count=0 ;
						while ($unique==FALSE AND $count<100) {
							$suffix=randomPassword(16) ;
							$imageLocation="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $id) . "_$suffix" . strrchr($_FILES["imageFile"]["name"], ".") ;
							if (!(file_exists($path . "/" . $imageLocation))) {
								$unique=TRUE ;
							}
							$count++ ;
						}
					
						if (!(move_uploaded_file($_FILES["imageFile"]["tmp_name"],$path . "/" . $imageLocation))) {
							$URL.="&return=warning1" ;
							header("Location: {$URL}");
						}
					}
				}
			}
			
			//Write to database
			try {
				$data=array("gibbonLibraryTypeID"=>$gibbonLibraryTypeID, "id"=>$id, "name"=>$name, "producer"=>$producer, "fields"=>serialize($fieldsOut), "vendor"=>$vendor, "purchaseDate"=>$purchaseDate, "invoiceNumber"=>$invoiceNumber, "imageType"=>$imageType, "imageLocation"=>$imageLocation, "replacement"=>$replacement, "gibbonSchoolYearIDReplacement"=>$gibbonSchoolYearIDReplacement, "replacementCost"=>$replacementCost, "comment"=>$comment, "gibbonSpaceID"=>$gibbonSpaceID, "locationDetail"=>$locationDetail, "ownershipType"=>$ownershipType, "gibbonPersonIDOwnership"=>$gibbonPersonIDOwnership, "gibbonDepartmentID"=>$gibbonDepartmentID, "bookable"=>$bookable, "borrowable"=>$borrowable, "status"=>$status, "physicalCondition"=>$physicalCondition, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestampCreator"=>date('Y-m-d H:i:s', time())) ; 
				$sql="INSERT INTO gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, id=:id, name=:name, producer=:producer, fields=:fields, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, imageType=:imageType, imageLocation=:imageLocation, replacement=:replacement, gibbonSchoolYearIDReplacement=:gibbonSchoolYearIDReplacement, replacementCost=:replacementCost, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, bookable=:bookable, borrowable=:borrowable, status=:status, physicalCondition=:physicalCondition, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator" ;
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
?>