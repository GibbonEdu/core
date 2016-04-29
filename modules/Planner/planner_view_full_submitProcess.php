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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$currentDate=$_POST["currentDate"] ;
$today=date("Y-m-d");
$params="" ;
if (isset($_GET["date"])) {
	$params=$params."&date=" . $_GET["date"] ;
}
if (isset($_GET["viewBy"])) {
	$params=$params."&viewBy=" . $_GET["viewBy"] ;
}
if (isset($_GET["gibbonCourseClassID"])) {
	$params=$params."&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] ;
}
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID$params" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_view_full.php")==FALSE) {
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	if (empty($_POST)) {
		$URL.="&updateReturn=fail6" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if planner specified
		if ($gibbonPlannerEntryID=="") {
				$URL.="&return=error1" ;
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
					$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}

			if ($result->rowCount()!=1) {
					$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {	
				//Check that date is not in the future
				if ($currentDate>$today) {
					$URL.="&return=error3" ;
					header("Location: {$URL}");
				}
				else {
					//Check that date is a school day
					if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
						$URL.="&return=warning1" ;
						header("Location: {$URL}");
					}
					else {
						//Get variables
						$type=$_POST["type"] ;
						$version=$_POST["version"] ;
						$link=$_POST["link"] ;
						$status=$_POST["status"] ;
						$gibbonPlannerEntryID=$_POST["gibbonPlannerEntryID"] ;
						$count=$_POST["count"] ;
						$lesson=$_POST["lesson"] ;
						
						//Validation
						if ($type=="" OR $version=="" OR ($_FILES['file']["name"]=="" AND $link=="") OR $status=="" OR $count=="" OR $lesson=="") {
											$URL.="&return=error3" ;
							header("Location: {$URL}");
						}
						else {
							$partialFail=FALSE ;
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
									$ext=explode(".", $_FILES['file']["name"]) ;
									$dataExt=array("extension"=>end($ext)); 
									$sqlExt="SELECT * FROM gibbonFileExtension WHERE extension=:extension";
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
										$count=0 ;
										while ($unique==FALSE AND $count<100) {
											$suffix=randomPassword(16) ;
											$location="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $_SESSION[$guid]["username"] . "_" . preg_replace("/[^a-zA-Z0-9]/", "", $lesson) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
											if (!(file_exists($path . "/" . $location))) {
												$unique=TRUE ;
											}
											$count++ ;
										}
										
										if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $location))) {
											$URL.="&return=warning1" ;
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
								$URL.="&updateReturn=fail6" ;
								header("Location: {$URL}");
							}
							else {
								//Write to database
								try {
									$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "type"=>$type, "version"=>$version, "status"=>$status, "location"=>$location, "count"=>($count+1), "timestamp"=>date("Y-m-d H:i:s")); 
									$sql="INSERT INTO gibbonPlannerEntryHomework SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, type=:type, version=:version, status=:status, location=:location, count=:count, timestamp=:timestamp" ;
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
		}
	}
}
?>