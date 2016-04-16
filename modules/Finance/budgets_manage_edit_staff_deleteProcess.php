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

$gibbonFinanceBudgetID=$_GET["gibbonFinanceBudgetID"] ;
$gibbonFinanceBudgetPersonID=$_GET["gibbonFinanceBudgetPersonID"] ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/budgets_manage_edit.php&gibbonFinanceBudgetID=$gibbonFinanceBudgetID" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_edit.php")==FALSE) {
	//Fail 0
	$URL.="&deleteReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	
	//Check if school year specified
	if ($gibbonFinanceBudgetID=="" OR $gibbonFinanceBudgetPersonID=="") {
		//Fail1
		$URL.="&deleteReturn=fail1" ;
		header("Location: {$URL}");
	}
	else {
		try {
			$data=array("gibbonFinanceBudgetPersonID"=>$gibbonFinanceBudgetPersonID, "gibbonFinanceBudgetID"=>$gibbonFinanceBudgetID); 
			$sql="SELECT * FROM gibbonFinanceBudgetPerson WHERE gibbonFinanceBudgetPersonID=:gibbonFinanceBudgetPersonID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
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
				$data=array("gibbonFinanceBudgetPersonID"=>$gibbonFinanceBudgetPersonID); 
				$sql="DELETE FROM gibbonFinanceBudgetPerson WHERE gibbonFinanceBudgetPersonID=:gibbonFinanceBudgetPersonID" ;
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