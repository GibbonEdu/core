<?
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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/activitySettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/activitySettings.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$dateType=$_POST["dateType"] ; 	
	if ($dateType=="Term") {
		$maxPerTerm=$_POST["maxPerTerm"] ;
	}
	else {
		$maxPerTerm=0 ;
	}
	$access=$_POST["access"] ; 	
	$payment=$_POST["payment"] ; 	
	$enrolmentType=$_POST["enrolmentType"] ;
	$backupChoice=$_POST["backupChoice"] ;
	$activityTypes="" ; 
	foreach (explode(",", $_POST["activityTypes"]) as $type) {
		$activityTypes.=trim($type) . "," ;
	}
	$activityTypes=substr($activityTypes,0,-1) ;
	
	
	//Validate Inputs
	if ($dateType=="" OR $access=="" OR $payment=="" OR $enrolmentType=="" OR $backupChoice=="") {
		//Fail 3
		$URL=$URL . "&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {	
		//Write to database
		$fail=FALSE ;
		
		try {
			$data=array("value"=>$dateType); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='dateType'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$maxPerTerm); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='maxPerTerm'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$access); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='access'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}

		try {
			$data=array("value"=>$payment); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='payment'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$enrolmentType); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='enrolmentType'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$backupChoice); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='backupChoice'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("value"=>$activityTypes); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='activityTypes'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		if ($fail==TRUE) {
			//Fail 2
			$URL=$URL . "&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Success 0
			getSystemSettings($guid, $connection2) ;
			$URL=$URL . "&updateReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>