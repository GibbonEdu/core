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
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPersonID=$_GET["gibbonPersonID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/user_manage_password.php&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage_password.php")==FALSE) {
	//Fail 0
	$URL.="&updateReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if person specified
	if ($gibbonPersonID=="") {
		//Fail1
		$URL.="&updateReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&updateReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			$passwordNew=$_POST["passwordNew"] ;
			$passwordConfirm=$_POST["passwordConfirm"] ;
			$passwordForceReset=$_POST["passwordForceReset"] ;
			
			//Validate Inputs
			if ($passwordNew=="" OR $passwordConfirm=="") {
				//Fail 3
				$URL.="&updateReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check strength of password
				$passwordMatch=doesPasswordMatchPolicy($connection2, $passwordNew) ;
				
				if ($passwordMatch==FALSE) {
					//Fail 6
					$URL.="&updateReturn=fail6" ;
					header("Location: {$URL}");
				}
				else {
					//Check new passwords match
					if ($passwordNew!=$passwordConfirm) {
						$URL.="&editReturn=fail5" ;
						header("Location: {$URL}");
					}
					else {	
						$salt=getSalt() ;
						$passwordStrong=hash("sha256", $salt.$passwordNew) ;
			
						//Write to database
						try {
							$data=array("passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "passwordForceReset"=>$passwordForceReset, "gibbonPersonID"=>$gibbonPersonID); 
							$sql="UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, passwordForceReset=:passwordForceReset, failCount=0 WHERE gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail 2
							$URL.="&updateReturn=fail2" ;
							header("Location: {$URL}");
							exit() ;
						}
	
						//Success 0
						$URL.="&updateReturn=success0" ;
						header("Location: {$URL}");
					}
				}
			}
		}
	}
}
?>