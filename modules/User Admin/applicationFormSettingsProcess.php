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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/applicationFormSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationFormSettings.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$introduction=$_POST["introduction"] ; 	
	$postscript=$_POST["postscript"] ; 	
	$scholarships=$_POST["scholarships"] ; 	
	$agreement=$_POST["agreement"] ; 	
	$applicationFee=$_POST["applicationFee"] ; 
	$publicApplications=$_POST["publicApplications"] ; 
	$milestones=$_POST["milestones"] ; 
	$howDidYouHear=$_POST["howDidYouHear"] ; 
	$requiredDocuments=$_POST["requiredDocuments"] ; 
	$requiredDocumentsText=$_POST["requiredDocumentsText"] ; 
	$requiredDocumentsCompulsory=$_POST["requiredDocumentsCompulsory"] ; 
	$notificationStudentDefault=$_POST["notificationStudentDefault"] ; 
	$notificationParentsDefault=$_POST["notificationParentsDefault"] ; 
	$languageOptionsActive=$_POST["languageOptionsActive"] ; 
	$languageOptionsBlurb=$_POST["languageOptionsBlurb"] ; 
	$languageOptionsLanguageList=$_POST["languageOptionsLanguageList"] ;
	
	//Write to database
	$fail=FALSE ;
	
	try {
		$data=array("value"=>$introduction); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='introduction'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$postscript); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='postscript'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$scholarships); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='scholarships'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$agreement); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='agreement'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$applicationFee); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='applicationFee'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$publicApplications); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='publicApplications'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$milestones); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='milestones'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$howDidYouHear); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='howDidYouHear'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$requiredDocuments); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='requiredDocuments'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$requiredDocumentsText); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='requiredDocumentsText'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$requiredDocumentsCompulsory); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='requiredDocumentsCompulsory'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$notificationStudentDefault); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='notificationStudentDefault'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$notificationParentsDefault); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='notificationParentsDefault'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$languageOptionsActive); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='languageOptionsActive'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$languageOptionsBlurb); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='languageOptionsBlurb'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$fail=TRUE ; 
	}
	
	try {
		$data=array("value"=>$languageOptionsLanguageList); 
		$sql="UPDATE gibbonSetting SET value=:value WHERE scope='Application Form' AND name='languageOptionsLanguageList'" ;
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
?>