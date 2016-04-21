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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenseApprovers_manage_add.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseApprovers_manage_add.php")==FALSE) {
	//Fail 0
	$URL.="&return=error0" ;
	header("Location: {$URL}");
}
else {
	//Proceed!
	//Validate Inputs
	$gibbonPersonID=$_POST["gibbonPersonID"] ;
	$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
	$sequenceNumber=NULL ;
	if ($expenseApprovalType=="Chain Of All") {
		$sequenceNumber=abs($_POST["sequenceNumber"]) ;
	}
	
	if ($gibbonPersonID=="" OR ($expenseApprovalType=="Y" AND $sequenceNumber=="")) {
		//Fail 3
		$URL.="&return=error1" ;
		header("Location: {$URL}");
	}
	else {
		//Check unique inputs for uniquness
		try {
			if ($expenseApprovalType=="Chain Of All") {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "sequenceNumber"=>$sequenceNumber); 
				$sql="SELECT * FROM gibbonFinanceExpenseApprover WHERE gibbonPersonID=:gibbonPersonID OR sequenceNumber=:sequenceNumber" ;
			}
			else {
				$data=array("gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT * FROM gibbonFinanceExpenseApprover WHERE gibbonPersonID=:gibbonPersonID" ;
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			//Fail 2
			$URL.="&return=error2" ;
			header("Location: {$URL}");
			exit() ;
		}
		
		if ($result->rowCount()>0) {
			//Fail 4
			$URL.="&return=error3" ;
			header("Location: {$URL}");
		}
		else {	
			//Write to database
			try {
				$data=array("gibbonPersonID"=>$gibbonPersonID, "sequenceNumber"=>$sequenceNumber, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestampCreator"=>date('Y-m-d H:i:s', time())); 
				$sql="INSERT INTO gibbonFinanceExpenseApprover SET gibbonPersonID=:gibbonPersonID, sequenceNumber=:sequenceNumber, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			//Success 0
			$URL.="&return=success0" ;
			header("Location: {$URL}");
		}
	}
}
?>
