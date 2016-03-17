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
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . __($guid, 'Manage Expenses') . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Expense') . "</div>" ;
		print "</div>" ;
	
		//Check if params are specified
		$gibbonFinanceExpenseID=$_GET["gibbonFinanceExpenseID"] ;
		$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
		$status2=$_GET["status2"] ;
		$gibbonFinanceBudgetID2=$_GET["gibbonFinanceBudgetID2"] ;
		if ($gibbonFinanceExpenseID=="" OR $gibbonFinanceBudgetCycleID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			//Check if have Full or Write in any budgets
			$budgets=getBudgetsByPerson($connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
			$budgetsAccess=FALSE ;
			if ($highestAction=="Manage Expenses_all") { //Access to everything {
				$budgetsAccess=TRUE ;
			}
			else {
				//Check if have Full or Write in any budgets
				$budgets=getBudgetsByPerson($connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
				foreach ($budgets AS $budget) {
					if ($budget[2]=="Full" OR $budget[2]=="Write") {
						$budgetsAccess=TRUE ;
					}
				}
			}
			
			if ($budgetsAccess==FALSE) {
				print "<div class='error'>" ;
					print __($guid, "You do not have Full or Write access to any budgets.") ;
				print "</div>" ;
			}
			else {
				//Get and check settings
				$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
				$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
				$expenseRequestTemplate=getSettingByScope($connection2, "Finance", "expenseRequestTemplate") ;
				if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
					print "<div class='error'>" ;
						print __($guid, "An error has occurred with your expense and budget settings.") ;
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
							print __($guid, "An error has occurred with your expense and budget settings.") ;
						print "</div>" ;
					}
					else {
						//Ready to go! Just check record exists and we have access, and load it ready to use...
						try {
							//Set Up filter wheres
							$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
							//GET THE DATA ACCORDING TO FILTERS
							if ($highestAction=="Manage Expenses_all") { //Access to everything
								$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC" ; 
							}
							else { //Access only to own budgets
								$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
								$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, access
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID 
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC" ; 
							}
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
		
						if ($result->rowCount()!=1) {
							print "<div class='error'>" ;
								print __($guid, "The specified record cannot be found.") ;
							print "</div>" ;
						}
						else {
							//Let's go!
							$row=$result->fetch() ;
					
							if ($status2!="" OR $gibbonFinanceBudgetID2!="") {
								print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>" . __($guid, 'Back to Search Results') . "</a>" ;
								print "</div>" ;
							}
							?>
							<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_addProcess.php" ?>">
								<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print __($guid, 'Basic Information') ?></h3>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'> 
											<b><?php print __($guid, 'Budget Cycle') ?></b><br/>
										</td>
										<td class="right">
											<?php
											$yearName="" ;
											try {
												$dataYear=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID); 
												$sqlYear="SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID" ;
												$resultYear=$connection2->prepare($sqlYear);
												$resultYear->execute($dataYear);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultYear->rowCount()==1) {
												$rowYear=$resultYear->fetch() ;
												$yearName=$rowYear["name"] ;
											}
											?>
											<input readonly name="name" id="name" maxlength=20 value="<?php print $yearName ?>" type="text" style="width: 300px">
											<input name="gibbonFinanceBudgetCycleID" id="gibbonFinanceBudgetCycleID" maxlength=20 value="<?php print $gibbonFinanceBudgetCycleID ?>" type="hidden" style="width: 300px">
											<script type="text/javascript">
												var gibbonFinanceBudgetCycleID=new LiveValidation('gibbonFinanceBudgetCycleID');
												gibbonFinanceBudgetCycleID.add(Validate.Presence);
											</script>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'> 
											<b><?php print __($guid, 'Budget') ?></b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=20 value="<?php print $row["budget"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print __($guid, 'Title') ?></b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php print $row["title"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print __($guid, 'Status') ?></b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php print $row["status"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php print __($guid, 'Description') ?></b>
											<?php 
												print "<p>" ;
													print $row["body"] ;
												print "</p>"	
											?>
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print __($guid, 'Total Cost') ?></b><br/>
											<span style="font-size: 90%">
												<i>
												<?php
												if ($_SESSION[$guid]["currency"]!="") {
													print sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]["currency"]) ;
												}
												else {
													print __($guid, "Numeric value of the fee.") ;
												}
												?>
												</i>
											</span>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php print $row["cost"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print __($guid, 'Count Against Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="countAgainstBudget" id="countAgainstBudget" maxlength=60 value="<?php print ynExpander($guid, $row["countAgainstBudget"]) ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print __($guid, 'Purchase By') ?></b><br/>
										</td>
										<td class="right">
											<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php print $row["purchaseBy"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php print __($guid, 'Purchase Details') ?></b>
											<?php 
												print "<p>" ;
													print $row["purchaseDetails"] ;
												print "</p>"	
											?>
										</td>
									</tr>
							
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print __($guid, 'Log') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<?php
											print getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2) ;
											?>
										</td>
									</tr>
									
									<?php
									if ($row["status"]=="Paid") {
										?>
										<tr class='break' id="paidTitle">
											<td colspan=2> 
												<h3><?php print __($guid, 'Payment Information') ?></h3>
											</td>
										</tr>
										<tr id="paymentDateRow">
											<td> 
												<b><?php print __($guid, 'Date Paid') ?></b><br/>
												<span style="font-size: 90%"><i><?php print __($guid, 'Date of payment, not entry to system.') ?></i></span>
											</td>
											<td class="right">
												<input readonly name="paymentDate" id="paymentDate" maxlength=10 value="<?php print dateConvertBack($guid, $row["paymentDate"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr id="paymentAmountRow">
											<td> 
												<b><?php print __($guid, 'Amount Paid') ?></b><br/>
												<span style="font-size: 90%"><i><?php print __($guid, 'Final amount paid.') ?>
												<?php
												if ($_SESSION[$guid]["currency"]!="") {
													print "<span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
												}
												?>
												</i></span>
											</td>
											<td class="right">
												<input readonly name="paymentAmount" id="paymentAmount" maxlength=10 value="<?php print number_format($row["paymentAmount"] , 2, ".", ",") ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr id="payeeRow">
											<td> 
												<b><?php print __($guid, 'Payee') ?></b><br/>
												<span style="font-size: 90%"><i><?php print __($guid, 'Staff who made, or arranged, the payment.') ?></i></span>
											</td>
											<td class="right">
												<?php
												try {
													$dataSelect=array("gibbonPersonID"=>$row["gibbonPersonIDPayment"]); 
													$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ;
													$resultSelect=$connection2->prepare($sqlSelect);
													$resultSelect->execute($dataSelect);
												}
												catch(PDOException $e) { }	
												if ($resultSelect->rowCount()==1) {
													$rowSelect=$resultSelect->fetch() ;
													?>
													<input readonly name="payee" id="payee" maxlength=10 value="<?php print formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) ?>" type="text" style="width: 300px">
													<?php
												}
												?>	
											</td>
										</tr>
										<tr id="paymentMethodRow">
											<td> 
												<b><?php print __($guid, 'Payment Method') ?></b><br/>
											</td>
											<td class="right">
												<input readonly name="paymentMethod" id="paymentMethod" maxlength=10 value="<?php print $row["paymentMethod"] ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr id="paymentIDRow">
											<td> 
												<b><?php print __($guid, 'Payment ID') ?></b><br/>
												<span style="font-size: 90%"><i><?php print __($guid, 'Transaction ID to identify this payment.') ?></i></span>
											</td>
											<td class="right">
												<input readonly name="paymentID" id="paymentID" maxlength=100 value="<?php print $row["paymentID"] ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<?php
									}
									?>
								</table>
							</form>
							<?php
						}
					}
				}
			}
		}
	}
}
?>