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

//Start session
@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Check to see if academic year id variables are set, if not set them 
if (($_SESSION[$guid]["gibbonAcademicYearID"]=="") OR ($_SESSION[$guid]["gibbonAcademicYearID"]=="")) {
	setCurrentSchoolYear($guid, $connection2) ;
}

//Create password
$password=randomPassword(8);

//Check email address is not blank
$input=$_POST["email"] ;

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=passwordReset.php" ;
	
if ($input=="") {
	$URL=$URL. "&editReturn=fail0" ;
	header("Location: {$URL}");
}
//Otherwise proceed
else {
	//If answer insert fails...
	$salt=getSalt() ;
	$passwordStrong=hash("sha256", $salt.$password) ;
	try {
		$data=array("email"=>$input, "username"=>$input); 
		$sql="SELECT gibbonPersonID, email, username FROM gibbonPerson WHERE (email=:email OR username=:username) AND gibbonPerson.status='Full' AND NOT email=''";
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$URL=$URL. "&editReturn=fail1" ;
		header("Location: {$URL}");
		exit() ;
	}

	if ($result->rowCount()!=1) {
		$URL=$URL. "&editReturn=fail2" ;
		header("Location: {$URL}");
	}
	else {
		$row=$result->fetch() ; 
		$gibbonPersonID=$row["gibbonPersonID"] ;
		$email=$row["email"] ;
		$username=$row["username"] ;
		
		try {
			$data=array("passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "gibbonPersonID"=>$gibbonPersonID); 
			$sql="UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, failCount=0, passwordForceReset='Y' WHERE gibbonPersonID=:gibbonPersonID";
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			$URL=$URL. "&editReturn=fail1" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			$URL=$URL. "&editReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$to=$email;
			$subject=$_SESSION[$guid]["organisationNameShort"] . " Gibbon Password Reset";
			$body="Your new password for account $username is as follows:\n\n$password\n\nPlease log in an change your password as soon as possible.\n\n" . $_SESSION[$guid]["systemName"] . " Administrator";
			$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;

			if (mail($to, $subject, $body, $headers)) {
				$_SESSION[$guid]["password"]=$passwordHash ;
				$URL=$URL. "&editReturn=success0" ;
				header("Location: {$URL}");
			}
			else {
				$URL=$URL. "&editReturn=fail3" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>