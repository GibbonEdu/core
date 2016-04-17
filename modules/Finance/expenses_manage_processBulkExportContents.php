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

include "../../config.php" ;

//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	$financeExpenseExportIDs=$_SESSION[$guid]["financeExpenseExportIDs"] ;
	$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
	
	if ($financeExpenseExportIDs=="" OR $gibbonFinanceBudgetCycleID=="") {
		print "<div class='error'>" ;
		print __($guid, "List of invoices or budget cycle have not been specified, and so this export cannot be completed.") ;
		print "</div>" ;
	}
	else {
		print "<h1>" ;
		print __($guid, "Expense Export") ;
		print "</h1>" ;
	
		try {
			$whereCount=0 ;
			$whereSched="(" ;
			$data=array(); 
			foreach ($financeExpenseExportIDs AS $gibbonFinanceExpenseID) {
				$data["gibbonFinanceExpenseID" . $whereCount]=$gibbonFinanceExpenseID ;
				$whereSched.="gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID" . $whereCount . " OR " ;
				$whereCount++ ;
			}
			$whereSched=substr($whereSched,0,-4) . ")";
					
			//SQL for billing schedule AND pending
			$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, gibbonFinanceBudgetCycle.name AS budgetCycle, preferredName, surname
			FROM gibbonFinanceExpense
			JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
			JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
			JOIN gibbonFinanceBudgetCycle ON (gibbonFinanceExpense.gibbonFinanceBudgetCycleID=gibbonFinanceBudgetCycle.gibbonFinanceBudgetCycleID)
			WHERE $whereSched" ; 
			$sql.=" ORDER BY FIELD(gibbonFinanceExpense.status, 'Requested','Approved','Rejected','Cancelled','Ordered','Paid'), timestampCreator, surname, preferredName" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th style='width: 120px'>" ;
					print __($guid, "Expense Number") ;
				print "</th>" ;
				print "<th style='width: 120px'>" ;
					print __($guid, "Budget") ;
				print "</th>" ;
				print "<th style='width: 120px'>" ;
					print __($guid, "Budget Cycle") ;
				print "</th>" ;
				print "<th style='width: 120px'>" ;
					print __($guid, "Title") ;
				print "</th>" ;
				print "<th style='width: 120px'>" ;
					print __($guid, "Status") ;
				print "</th>" ;
				print "<th style='width: 100px'>" ;
					print __($guid, "Cost") . " <span style='font-style: italic; font-size: 85%'>(" . $_SESSION[$guid]["currency"] . ")</span>" ;
				print "</th>" ;
				print "<th style='width: 90px'>" ;
					print __($guid, "Staff") ;
				print "</th>" ;
				print "<th style='width: 100px'>" ;
					print __($guid, "Timestamp") . " <span style='font-style: italic; font-size: 85%'>(" . $_SESSION[$guid]["currency"] . ")</span>" ;
				print "</th>" ;
			print "</tr>" ;
		
			$count=0 ;
			while ($row=$result->fetch()) {
				$count++ ;
				//COLOR ROW BY STATUS!
				print "<tr>" ;
					print "<td>" ;
						print $row["gibbonFinanceExpenseID"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["budget"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["budgetCycle"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["title"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["status"] ;
					print "</td>" ;
					print "<td>" ;
						print number_format($row["cost"] , 2, ".", ",") ;
					print "</td>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Staff", true, true) ;
					print "</td>" ;
					print "<td>" ;
						print $row["timestampCreator"] ;
					print "</td>" ;
				print "</tr>" ;
			}
			if ($count==0) {
				print "<tr>" ;
					print "<td colspan=2>" ;
						print __($guid, "There are no records to display.") ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
		
	$_SESSION[$guid]["financeExpenseExportIDs"]=NULL ;
}
?>