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

//Module includes
include "./moduleFunctions.php" ;

//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/systemSettings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/systemSettings.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	$absoluteURL=$_POST["absoluteURL"] ; 	
	$absolutePath=$_POST["absolutePath"] ; 	
	$systemName=$_POST["systemName"] ;
	$indexText=$_POST["indexText"] ;
	$organisationName=$_POST["organisationName"] ;
	$organisationNameShort=$_POST["organisationNameShort"] ;
	$organisationEmail=$_POST["organisationEmail"] ;
	$organisationLogo=$_POST["organisationLogo"] ;
	$organisationAdministrator=$_POST["organisationAdministrator"] ;
	$organisationDBA=$_POST["organisationDBA"] ;
	$organisationHR=$_POST["organisationHR"] ;
	$organisationAdmissions=$_POST["organisationAdmissions"] ;
	$pagination=$_POST["pagination"] ;
	$timezone=$_POST["timezone"] ;
	$country=$_POST["country"] ;
	$firstDayOfTheWeek=$_POST["firstDayOfTheWeek"] ;
	$analytics=$_POST["analytics"] ;
	$emailLink=$_POST["emailLink"] ;
	$webLink=$_POST["webLink"] ;
	$primaryAssessmentScale=$_POST["primaryAssessmentScale"] ;
	$installType=$_POST["installType"] ;
	$statsCollection=$_POST["statsCollection"] ;
	$passwordPolicyMinLength=$_POST["passwordPolicyMinLength"] ;
	$passwordPolicyAlpha=$_POST["passwordPolicyAlpha"] ;
	$passwordPolicyNumeric=$_POST["passwordPolicyNumeric"] ;
	$passwordPolicyNonAlphaNumeric=$_POST["passwordPolicyNonAlphaNumeric"] ;
	$sessionDuration=$_POST["sessionDuration"] ;
	$allowableHTML=$_POST["allowableHTML"] ;
	$currency=$_POST["currency"] ;
	$gibboneduComOrganisationName=$_POST["gibboneduComOrganisationName"] ;
	$gibboneduComOrganisationKey=$_POST["gibboneduComOrganisationKey"] ;
	
	
	//Validate Inputs
	if ($absoluteURL=="" OR $systemName=="" OR $organisationLogo=="" OR $indexText=="" OR $organisationName=="" OR $organisationNameShort=="" OR $organisationAdministrator=="" OR $organisationDBA=="" OR $organisationHR=="" OR $organisationAdmissions=="" OR $pagination=="" OR (!(is_numeric($pagination))) OR $timezone=="" OR $installType=="" OR $statsCollection=="" OR $passwordPolicyMinLength=="" OR $passwordPolicyAlpha=="" OR $passwordPolicyNumeric=="" OR $passwordPolicyNonAlphaNumeric=="" OR $firstDayOfTheWeek=="" OR ($firstDayOfTheWeek!="Monday" AND $firstDayOfTheWeek!="Sunday") OR $currency=="") {
		//Fail 3
		$URL.="&updateReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {	
		//Write to database
		$fail=FALSE ;
		
		try {
			$data=array("absoluteURL"=>$absoluteURL); 
			$sql="UPDATE gibbonSetting SET value=:absoluteURL WHERE scope='System' AND name='absoluteURL'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("absolutePath"=>$absolutePath); 
			$sql="UPDATE gibbonSetting SET value=:absolutePath WHERE scope='System' AND name='absolutePath'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("systemName"=>$systemName); 
			$sql="UPDATE gibbonSetting SET value=:systemName WHERE scope='System' AND name='systemName'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("indexText"=>$indexText); 
			$sql="UPDATE gibbonSetting SET value=:indexText WHERE scope='System' AND name='indexText'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("organisationName"=>$organisationName); 
			$sql="UPDATE gibbonSetting SET value=:organisationName WHERE scope='System' AND name='organisationName'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("organisationNameShort"=>$organisationNameShort); 
			$sql="UPDATE gibbonSetting SET value=:organisationNameShort WHERE scope='System' AND name='organisationNameShort'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("organisationLogo"=>$organisationLogo); 
			$sql="UPDATE gibbonSetting SET value=:organisationLogo WHERE scope='System' AND name='organisationLogo'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("organisationEmail"=>$organisationEmail); 
			$sql="UPDATE gibbonSetting SET value=:organisationEmail WHERE scope='System' AND name='organisationEmail'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		//ADMINISTRATORS
		try {
			$data=array("organisationAdministrator"=>$organisationAdministrator); 
			$sql="UPDATE gibbonSetting SET value=:organisationAdministrator WHERE scope='System' AND name='organisationAdministrator'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		//Update session variables
		try {
			$data=array("gibbonPersonID"=>$organisationAdministrator); 
			$sql="SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		if ($result->rowCount()!=1) {
			$fail=TRUE ;
		}
		else {
			$row=$result->fetch() ;
			$_SESSION[$guid]["organisationAdministratorName"]=formatName("", $row["preferredName"], $row["surname"], "Staff", FALSE, TRUE) ;
			$_SESSION[$guid]["organisationAdministratorEmail"]=$row["email"] ;
		}
		
		
		try {
			$data=array("organisationDBA"=>$organisationDBA); 
			$sql="UPDATE gibbonSetting SET value=:organisationDBA WHERE scope='System' AND name='organisationDBA'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		//Update session variables
		try {
			$data=array("gibbonPersonID"=>$organisationDBA); 
			$sql="SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		if ($result->rowCount()!=1) {
			$fail=TRUE ;
		}
		else {
			$row=$result->fetch() ;
			$_SESSION[$guid]["organisationDBAName"]=formatName("", $row["preferredName"], $row["surname"], "Staff", FALSE, TRUE) ;
			$_SESSION[$guid]["organisationDBAEmail"]=$row["email"] ;
		}
		
		
		try {
			$data=array("organisationHR"=>$organisationHR); 
			$sql="UPDATE gibbonSetting SET value=:organisationHR WHERE scope='System' AND name='organisationHR'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		//Update session variables
		try {
			$data=array("gibbonPersonID"=>$organisationHR); 
			$sql="SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		if ($result->rowCount()!=1) {
			$fail=TRUE ;
		}
		else {
			$row=$result->fetch() ;
			$_SESSION[$guid]["organisationHRName"]=formatName("", $row["preferredName"], $row["surname"], "Staff", FALSE, TRUE) ;
			$_SESSION[$guid]["organisationHREmail"]=$row["email"] ;
		}
		
		
		
		try {
			$data=array("organisationAdmissions"=>$organisationAdmissions); 
			$sql="UPDATE gibbonSetting SET value=:organisationAdmissions WHERE scope='System' AND name='organisationAdmissions'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		//Update session variables
		try {
			$data=array("gibbonPersonID"=>$organisationAdmissions); 
			$sql="SELECT surname, preferredName, email FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		if ($result->rowCount()!=1) {
			$fail=TRUE ;
		}
		else {
			$row=$result->fetch() ;
			$_SESSION[$guid]["organisationAdmissionsName"]=formatName("", $row["preferredName"], $row["surname"], "Staff", FALSE, TRUE) ;
			$_SESSION[$guid]["organisationAdmissionsEmail"]=$row["email"] ;
		}
		
		
		try {
			$data=array("pagination"=>$pagination); 
			$sql="UPDATE gibbonSetting SET value=:pagination WHERE scope='System' AND name='pagination'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("country"=>$country); 
			$sql="UPDATE gibbonSetting SET value=:country WHERE scope='System' AND name='country'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("firstDayOfTheWeek"=>$firstDayOfTheWeek); 
			$sql="UPDATE gibbonSetting SET value=:firstDayOfTheWeek WHERE scope='System' AND name='firstDayOfTheWeek'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		if (setFirstDayOfTheWeek($connection2, $firstDayOfTheWeek, $databaseName)!=TRUE) {
			$fail=TRUE ;
		}
		
		try {
			$data=array("currency"=>$currency); 
			$sql="UPDATE gibbonSetting SET value=:currency WHERE scope='System' AND name='currency'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("gibboneduComOrganisationName"=>$gibboneduComOrganisationName); 
			$sql="UPDATE gibbonSetting SET value=:gibboneduComOrganisationName WHERE scope='System' AND name='gibboneduComOrganisationName'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("gibboneduComOrganisationKey"=>$gibboneduComOrganisationKey); 
			$sql="UPDATE gibbonSetting SET value=:gibboneduComOrganisationKey WHERE scope='System' AND name='gibboneduComOrganisationKey'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("timezone"=>$timezone); 
			$sql="UPDATE gibbonSetting SET value=:timezone WHERE scope='System' AND name='timezone'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("analytics"=>$analytics); 
			$sql="UPDATE gibbonSetting SET value=:analytics WHERE scope='System' AND name='analytics'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("emailLink"=>$emailLink); 
			$sql="UPDATE gibbonSetting SET value=:emailLink WHERE scope='System' AND name='emailLink'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("webLink"=>$webLink); 
			$sql="UPDATE gibbonSetting SET value=:webLink WHERE scope='System' AND name='webLink'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("primaryAssessmentScale"=>$primaryAssessmentScale); 
			$sql="UPDATE gibbonSetting SET value=:primaryAssessmentScale WHERE scope='System' AND name='primaryAssessmentScale'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		
		try {
			$data=array("installType"=>$installType); 
			$sql="UPDATE gibbonSetting SET value=:installType WHERE scope='System' AND name='installType'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("statsCollection"=>$statsCollection); 
			$sql="UPDATE gibbonSetting SET value=:statsCollection WHERE scope='System' AND name='statsCollection'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		
		try {
			$data=array("passwordPolicyMinLength"=>$passwordPolicyMinLength); 
			$sql="UPDATE gibbonSetting SET value=:passwordPolicyMinLength WHERE scope='System' AND name='passwordPolicyMinLength'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		try {
			$data=array("passwordPolicyAlpha"=>$passwordPolicyAlpha); 
			$sql="UPDATE gibbonSetting SET value=:passwordPolicyAlpha WHERE scope='System' AND name='passwordPolicyAlpha'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		try {
			$data=array("passwordPolicyNumeric"=>$passwordPolicyNumeric); 
			$sql="UPDATE gibbonSetting SET value=:passwordPolicyNumeric WHERE scope='System' AND name='passwordPolicyNumeric'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}
		try {
			$data=array("passwordPolicyNonAlphaNumeric"=>$passwordPolicyNonAlphaNumeric); 
			$sql="UPDATE gibbonSetting SET value=:passwordPolicyNonAlphaNumeric WHERE scope='System' AND name='passwordPolicyNonAlphaNumeric'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}	
		try {
			$data=array("allowableHTML"=>$allowableHTML); 
			$sql="UPDATE gibbonSetting SET value=:allowableHTML WHERE scope='System' AND name='allowableHTML'" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$fail=TRUE ;
		}	
		try {
			$data=array("sessionDuration"=>$sessionDuration); 
			$sql="UPDATE gibbonSetting SET value=:sessionDuration WHERE scope='System' AND name='sessionDuration'" ;
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