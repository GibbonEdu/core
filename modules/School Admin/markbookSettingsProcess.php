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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/markbookSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/behaviourSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$markbookType="" ; 
	foreach (explode(",", $_POST["markbookType"]) as $type) {
		$markbookType.=trim($type) . "," ;
	}
	$markbookType=substr($markbookType,0,-1) ;
	$attainmentAlternativeName=$_POST["attainmentAlternativeName"] ;
	$attainmentAlternativeNameAbrev=$_POST["attainmentAlternativeNameAbrev"] ;
	$effortAlternativeName=$_POST["effortAlternativeName"] ;
	$effortAlternativeNameAbrev=$_POST["effortAlternativeNameAbrev"] ;
	$wordpressCommentPush=$_POST["wordpressCommentPush"] ;
	$showStudentAttainmentWarning=$_POST["showStudentAttainmentWarning"] ;
	$showStudentEffortWarning=$_POST["showStudentEffortWarning"] ;
	$showParentAttainmentWarning=$_POST["showParentAttainmentWarning"] ;
	$showParentEffortWarning=$_POST["showParentEffortWarning"] ;
	$personalisedWarnings=$_POST["personalisedWarnings"] ;
	
	//Validate Inputs
	if ($markbookType=="") {
		//Fail 3
		$URL.="&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {	
		//Write to database
		$fail=FALSE ;
		
		try {
			$data=array("value"=>$markbookType); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='markbookType'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$attainmentAlternativeName); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='attainmentAlternativeName'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$attainmentAlternativeNameAbrev); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='attainmentAlternativeNameAbrev'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$effortAlternativeName); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='effortAlternativeName'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$effortAlternativeNameAbrev); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='effortAlternativeNameAbrev'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$wordpressCommentPush); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='wordpressCommentPush'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$showStudentAttainmentWarning); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showStudentAttainmentWarning'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$showStudentEffortWarning); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showStudentEffortWarning'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$showParentAttainmentWarning); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showParentAttainmentWarning'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$showParentEffortWarning); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showParentEffortWarning'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$personalisedWarnings); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='personalisedWarnings'" ;
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