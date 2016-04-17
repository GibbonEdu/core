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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/messengerSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/messengerSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$smsUsername=$_POST["smsUsername"] ;
	$smsPassword=$_POST["smsPassword"] ;
	$smsURL=$_POST["smsURL"] ;
	$smsURLCredit=$_POST["smsURLCredit"] ;
	$messageBubbleWidthType=$_POST["messageBubbleWidthType"] ;
	$messageBubbleBGColor=$_POST["messageBubbleBGColor"] ;
	$messageBubbleAutoHide=$_POST["messageBubbleAutoHide"] ;
	$enableHomeScreenWidget=$_POST["enableHomeScreenWidget"] ;
	
	//Write to database
	$fail=FALSE ;
	
	try {
		$data=array("value"=>$smsUsername); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsUsername'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$smsPassword); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsPassword'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$smsURL); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsURL'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$smsURLCredit); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='smsURLCredit'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$messageBubbleWidthType); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBubbleWidthType'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$messageBubbleBGColor); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBubbleBGColor'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$messageBubbleAutoHide); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='messageBubbleAutoHide'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ;
	}
	
	try {
		$data=array("value"=>$enableHomeScreenWidget); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Messenger' AND name='enableHomeScreenWidget'" ;
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