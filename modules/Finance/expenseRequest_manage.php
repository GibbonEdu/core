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

//Module includes
include "./modules/Finance/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('My Expense Requests') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["editReturn"])) { $editReturn=$_GET["editReturn"] ; } else { $editReturn="" ; }
	$editReturnMessage="" ;
	$class="error" ;
	if (!($editReturn=="")) {
		if ($editReturn=="success0") {
			$editReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $editReturnMessage;
		print "</div>" ;
	} 
	
	print "<p>" ;
		print _("This action allows you to create and manage expense requests, which will be submitted for approval to the relevant individuals. You will be notified when a request has been approved.") . "<br/>" ;
	print "</p>" ;
	
	//Check if have Full or Write in any budgets
	$budgets=getBudgetsByPerson($connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
	$budgetsAccess=FALSE ;
	if (is_array($budgets)>0) {
		foreach ($budgets AS $budget) {
			if ($budget[2]=="Full" OR $budget[2]=="Write") {
				$budgetsAccess=TRUE ;
			}
		}
	}
	if ($budgetsAccess==FALSE) {
		print "<div class='error'>" ;
			print _("You do not have Full or Write access to any budgets.") ;
		print "</div>" ;
	}
	else {
		//Get and check settings
		$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
		$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
		$expenseRequestTemplate=getSettingByScope($connection2, "Finance", "expenseRequestTemplate") ;
		if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
			print "<div class='error'>" ;
				print _("An error has occurred with your expense and budget settings.") ;
			print "</div>" ;
		}
		else {
			//Check if there are approvers
			try {
				$data=array(); 
				$sql="SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { print $e->getMessage() ; }
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
					print _("An error has occurred with your expense and budget settings.") ;
				print "</div>" ;
			}
			else {
				//Ready to go!
	
				$gibbonFinanceBudgetCycleID="" ;
				if (isset($_GET["gibbonFinanceBudgetCycleID"])) {
					$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
				}
				if ($gibbonFinanceBudgetCycleID=="") {
					try {
						$data=array(); 
						$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE status='Current'" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($result->rowcount()!=1) {
						print "<div class='error'>" ;
							print _("The Current budget cycle cannot be determined.") ;
						print "</div>" ;
					}
					else {
						$row=$result->fetch() ;
						$gibbonFinanceBudgetCycleID=$row["gibbonFinanceBudgetCycleID"] ;
						$gibbonFinanceBudgetCycleName=$row["name"] ;
					}
				}
				if ($gibbonFinanceBudgetCycleID!="") {
					try {
						$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID); 
						$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($result->rowcount()!=1) {
						print "<div class='error'>" ;
							print _("The specified budget cycle cannot be determined.") ;
						print "</div>" ;
					}
					else {
						$row=$result->fetch() ;
						$gibbonFinanceBudgetCycleName=$row["name"] ;
					}
					
					print "<h2>" ;
						print $gibbonFinanceBudgetCycleName ;
					print "</h2>" ;
		
					print "<div class='linkTop'>" ;
						//Print year picker
						$previousCycle=getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2) ;
						if ($previousCycle!=FALSE) {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=" . $previousCycle . "'>" . _('Previous Cycle') . "</a> " ;
						}
						else {
							print _("Previous Cycle") . " " ;
						}
						print " | " ;
						$nextCycle=getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2) ;
						if ($nextCycle!=FALSE) {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=" . $nextCycle . "'>" . _('Next Cycle') . "</a> " ;
						}
						else {
							print _("Next Cycle") . " " ;
						}
					print "</div>" ;
	
					$status=NULL ;
					if (isset($_GET["status"])) {
						$status=$_GET["status"] ;
					}
					$gibbonFinanceBudgetID=NULL ;
					if (isset($_GET["gibbonFinanceBudgetID"])) {
						$gibbonFinanceBudgetID=$_GET["gibbonFinanceBudgetID"] ;
					}
					
					print "<h3>" ;
						print _("Filters") ;
					print "</h3>" ;
					print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php'>" ;
						print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
							?>
							<tr>
								<td> 
									<b><?php print _('Status') ?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<?php
									print "<select name='status' id='status' style='width:302px'>" ;
										$selected="" ;
										if ($status=="") {
											$selected="selected" ;
										}
										print "<option $selected value='%'>" . _('All') . "</option>" ;
										$selected="" ;
										if ($status=="Requested") {
											$selected="selected" ;
										}
										print "<option $selected value='Requested'>" . _('Requested') . "</option>" ;
										$selected="" ;
										if ($status=="Approved") {
											$selected="selected" ;
										}
										print "<option $selected value='Approved'>" . _('Approved') . "</option>" ;
										$selected="" ;
										if ($status=="Rejected") {
											$selected="selected" ;
										}
										print "<option $selected value='Rejected'>" . _('Rejected') . "</option>" ;
										$selected="" ;
										if ($status=="Cancelled") {
											$selected="selected" ;
										}
										print "<option $selected value='Cancelled'>" . _('Cancelled') . "</option>" ;
										$selected="" ;
										if ($status=="Ordered") {
											$selected="selected" ;
										}
										print "<option $selected value='Ordered'>" . _('Ordered') . "</option>" ;
										$selected="" ;
										if ($status=="Paid") {
											$selected="selected" ;
										}
										print "<option $selected value='Paid'>" . _('Paid') . "</option>" ;
									print "</select>" ;
									?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print _('Budget') ?></b><br/>
									<span style="font-size: 90%"><i></i></span>
								</td>
								<td class="right">
									<?php
									print "<select name='gibbonFinanceBudgetID' id='gibbonFinanceBudgetID' style='width:302px'>" ;
										$selected="" ;
										if ($gibbonFinanceBudgetID=="") {
											$selected="selected" ;
										}
										print "<option $selected value=''>" . _('All') . "</option>" ;
										foreach ($budgets AS $budget) {
											$selected="" ;
											if ($gibbonFinanceBudgetID==$budget[0]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $budget[0] . "'>" . $budget[1] . "</option>" ;
										}
									print "</select>" ;
									?>
								</td>
							</tr>
							<?php
				
							print "<tr>" ;
								print "<td class='right' colspan=2>" ;
									print "<input type='hidden' name='gibbonFinanceBudgetCycleID' value='$gibbonFinanceBudgetCycleID'>" ;
									print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID'>" . _('Clear Filters') . "</a> " ;
									print "<input type='submit' value='" . _('Go') . "'>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					print "</form>" ;
		
					try {
						//Add in filter wheres
						$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$whereBudget="" ;
						if ($gibbonFinanceBudgetID!="") {
							$data["gibbonFinanceBudgetID"]=$gibbonFinanceBudgetID ;
							$whereBudget.=" AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
						}
						$whereStatus="" ;
						if ($status!="") {
							$data["status"]=$status ;
							$whereStatus.=" AND status=:status" ;
						}
						//SQL for billing schedule AND pending
						$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpense.gibbonPersonIDCreator=:gibbonPersonIDCreator $whereBudget $whereStatus" ; 
						$sql.=" ORDER BY FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC" ; 
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
		
					if ($result->rowCount()<1) {
						print "<h3>" ;
						print _("View") ;
						print "</h3>" ;
			
						print "<div class='linkTop' style='text-align: right'>" ;
							print "<a style='margin-right: 3px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=$status&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a><br/>" ;
						print "</div>" ;
			
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<h3>" ;
						print _("View") ;
						print "<span style='font-weight: normal; font-style: italic; font-size: 55%'> " . sprintf(_('%1$s expense requests in current view'), $result->rowCount()) . "</span>" ;
						print "</h3>" ;

						print "<div class='linkTop'>" ;
							print "<a style='margin-right: 3px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=$status&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a><br/>" ;
						print "</div>" ;	
			
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th style='width: 110px'>" ;
									print _("Title") . "<br/>" ;
								print "</th>" ;
								print "<th style='width: 110px'>" ;
									print _("Budget") ;
								print "</th>" ;
								print "<th style='width: 100px'>" ;
									print _("Status") ;
								print "</th>" ;
								print "<th style='width: 90px'>" ;
									print _("Cost") . "<br/><span style='font-style: italic; font-size: 75%'>(" . $_SESSION[$guid]["currency"] . ")</span><br/>" ;
								print "</th>" ;
								print "<th style='width: 120px'>" ;
									print _("Date") ;
								print "</th>" ;
								print "<th style='width: 140px'>" ;
									print _("Actions") ;
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
								$count++ ;
		
								//Color row by status
								if ($row["status"]=="Approved") {
									$rowNum="current" ;	
								}
								if ($row["status"]=="Rejected" OR $row["status"]=="Cancelled") {
									$rowNum="error" ;	
								}
		
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print "<b>" . $row["title"] . "</b><br/>" ;
									print "</td>" ;
									print "<td>" ;
										print $row["budget"] ;
									print "</td>" ;
									print "<td>" ;
										print $row["status"] ;
									print "</td>" ;
									print "<td>" ;
										print number_format($row["cost"] , 2, ".", ",") ;
									print "</td>" ;
									print "<td>" ;
										print dateConvertBack($guid, substr($row["timestampCreator"], 0, 10)) ;
									print "</td>" ;
									print "<td>" ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_view.php&gibbonFinanceExpenseID=" . $row["gibbonFinanceExpenseID"] . "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=$status&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'><img title='" . _('View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
										if ($row["status"]=="Approved" AND $row["purchaseBy"]=="Self") {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_reimburse.php&gibbonFinanceExpenseID=" . $row["gibbonFinanceExpenseID"] . "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=$status&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'><img title='" . _('Request Reimbursement') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/gift.png'/></a> " ;
										}
									print "</td>" ;
								print "</tr>" ;
							}
							print "<input type=\"hidden\" name=\"address\" value=\"" . $_SESSION[$guid]["address"] . "\">" ;
						
						print "</table>" ;
					}
				}
			}
		}
	}
}
?>