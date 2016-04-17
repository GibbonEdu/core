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

$gibbonApplicationFormID=$_POST["gibbonApplicationFormID"] ;
$gibbonSchoolYearID=$_POST["gibbonSchoolYearID"] ;
$search=$_GET["search"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/applicationForm_manage_reject.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ;
$URLReject=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ;

if (isActionAccessible($guid, $connection2, "/modules/Students/applicationForm_manage_reject.php")==FALSE) {
	//Fail 0
	$URL.="&rejectReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Check if school year specified
	
	if ($gibbonApplicationFormID=="" OR $gibbonSchoolYearID=="") {
		//Fail1
		$URL.="&rejectReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&rejectReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&rejectReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
				$sql="UPDATE gibbonApplicationForm SET status='Rejected' WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&rejectReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			//Success 0
			$URLReject=$URLReject . "&rejectReturn=success0" ;
			header("Location: {$URLReject}");
		}
	}
}
?>