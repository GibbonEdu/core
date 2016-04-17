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

$gibbonDepartmentID=$_GET["gibbonDepartmentID"] ;
$gibbonDepartmentStaffID=$_GET["gibbonDepartmentStaffID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/department_manage_edit.php&gibbonDepartmentID=$gibbonDepartmentID" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	
	//Check if school year specified
	if ($gibbonDepartmentID=="" OR $gibbonDepartmentStaffID=="") {
		//Fail1
		$URL.="&deleteReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonDepartmentStaffID"=>$gibbonDepartmentStaffID, "gibbonDepartmentID"=>$gibbonDepartmentID); 
			$sql="SELECT * FROM gibbonDepartmentStaff WHERE gibbonDepartmentStaffID=:gibbonDepartmentStaffID AND gibbonDepartmentID=:gibbonDepartmentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()!=1) {
			//Fail 2
			$URL.="&deleteReturn=fail2" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("gibbonDepartmentStaffID"=>$gibbonDepartmentStaffID); 
				$sql="DELETE FROM gibbonDepartmentStaff WHERE gibbonDepartmentStaffID=:gibbonDepartmentStaffID" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&deleteReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			//Success 0
			$URL.="&deleteReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>