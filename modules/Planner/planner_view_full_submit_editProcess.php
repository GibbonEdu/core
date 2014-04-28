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

//Gibbon system-wide includes
include "../../functions.php" ;
include "../../config.php" ;

//Module includes
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

$gibbonPlannerEntryID=$_POST["gibbonPlannerEntryID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=" . $_POST["search"] . $_POST["params"] ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full_submit_edit.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if planner specified
		if ($gibbonPlannerEntryID=="") {
			//Fail1
			$URL=$URL . "&updateReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			try {
				$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
				$sql="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL=$URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL=$URL . "&updateReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				if ($_POST["submission"]!="true" AND $_POST["submission"]!="false") {
					//Fail1
					$URL=$URL . "&updateReturn=fail1" ;
					header("Location: {$URL}");
				}
				else {
					if ($_POST["submission"]=="true") {
						$submission=true ;
						$gibbonPlannerEntryHomeworkID=$_POST["gibbonPlannerEntryHomeworkID"] ;
					}
					else {
						$submission=false ;
						$gibbonPersonID=$_POST["gibbonPersonID"] ;
					}
					$type=$_POST["type"] ;
					$version=$_POST["version"] ;
					$link=$_POST["link"] ;
					$status=$_POST["status"] ;
					$gibbonPlannerEntryID=$_POST["gibbonPlannerEntryID"] ;
					$count=$_POST["count"] ;
					$lesson=$_POST["lesson"] ;
					
					if (($submission==true AND $gibbonPlannerEntryHomeworkID=="") OR ($submission==false AND ($gibbonPersonID=="" OR $type=="" OR $version=="" OR ($type=="File" AND $_FILES['file']["name"]=="") OR ($type=="Link" AND $link=="") OR $status=="" OR $lesson=="" OR $count==""))) {
						//Fail1
						$URL=$URL . "&updateReturn=fail1" ;
						header("Location: {$URL}");
					}
					else {
						if ($submission==true) {
							try {
								$data=array("status"=>$status, "gibbonPlannerEntryHomeworkID"=>$gibbonPlannerEntryHomeworkID); 
								$sql="UPDATE gibbonPlannerEntryHomework SET status=:status WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								//Fail 2
								$URL=$URL . "&updateReturn=fail2" ;
								header("Location: {$URL}");
								break ;
							}
							$URL=$URL . "&updateReturn=success0" ;
							//Success 0
							header("Location: {$URL}");
						}
						else {
							$partialFail=FALSE ;
							$location=NULL ;
							if ($type=="Link") {
								if (substr($link, 0, 7)!="http://" AND substr($link, 0, 8)!="https://" ) {
									$partialFail=TRUE ;	
								}
								else {
									$location=$link ;
								}
							}
							if ($type=="File") {
								//Check extension to see if allow
								try {
									$dataExt=array(); 
									$sqlExt="SELECT * FROM gibbonFileExtension WHERE extension='". end(explode(".", $_FILES['file']["name"])) ."'";
									$resultExt=$connection2->prepare($sqlExt);
									$resultExt->execute($dataExt);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
								
								if ($resultExt->rowCount()!=1) {
									$partialFail=TRUE ;
								}
								else {
									//Attempt file upload
									$time=time() ;
									if ($_FILES['file']["tmp_name"]!="") {
										//Check for folder in uploads based on today's date
										$path=$_SESSION[$guid]["absolutePath"] ; ;
										if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
											mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
										}
										$unique=FALSE;
										while ($unique==FALSE) {
											$suffix=randomPassword(16) ;
											$location="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $_SESSION[$guid]["username"] . "_" . preg_replace("/[^a-zA-Z0-9]/", "", $lesson) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
											if (!(file_exists($path . "/" . $location))) {
												$unique=TRUE ;
											}
										}
										if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $location))) {
											//Fail 5
											$URL=$URL . "&addReturn=fail5" ;
											header("Location: {$URL}");
										}
									}
									else {
										$partialFail=TRUE ;
									}
								}
							}
							
							//Deal with partial fail
							if ($partialFail==TRUE) {
								//Fail 6
								$URL=$URL . "&updateReturn=fail6" ;
								header("Location: {$URL}");
							}
							else {
								//Write to database
								try {
									$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$gibbonPersonID, "type"=>$type, "version"=>$version, "status"=>$status, "location"=>$location, "count"=>($count+1), "timestamp"=>date("Y-m-d H:i:s")); 
									$sql="INSERT INTO gibbonPlannerEntryHomework SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, type=:type, version=:version, status=:status, location=:location, count=:count, timestamp=:timestamp" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									//Fail 2
									$URL=$URL . "&updateReturn=fail2" ;
									header("Location: {$URL}");
									break ;
								}
								
								//Success 0
								$URL=$URL . "&updateReturn=success0" ;
								header("Location: {$URL}");
							}
						}	
					}
				}					
			}
		}
	}
}
?>