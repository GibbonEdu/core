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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/thirdPartySettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/thirdPartySettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$enablePayments=$_POST["enablePayments"] ;
	$paypalAPIUsername=$_POST["paypalAPIUsername"] ;
	$paypalAPIPassword=$_POST["paypalAPIPassword"] ;
	$paypalAPISignature=$_POST["paypalAPISignature"] ;
	$googleOAuth=$_POST["googleOAuth"] ; 	
	$googleClientName=$_POST["googleClientName"] ; 	
	$googleClientID=$_POST["googleClientID"] ; 
	$googleClientSecret=$_POST["googleClientSecret"] ;
	$googleRedirectUri=$_POST["googleRedirectUri"] ;
	$googleDeveloperKey=$_POST["googleDeveloperKey"] ;
	$calendarFeed=$_POST["calendarFeed"] ;
	$smsUsername=$_POST["smsUsername"] ;
	$smsPassword=$_POST["smsPassword"] ;
	$smsURL=$_POST["smsURL"] ;
	$smsURLCredit=$_POST["smsURLCredit"] ;
	
	//Validate Inputs
	if ($enablePayments=="" OR $googleOAuth=="") {
		//Fail 3
		$URL.="&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {	
		//Write to database
		$fail=FALSE ;
		
		try {
			$data=array("calendarFeed"=>$calendarFeed); 
			$sql="UPDATE gibbonSetting SET value=:calendarFeed WHERE scope='System' AND name='calendarFeed'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
	try {
			$data=array("value"=>$googleOAuth); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleOAuth'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}

		try {
			$data=array("value"=>$googleClientName); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleClientName'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}

		try {
			$data=array("value"=>$googleClientID); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleClientID'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}

		try {
			$data=array("value"=>$googleClientSecret); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleClientSecret'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}

		try {
			$data=array("value"=>$googleRedirectUri); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleRedirectUri'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}

		try {
			$data=array("value"=>$googleDeveloperKey); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='googleDeveloperKey'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ; 
		}
		
		
		try {
			$data=array("enablePayments"=>$enablePayments); 
			$sql="UPDATE gibbonSetting SET value=:enablePayments WHERE scope='System' AND name='enablePayments'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("paypalAPIUsername"=>$paypalAPIUsername); 
			$sql="UPDATE gibbonSetting SET value=:paypalAPIUsername WHERE scope='System' AND name='paypalAPIUsername'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("paypalAPIPassword"=>$paypalAPIPassword); 
			$sql="UPDATE gibbonSetting SET value=:paypalAPIPassword WHERE scope='System' AND name='paypalAPIPassword'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("paypalAPISignature"=>$paypalAPISignature); 
			$sql="UPDATE gibbonSetting SET value=:paypalAPISignature WHERE scope='System' AND name='paypalAPISignature'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}		
		
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