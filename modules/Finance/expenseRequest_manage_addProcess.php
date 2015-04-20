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

$gibbonFinanceBudgetCycleID=$_POST["gibbonFinanceBudgetCycleID"] ;
$gibbonFinanceBudgetID=$_POST["gibbonFinanceBudgetID"] ;
$status=$_POST["status"] ;
		
if ($gibbonFinanceBudgetCycleID=="" OR $gibbonFinanceBudgetID=="" OR $status=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID=$gibbonFinanceBudgetID&status=$status" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage_add.php")==FALSE) {
		//Fail 0
		$URL.="&addReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		$title=$_POST["title"] ;
		$body=$_POST["body"] ;
		$cost=$_POST["cost"] ;
			
		if ($title=="" OR $body=="" OR $cost=="") {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Write to database
			try {
				$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceBudgetID"=>$gibbonFinanceBudgetID, "title"=>$title, "body"=>$body, "status"=>$status, "cost"=>$cost, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="INSERT INTO gibbonFinanceExpense SET gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID, title=:title, body=:body, status=:status, cost=:cost, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='" . date("Y-m-d H:i:s") . "'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
	
			//Success 0
			$URL.="&addReturn=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>