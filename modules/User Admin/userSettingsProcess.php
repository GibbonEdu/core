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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/userSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/userSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$ethnicity=$_POST["ethnicity"] ; 
	$religions=$_POST["religions"] ; 		
	$nationality=$_POST["nationality"] ; 	
	$residencyStatus=$_POST["residencyStatus"] ; 	
	$departureReasons=$_POST["departureReasons"] ; 	
	$privacy=$_POST["privacy"] ; 	
	$privacyBlurb=$_POST["privacyBlurb"] ; 	
	$privacyOptions=$_POST["privacyOptions"] ; 	
	$personalBackground=$_POST["personalBackground"] ;  
	$dayTypeOptions=$_POST["dayTypeOptions"] ; 
	$dayTypeText=$_POST["dayTypeText"] ; 	
	
	//Write to database
	$fail=FALSE ;
	
	try {
		$data=array("value"=>$ethnicity); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='ethnicity'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$religions); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='religions'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$nationality); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='nationality'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$departureReasons); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='departureReasons'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$residencyStatus); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='residencyStatus'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$privacy); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='privacy'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$privacyBlurb); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='privacyBlurb'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$privacyOptions); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='privacyOptions'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$personalBackground); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='personalBackground'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$dayTypeOptions); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='dayTypeOptions'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$dayTypeText); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='dayTypeText'" ;
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