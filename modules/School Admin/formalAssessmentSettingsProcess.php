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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/formalAssessmentSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/formalAssessmentSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$internalAssessmentTypes="" ; 
	foreach (explode(",", $_POST["internalAssessmentTypes"]) as $type) {
		$internalAssessmentTypes.=trim($type) . "," ;
	}
	$internalAssessmentTypes=substr($internalAssessmentTypes,0,-1) ;
	$gibbonYearGroupID=$_POST["gibbonYearGroupID"] ;
	$gibbonExternalAssessmentID=$_POST["gibbonExternalAssessmentID"] ;
	$primaryExternalAssessmentByYearGroup=array() ;
	$count=0 ; 
	foreach ($gibbonYearGroupID as $year) {
		$set=FALSE ;
		
		if (isset($gibbonExternalAssessmentID[$count]) AND $gibbonExternalAssessmentID[$count]!="") {
			if (isset($_POST["category$count"])) {
				if ($_POST["category$count"]!="") {
					$primaryExternalAssessmentByYearGroup[$year]=$gibbonExternalAssessmentID[$count] . "-" . $_POST["category$count"] ;
					$set=TRUE ;
				}
			}
		}
		if ($set==FALSE) {
			$primaryExternalAssessmentByYearGroup[$year]=NULL ;
		}
		$count++ ;
	}
	
	//Validate Inputs
	if ($internalAssessmentTypes=="") {
		//Fail 3
		$URL.="&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {	
		//Write to database
		$fail=FALSE ;
		
		//Update internal assessment fields
		try {
			$data=array("value"=>$internalAssessmentTypes); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Formal Assessment' AND name='internalAssessmentTypes'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}
		
		//Update external assessment fields
		try {
			$data=array("value"=>serialize($primaryExternalAssessmentByYearGroup)); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='School Admin' AND name='primaryExternalAssessmentByYearGroup'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}
	
		if ($fail==TRUE) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			getSystemSettings($guid, $connection2) ;
			$URL.="&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>