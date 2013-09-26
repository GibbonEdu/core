<?
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

session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonLibraryItemID=$_POST["gibbonLibraryItemID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/library_manage_catalog_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=" . $_GET["name"] . "&gibbonLibraryTypeID=" . $_GET["gibbonLibraryTypeID"] . "&gibbonSpaceID=" . $_GET["gibbonSpaceID"] . "&status=" . $_GET["status"] . "&gibbonPersonIDOwnership=" . $_GET["gibbonPersonIDOwnership"] ;

if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_edit.php")==FALSE) {
	//Fail 0
	$URL = $URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	if ($gibbonLibraryItemID=="") {
		//Fail1
		$URL = $URL . "&updateReturn=fail1" ;
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
			$URL = $URL . "&deleteReturn=fail2" ;
			header("Location: {$URL}");
			break ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL = $URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$row=$result->fetch() ;
			//Proceed!
			//Get general fields
			$gibbonLibraryTypeID=$_POST["gibbonLibraryTypeID"] ;
			$id=$_POST["id"] ;
			$name=$_POST["name"] ;
			$producer=$_POST["producer"] ;
			$vendor=$_POST["vendor"] ;
			$purchaseDate=NULL ;
			if ($_POST["purchaseDate"]!="") {
				$purchaseDate=dateConvert($_POST["purchaseDate"]);
			}
			$invoiceNumber=$_POST["invoiceNumber"] ;
			$imageType=$_POST["imageType"] ;
			if ($imageType=="Link") {
				$imageLocation=$_POST["imageLink"] ;
			}
			else if ($imageType=="File") {
				$imageLocation=$row["imageLocation"] ;
			}
			else {
				$imageLocation="" ;
			}
			$comment=$_POST["comment"] ;
			$gibbonSpaceID=NULL ;
			if ($_POST["gibbonSpaceID"]!="") {
				$gibbonSpaceID=$_POST["gibbonSpaceID"];
			}
			$locationDetail=$_POST["locationDetail"] ;
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
			$borrowable=$_POST["borrowable"] ;
			if ($borrowable=="Y") {
				$status=$_POST["statusBorrowable"] ;
			}
			else {
				$status=$_POST["statusNotBorrowable"] ;
			}
			
			
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
						$fieldsOut[$field["name"]]=dateConvert($_POST["field" . $fieldName]) ;
					}
					else {
						$fieldsOut[$field["name"]]=$_POST["field" . $fieldName] ;
					}
				}
			}
			
			if ($gibbonLibraryTypeID=="" OR $name=="" OR $id=="" OR $producer=="" OR $borrowable=="") {
				//Fail 3
				$URL = $URL . "&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check unique inputs for uniquness
				try {
					$dataUnique=array("id"=>$id, "gibbonLibraryItemID"=>$gibbonLibraryItemID); 
					$sqlUnique="SELECT * FROM gibbonLibraryItem WHERE id=:id AND NOT gibbonLibraryItemID=:gibbonLibraryItemID" ;
					$resultUnique=$connection2->prepare($sqlUnique);
					$resultUnique->execute($dataUnique);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL = $URL . "&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($resultUnique->rowCount()>0) {
					//Fail 4
					$URL = $URL . "&addReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Move attached image  file, if there is one
					if ($_FILES["imageFile"]["tmp_name"]!="" AND $imageType=="File") {
						//Move attached file, if there is one
						if ($_FILES["imageFile"]["tmp_name"]!="") {
							$time=mktime() ;
							//Check for folder in uploads based on today's date
							$path=$_SESSION[$guid]["absolutePath"] ; ;
							if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
								mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
							}
							$unique=FALSE;
							while ($unique==FALSE) {
								$suffix=randomPassword(16) ;
								$imageLocation="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $id) . "_$suffix" . strrchr($_FILES["imageFile"]["name"], ".") ;
								if (!(file_exists($path . "/" . $imageLocation))) {
									$unique=TRUE ;
								}
							}
							
							if (!(move_uploaded_file($_FILES["imageFile"]["tmp_name"],$path . "/" . $imageLocation))) {
								//Fail 5
								$URL = $URL . "&addReturn=fail5" ;
								header("Location: {$URL}");
							}
						}
					}
					
					//Move attached thumbnail  file, if there is one
					if ($_FILES["thumbnailFile"]["tmp_name"]!="" AND $imageType_100=="File") {
						//Move attached file, if there is one
						if ($_FILES["thumbnailFile"]["tmp_name"]!="") {
							$time=mktime() ;
							//Check for folder in uploads based on today's date
							$path=$_SESSION[$guid]["absolutePath"] ; ;
							if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
								mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
							}
							$unique=FALSE;
							while ($unique==FALSE) {
								$suffix=randomPassword(16) ;
								$imageLocation_100="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $id) . "_100_$suffix" . strrchr($_FILES["thumbnailFile"]["name"], ".") ;
								if (!(file_exists($path . "/" . $imageLocation_100))) {
									$unique=TRUE ;
								}
							}
							
							if (!(move_uploaded_file($_FILES["thumbnailFile"]["tmp_name"],$path . "/" . $imageLocation_100))) {
								//Fail 5
								$URL = $URL . "&addReturn=fail5" ;
								header("Location: {$URL}");
							}
						}
					}
			
					//Write to database
					try {
						$data=array("id"=>$id, "name"=>$name, "producer"=>$producer, "fields"=>serialize($fieldsOut), "vendor"=>$vendor, "purchaseDate"=>$purchaseDate, "invoiceNumber"=>$invoiceNumber, "imageType"=>$imageType, "imageLocation"=>$imageLocation, "comment"=>$comment, "gibbonSpaceID"=>$gibbonSpaceID, "locationDetail"=>$locationDetail, "ownershipType"=>$ownershipType, "gibbonPersonIDOwnership"=>$gibbonPersonIDOwnership, "gibbonDepartmentID"=>$gibbonDepartmentID, "borrowable"=>$borrowable, "status"=>$status, "gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "timestampUpdate"=>date('Y-m-d H:i:s', time()), "gibbonLibraryItemID"=>$gibbonLibraryItemID) ; 
						$sql="UPDATE gibbonLibraryItem SET id=:id, name=:name, producer=:producer, fields=:fields, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, imageType=:imageType, imageLocation=:imageLocation, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, status=:status, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate=:timestampUpdate WHERE gibbonLibraryItemID=:gibbonLibraryItemID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL = $URL . "&updateReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
	
					//Success 0
					$URL = $URL . "&updateReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>