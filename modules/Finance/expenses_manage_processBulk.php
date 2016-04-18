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

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$action=$_POST["action"] ;
$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;

if ($gibbonFinanceBudgetCycleID=="" OR $action=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage.php")==FALSE) {
		//Fail 0
		$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		$gibbonFinanceExpenseIDs=$_POST["gibbonFinanceExpenseIDs"] ;
		if (count($gibbonFinanceExpenseIDs)<1) {
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
			$partialFail=FALSE ;
			//Export
			if ($action=="export") {
				$_SESSION[$guid]["financeExpenseExportIDs"]=$gibbonFinanceExpenseIDs ;
				
				$exp=new Gibbon\Excel();
				$exp->exportWithPage($guid, "./expenses_manage_processBulkExportContents.php","invoices.xls", "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID");
				
				// THIS CODE HAS BEEN COMMENTED OUT, AS THE EXPORT RETURNS WITHOUT IT...NOT SURE WHY!
				//Success 0
				//$URL.="&bulkReturn=success0" ;
				//header("Location: {$URL}");
			}
			else {
				$URL.="&return=error1" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>