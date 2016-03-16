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
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID" ;

if (isActionAccessible($guid, $connection2, "/modules/Departments/department_edit.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	if (empty($_POST)) {
		$URL.="&updateReturn=fail5" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Validate Inputs
		$blurb=$_POST["blurb"] ;
		
		if ($gibbonDepartmentID=="") {
			//Fail 3
			$URL.="&updateReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Check access to specified course
			try {
				$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
				$sql="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 4
				$URL.="&updateReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Get role within learning area
				$role=getRole($_SESSION[$guid]["gibbonPersonID"], $gibbonDepartmentID, $connection2 ) ;
				
				if ($role!="Coordinator" AND $role!="Assistant Coordinator" AND $role!="Teacher (Curriculum)" AND $role!="Director" AND $role!="Manager") {
					//Fail 0
					$URL.="&addReturn=fail0" ;
					header("Location: {$URL}");
				}
				else{
					//Scan through resources
					$partialFail=FALSE ;
					for ($i=1; $i<4; $i++) {
						$resourceName=$_POST["name$i"] ;
						$resourceType=NULL ;
						if (isset($_POST["type$i"])) {
							$resourceType=$_POST["type$i"] ;
						}
						$resourceURL=$_POST["url$i"] ;
						
						if ($resourceName!="" AND $resourceType!="" AND ($resourceType=="File" OR $resourceType=="Link")) {
							if (($resourceType=="Link" AND $resourceURL!="") OR ($resourceType=="File" AND $_FILES['file' . $i]["tmp_name"]!="")) {
								if ($resourceType=="Link") {
									try {
										$data=array("gibbonDepartmentID"=>$gibbonDepartmentID, "resourceType"=>$resourceType, "resourceName"=>$resourceName, "resourceURL"=>$resourceURL); 
										$sql="INSERT INTO gibbonDepartmentResource SET gibbonDepartmentID=:gibbonDepartmentID, type=:resourceType, name=:resourceName, url=:resourceURL" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
								}
								else if ($resourceType=="File") {
									$time=time() ;
									//Move attached file, if there is one
									if ($_FILES["file" . $i]["tmp_name"]!="") {
										//Check for folder in uploads based on today's date
										$path=$_SESSION[$guid]["absolutePath"] ;
										if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
											mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
										}
										$unique=FALSE;
										$count=0 ;
										while ($unique==FALSE AND $count<100) {
											$suffix=randomPassword(16) ;
											$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $resourceName) . "_$suffix" . strrchr($_FILES["file" . $i]["name"], ".") ;
											if (!(file_exists($path . "/" . $attachment))) {
												$unique=TRUE ;
											}
											$count++ ;
										}
										
										if (!(move_uploaded_file($_FILES["file" . $i]["tmp_name"],$path . "/" . $attachment))) {
											//Fail 5
											$URL.="&updateReturn=fail5" ;
											header("Location: {$URL}");
										}
										else {
											try {	
												$data=array("gibbonDepartmentID"=>$gibbonDepartmentID, "resourceType"=>$resourceType, "resourceName"=>$resourceName, "attachment"=>$attachment); 
												$sql="INSERT INTO gibbonDepartmentResource SET gibbonDepartmentID=:gibbonDepartmentID, type=:resourceType, name=:resourceName, url=:attachment" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) {
												$partialFail=TRUE ;
											}
										}
									}
								}
							}
						}
					}
					
					//Write to database
					try {
						$data=array("blurb"=>$blurb, "gibbonDepartmentID"=>$gibbonDepartmentID); 
						$sql="UPDATE gibbonDepartment SET blurb=:blurb WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&updateReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					if ($partialFail==true) {
						//Fail 5
						$URL.="&updateReturn=fail5" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL.="&updateReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>