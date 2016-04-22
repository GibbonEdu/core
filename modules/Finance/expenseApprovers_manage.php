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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseApprovers_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Expense Approvers') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, array("success0" => "Your request was completed successfully.")); }

	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
	$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
	try {
		$data=array(); 
		if ($expenseApprovalType=="Chain Of All") {
			$sql="SELECT gibbonFinanceExpenseApprover.*, surname, preferredName FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName" ; 
		}
		else {
			$sql="SELECT gibbonFinanceExpenseApprover.*, surname, preferredName FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
		}
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<p>" ;
		if ($expenseApprovalType=="One Of") {
			if ($budgetLevelExpenseApproval=="Y") {
				print __($guid, "Expense approval has been set as 'One Of', which means that only one of the people listed below (as well as someone with Full budget access) needs to approve an expense before it can go ahead.") ;
			}
			else {
				print __($guid, "Expense approval has been set as 'One Of', which means that only one of the people listed below needs to approve an expense before it can go ahead.") ;
			}
		}
		else if ($expenseApprovalType=="One Of") {
			if ($budgetLevelExpenseApproval=="Y") {
				print __($guid, "Expense approval has been set as 'Two Of', which means that only two of the people listed below (as well as someone with Full budget access) need to approve an expense before it can go ahead.") ;
			}
			else {
				print __($guid, "Expense approval has been set as 'Two Of', which means that only two of the people listed below need to approve an expense before it can go ahead.") ;
			}
		}
		else if ($expenseApprovalType=="Chain Of All") {
			if ($budgetLevelExpenseApproval=="Y") {
				print __($guid, "Expense approval has been set as 'Chain Of All', which means that all of the people listed below (as well as someone with Full budget access) need to approve an expense, in order from lowest to highest, before it can go ahead.") ;
			}
			else {
				print __($guid, "Expense approval has been set as 'Chain Of All', which means that all of the people listed below need to approve an expense, in order from lowest to highest, before it can go ahead.") ;
			}
		}
		else {
			print __($guid, "Expense Approval policies have not been set up: this should be done under Admin > School Admin > Manage Finance Settings.") ;
		}
	print "</p>" ;
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseApprovers_manage_add.php'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				if ($expenseApprovalType=="Chain Of All") {
					print "<th>" ;
						print __($guid, "Sequence Number") ;
					print "</th>" ;
				}
				print "<th>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			while ($row=$result->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Staff", TRUE, TRUE) ;
					print "</td>" ;
					if ($expenseApprovalType=="Chain Of All") {
						print "<td>" ;
							if ($row["sequenceNumber"]!="") {
								print __($guid, $row["sequenceNumber"]) ;
							}
						print "</td>" ;
					}
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseApprovers_manage_edit.php&gibbonFinanceExpenseApproverID=" . $row["gibbonFinanceExpenseApproverID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseApprovers_manage_delete.php&gibbonFinanceExpenseApproverID=" . $row["gibbonFinanceExpenseApproverID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
				
				$count++ ;
			}
		print "</table>" ;
	}
}
?>