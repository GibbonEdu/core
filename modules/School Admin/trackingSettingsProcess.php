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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/trackingSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/trackingSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
   $fail=FALSE ;

   //DEAL WITH EXTERNAL ASSESSMENT DATA POINTS
   $externalAssessmentDataPoints=array() ;
   $assessmentCount=$_POST["external_gibbonExternalAssessmentID_count"] ;
   $yearCount=$_POST["external_year_count"] ;
   $count=0 ;
   for ($i=0; $i<$assessmentCount; $i++) {
      $externalAssessmentDataPoints[$count]["gibbonExternalAssessmentID"]=$_POST["external_gibbonExternalAssessmentID_" . $i] ;
      $externalAssessmentDataPoints[$count]["category"]=$_POST["external_category_" . $i] ;
      $externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"]="" ;
      for ($j=0; $j<$yearCount; $j++) {
         if (isset($_POST["external_gibbonExternalAssessmentID_" . $i . "_gibbonYearGroupID_" . $j])) {
            $externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"].=$_POST["external_gibbonExternalAssessmentID_" . $i . "_gibbonYearGroupID_" . $j] . "," ;
         }
      }
      if ($externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"]!="") {
         $externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"]=substr($externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"],0,-1) ;
      }
      $count++ ;
   }

   //Write setting to database
   try {
		$data=array("value"=>serialize($externalAssessmentDataPoints));
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Tracking' AND name='externalAssessmentDataPoints'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) {
		$fail=TRUE ;
	}

   //DEAL WITH INTERNAL ASSESSMENT DATA POINTS
   $internalAssessmentDataPoints=array() ;
   $assessmentCount=$_POST["internal_type_count"] ;
   $yearCount=$_POST["internal_year_count"] ;
   $count=0 ;
   for ($i=0; $i<$assessmentCount; $i++) {
      $internalAssessmentDataPoints[$count]["type"]=$_POST["internal_type_" . $i] ;
      $internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"]="" ;
      for ($j=0; $j<$yearCount; $j++) {
         if (isset($_POST["internal_type_" . $i . "_gibbonYearGroupID_" . $j])) {
            $internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"].=$_POST["internal_type_" . $i . "_gibbonYearGroupID_" . $j] . "," ;
         }
      }
      if ($internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"]!="") {
         $internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"]=substr($internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"],0,-1) ;
      }
      $count++ ;
   }
   //Write setting to database
   try {
      $data=array("value"=>serialize($internalAssessmentDataPoints));
      $sql="UPDATE gibbonSetting SET value=:value WHERE scope='Tracking' AND name='internalAssessmentDataPoints'" ;
      $result=$connection2->prepare($sql);
      $result->execute($data);
   }
   catch(PDOException $e) {
      $fail=TRUE ;
   }


   //RETURN RESULTS
   if ($fail==TRUE) {
		//Fail 2
		$URL.="&updateReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		//Success 0
		$URL.="&updateReturn=success0" ;
		header("Location: {$URL}");
	}
}
?>
