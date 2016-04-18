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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/staffApplicationFormSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/staffApplicationFormSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$staffApplicationFormIntroduction=$_POST["staffApplicationFormIntroduction"] ; 	
	$staffApplicationFormQuestions=$_POST["staffApplicationFormQuestions"] ; 		
	$applicationFormRefereeLink=$_POST["applicationFormRefereeLink"] ; 	
	$staffApplicationFormPostscript=$_POST["staffApplicationFormPostscript"] ; 	
	$staffApplicationFormAgreement=$_POST["staffApplicationFormAgreement"] ; 	
	$staffApplicationFormMilestones=$_POST["staffApplicationFormMilestones"] ; 
	$staffApplicationFormRequiredDocuments=$_POST["staffApplicationFormRequiredDocuments"] ; 
	$staffApplicationFormRequiredDocumentsText=$_POST["staffApplicationFormRequiredDocumentsText"] ; 
	$staffApplicationFormRequiredDocumentsCompulsory=$_POST["staffApplicationFormRequiredDocumentsCompulsory"] ; 
	$staffApplicationFormNotificationMessage=$_POST["staffApplicationFormNotificationMessage"] ; 
	$staffApplicationFormNotificationDefault=$_POST["staffApplicationFormNotificationDefault"] ; 
	$staffApplicationFormDefaultEmail=$_POST["staffApplicationFormDefaultEmail"] ; 
	$staffApplicationFormDefaultWebsite=$_POST["staffApplicationFormDefaultWebsite"] ;
	$staffApplicationFormUsernameFormat=$_POST["staffApplicationFormUsernameFormat"] ;
	
	//Write to database
	$fail=FALSE ;
	
	try {
		$data=array("value"=>$staffApplicationFormIntroduction); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormIntroduction'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormQuestions); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormQuestions'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$applicationFormRefereeLink); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='applicationFormRefereeLink'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormPostscript); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormPostscript'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormAgreement); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormAgreement'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormMilestones); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormMilestones'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormRequiredDocuments); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormRequiredDocuments'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormRequiredDocumentsText); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormRequiredDocumentsText'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormRequiredDocumentsCompulsory); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormRequiredDocumentsCompulsory'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormNotificationMessage); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormNotificationMessage'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormNotificationDefault); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormNotificationDefault'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormDefaultEmail); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormDefaultEmail'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormDefaultWebsite); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormDefaultWebsite'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$staffApplicationFormUsernameFormat); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Staff' AND name='staffApplicationFormUsernameFormat'" ;
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
?>