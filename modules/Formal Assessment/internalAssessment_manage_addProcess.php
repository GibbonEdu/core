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

$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/internalAssessment_manage_add.php&gibbonCourseClassID=$gibbonCourseClassID" ;

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	if (empty($_POST)) {
		$URL.="&addReturn=fail5" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Validate Inputs
		$gibbonCourseClassIDMulti=NULL ;
		if (isset($_POST["gibbonCourseClassIDMulti"])) {
			$gibbonCourseClassIDMulti=$_POST["gibbonCourseClassIDMulti"] ;
		}
		$name=$_POST["name"] ;
		$description=$_POST["description"] ;
		$type=$_POST["type"] ;
		//Sort out attainment
		$attainment=$_POST["attainment"] ;
		if ($attainment=="N") {
			$gibbonScaleIDAttainment=NULL ;
		}
		else {
			if ($_POST["gibbonScaleIDAttainment"]=="") {
				$gibbonScaleIDAttainment=NULL ;
			}
			else {
				$gibbonScaleIDAttainment=$_POST["gibbonScaleIDAttainment"] ;
			}
		}
		//Sort out effort
		$effort=$_POST["effort"] ;
		if ($effort=="N") {
			$gibbonScaleIDEffort=NULL ;
		}
		else {
			if ($_POST["gibbonScaleIDEffort"]=="") {
				$gibbonScaleIDEffort=NULL ;
			}
			else {
				$gibbonScaleIDEffort=$_POST["gibbonScaleIDEffort"] ;
			}
		}
		$comment=$_POST["comment"] ;
		$uploadedResponse=$_POST["uploadedResponse"] ;
		$completeDate=$_POST["completeDate"] ;
		if ($completeDate=="") {
			$completeDate=NULL ;
			$complete="N" ;
		}
		else {
			$completeDate=dateConvert($guid, $completeDate) ;
			$complete="Y" ;
		}
		$viewableStudents=$_POST["viewableStudents"] ;
		$viewableParents=$_POST["viewableParents"] ;
		$gibbonPersonIDCreator=$_SESSION[$guid]["gibbonPersonID"] ;
		$gibbonPersonIDLastEdit=$_SESSION[$guid]["gibbonPersonID"] ;
		
		//Lock markbook column table
		try {
			$sqlLock="LOCK TABLES gibbonInternalAssessmentColumn WRITE" ;
			$resultLock=$connection2->query($sqlLock);   
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}			

		//Get next groupingID
		try {
			$sqlGrouping="SELECT DISTINCT groupingID FROM gibbonInternalAssessmentColumn WHERE NOT groupingID IS NULL ORDER BY groupingID DESC";
			$resultGrouping=$connection2->query($sqlGrouping);   
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&addReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}			
		
		$rowGrouping=$resultGrouping->fetch();
		if (is_null($rowGrouping['groupingID'])) {
			$groupingID=1 ;
		}
		else {
			$groupingID=($rowGrouping['groupingID']+1) ;
		}
		
		$time=time() ;
		//Move attached file, if there is one
		if ($_FILES['file']["tmp_name"]!="") {
			//Check for folder in uploads based on today's date
			$path=$_SESSION[$guid]["absolutePath"] ;
			if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
				mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
			}
			$unique=FALSE;
			$count=0 ;
			while ($unique==FALSE AND $count<100) {
				$suffix=randomPassword(16) ;
				$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
				if (!(file_exists($path . "/" . $attachment))) {
					$unique=TRUE ;
				}
				$count++ ;
			}
			if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
				//Fail 5
				$URL.="&updateReturn=fail5" ;
				header("Location: {$URL}");
			}
		}
		else {
			$attachment="" ;
		}
		
		if (is_array($gibbonCourseClassIDMulti)==FALSE OR is_numeric($groupingID)==FALSE or $groupingID<1 OR $name=="" OR $description=="" OR $type=="" OR $viewableStudents=="" OR $viewableParents=="") {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			$partialFail=FALSE ;
			
			foreach ($gibbonCourseClassIDMulti AS $gibbonCourseClassIDSingle) {
				//Write to database
				try {
					$data=array("groupingID"=>$groupingID, "gibbonCourseClassID"=>$gibbonCourseClassIDSingle, "name"=>$name, "description"=>$description, "type"=>$type, "attainment"=>$attainment, "gibbonScaleIDAttainment"=>$gibbonScaleIDAttainment, "effort"=>$effort, "gibbonScaleIDEffort"=>$gibbonScaleIDEffort, "comment"=>$comment, "uploadedResponse"=>$uploadedResponse, "completeDate"=>$completeDate, "complete"=>$complete, "viewableStudents"=>$viewableStudents, "viewableParents"=>$viewableParents, "attachment"=>$attachment, "gibbonPersonIDCreator"=>$gibbonPersonIDCreator, "gibbonPersonIDLastEdit"=>$gibbonPersonIDLastEdit); 
					$sql="INSERT INTO gibbonInternalAssessmentColumn SET groupingID=:groupingID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					print $e->getMessage() ; exit() ;
					$partialFail=TRUE ;
				}
			}
		
			//Unlock module table
			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) { }			
			
			if ($partialFail!=FALSE) {
				//Success 0
				$URL.="&addReturn=fail6" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&addReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>