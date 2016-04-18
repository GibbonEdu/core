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

include "./functions.php" ;
include "./config.php" ;

//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/publicRegistration.php" ;

$proceed=FALSE ;

if (isset($_SESSION[$guid]["username"])==FALSE) {
	$enablePublicRegistration=getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration') ;
	if ($enablePublicRegistration=="Y") {
		$proceed=TRUE ;
	}
}

if ($proceed==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Lock activities table
	try {
		$data=array(); 
		$sql="LOCK TABLES gibbonPerson WRITE, gibbonSetting READ, gibbonNotification WRITE, gibbonModule WRITE" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		//Fail 2
		$URL.="&addReturn=fail2" ;
		header("Location: {$URL}");
		exit() ;
	}
	
	//Proceed!
	$surname=$_POST["surname"] ;
	$firstName=$_POST["firstName"] ;
	$preferredName=$firstName ;
	$officialName=$firstName . " " . $surname ;
	$gender=$_POST["gender"] ;
	$dob=$_POST["dob"] ;
	if ($dob=="") {
		$dob=NULL ;
	}
	else {
		$dob=dateConvert($guid, $dob) ;
	}
	$email=$_POST["email"] ;
	$username=$_POST["username"] ;
	$password=$_POST["passwordNew"] ;
	$salt=getSalt() ;
	$passwordStrong=hash("sha256", $salt.$password) ;
	$status=getSettingByScope($connection2, 'User Admin', 'publicRegistrationDefaultStatus') ;
	$gibbonRoleIDPrimary=getSettingByScope($connection2, 'User Admin', 'publicRegistrationDefaultRole') ;
	$gibbonRoleIDAll=$gibbonRoleIDPrimary ;
	
	if ($surname=="" OR $firstName=="" OR $preferredName=="" OR $officialName=="" OR $gender=="" OR $dob=="" OR $email=="" OR $username=="" OR $password=="" OR $gibbonRoleIDPrimary=="" OR $gibbonRoleIDPrimary=="" OR ($status!="Pending Approval" AND $status!="Full")) {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check strength of password
		$passwordMatch=doesPasswordMatchPolicy($connection2, $password) ;
		
		if ($passwordMatch==FALSE) {
			//Fail 7
			$URL.="&addReturn=fail7" ;
			header("Location: {$URL}");
		}
		else {
			//Check uniqueness of username
			try {
				$data=array("username"=>$username, "email"=>$email); 
				$sql="SELECT * FROM gibbonPerson WHERE username=:username OR email=:email" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
		
			if ($result->rowCount()>0) {
				//Fail 4
				$URL.="&addReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Check publicRegistrationMinimumAge
				$publicRegistrationMinimumAge=getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge') ;

				$ageFail=FALSE ;
				if ($publicRegistrationMinimumAge=="") {
					$ageFail=TRUE ;
				}
				else if ($publicRegistrationMinimumAge>0 AND $publicRegistrationMinimumAge>getAge($guid, dateConvertToTimestamp($dob), TRUE, TRUE)) {
					$ageFail=TRUE ;
				}
			
				if ($ageFail==TRUE) {
					//Fail 5
					$URL.="&addReturn=fail5" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("surname"=>$surname, "firstName"=>$firstName, "preferredName"=>$preferredName, "officialName"=>$officialName, "gender"=>$gender, "dob"=>$dob, "email"=>$email, "username"=>$username, "passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "status"=>$status, "gibbonRoleIDPrimary"=>$gibbonRoleIDPrimary, "gibbonRoleIDAll"=>$gibbonRoleIDAll); 
						$sql="INSERT INTO gibbonPerson SET surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, gender=:gender, dob=:dob, email=:email, username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, status=:status, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						print $e->getMessage() ; exit() ;
						$URL.="&addReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
					
					$gibbonPersonID=$connection2->lastInsertId();

					if ($status=="Pending Approval") {
						//Attempt to notify Admissions
						if ($_SESSION[$guid]["organisationAdmissions"]) {
							$notificationText=sprintf(__($guid, 'An new public registration, for %1$s, is pending approval.'), formatName("", $preferredName, $surname, "Student")) ;
							setNotification($connection2, $guid, $_SESSION[$guid]["organisationAdmissions"], $notificationText, "User Admin", "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID&search=") ;
						}
						
						//Success 1
						$URL.="&addReturn=success1" ;
						header("Location: {$URL}");
					}
					else {
						//Success 0
						$URL.="&addReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>