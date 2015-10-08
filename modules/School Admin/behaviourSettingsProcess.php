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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/behaviourSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/behaviourSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$enableDescriptors=$_POST["enableDescriptors"] ;
	$enableLevels=$_POST["enableLevels"] ;
	$positiveDescriptors="" ; 
	$negativeDescriptors="" ; 
	if ($enableDescriptors=="Y") {
		foreach (explode(",", $_POST["positiveDescriptors"]) as $descriptor) {
			$positiveDescriptors.=trim($descriptor) . "," ;
		}
		$positiveDescriptors=substr($positiveDescriptors,0,-1) ;
		
		foreach (explode(",", $_POST["negativeDescriptors"]) as $descriptor) {
			$negativeDescriptors.=trim($descriptor) . "," ;
		}
		$negativeDescriptors=substr($negativeDescriptors,0,-1) ;
	}
	$levels="" ; 
	if ($enableLevels=="Y") {
		foreach (explode(",", $_POST["levels"]) as $level) {
			$levels.=trim($level) . "," ;
		}
		$levels=substr($levels,0,-1) ;	
	}
	$policyLink=$_POST["policyLink"] ;
	
	//Validate Inputs
	if ($enableDescriptors=="" OR $enableLevels=="" OR ($positiveDescriptors=="" AND $enableDescriptors=="Y") OR ($negativeDescriptors=="" AND $enableDescriptors=="Y") OR ($levels=="" AND $enableLevels=="Y")) {
		//Fail 3
		$URL.="&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {	
		//Write to database
		$fail=FALSE ;
		
		try {
			$data=array("value"=>$enableDescriptors); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='enableDescriptors'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		if ($enableDescriptors=="Y") {
			try {
				$data=array("value"=>$positiveDescriptors); 
				$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='positiveDescriptors'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$fail=TRUE ;
			}
		
			try {
				$data=array("value"=>$negativeDescriptors); 
				$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='negativeDescriptors'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$fail=TRUE ;
			}
		}
		try {
			$data=array("value"=>$enableLevels); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='enableLevels'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		if ($enableLevels=="Y") {
			try {
				$data=array("value"=>$levels); 
				$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='levels'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				$fail=TRUE ;
			}
		}
		
		try {
			$data=array("value"=>$policyLink); 
			$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='policyLink'" ;
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