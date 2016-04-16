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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/budgetCycles_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/budgetCycles_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$name=$_POST["name"] ;
	$status=$_POST["status"] ;
	$sequenceNumber=$_POST["sequenceNumber"] ;
	$dateStart=dateConvert($guid, $_POST["dateStart"]) ;
	$dateEnd=dateConvert($guid, $_POST["dateEnd"]) ;
	
	if ($name=="" OR $status=="" OR $sequenceNumber=="" OR is_numeric($sequenceNumber)==FALSE OR $dateStart=="" OR $dateEnd=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			$data=array("name"=>$name, "sequenceNumber"=>$sequenceNumber); 
			$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE name=:name OR sequenceNumber=:sequenceNumber" ;
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
			//Write to database
			try {
				$data=array("name"=>$name, "status"=>$status, "sequenceNumber"=>$sequenceNumber, "dateStart"=>$dateStart, "dateEnd"=>$dateEnd, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="INSERT INTO gibbonFinanceBudgetCycle SET name=:name, status=:status, sequenceNumber=:sequenceNumber, dateStart=:dateStart, dateEnd=:dateEnd, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='" . date("Y-m-d H:i:s") . "'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				print $e->getMessage() ; exit() ;
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
			}
			
			$gibbonFinanceBudgetCycleID=$connection2->lastInsertID() ;
			
			//UPDATE CYCLE ALLOCATION VALUES
			$partialFail=FALSE ;
			if (isset($_POST["values"])) {
				$values=$_POST["values"] ;
				$gibbonFinanceBudgetIDs=$_POST["gibbonFinanceBudgetIDs"] ;
				$count=0 ;
				foreach ($values AS $value) {
					try {
						$data=array("value"=>$value, "gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceBudgetID"=>$gibbonFinanceBudgetIDs[$count]); 
						$sql="INSERT INTO gibbonFinanceBudgetCycleAllocation SET value=:value, gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) {
						$partialFail=TRUE ;
					}
					$count++ ;
				}
			}
			
			if ($partialFail==TRUE) {
				//Fail 5
				$URL.="&addReturn=fail5" ;
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
?>