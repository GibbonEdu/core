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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_approve.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . _('Manage Expenses') . "</a> > </div><div class='trailEnd'>" . _('Approve/Reject Expense') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["approveReturn"])) { $approveReturn=$_GET["approveReturn"] ; } else { $approveReturn="" ; }
		$approveReturnMessage="" ;
		$class="error" ;
		if (!($approveReturn=="")) {
			if ($approveReturn=="fail0") {
				$approveReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($approveReturn=="fail2") {
				$approveReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($approveReturn=="fail3") {
				$approveReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			print "<div class='$class'>" ;
				print $approveReturnMessage;
			print "</div>" ;
		} 
	
		//Check if params are specified
		$gibbonFinanceExpenseID=$_GET["gibbonFinanceExpenseID"] ;
		$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
		$status=$_GET["status"] ;
		$gibbonFinanceBudgetID=$_GET["gibbonFinanceBudgetID"] ;
		if ($gibbonFinanceExpenseID=="" OR $gibbonFinanceBudgetCycleID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
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
								print _("The specified record cannot be found.") ;
							print "</div>" ;
						}
						else {
							//Let's go!
							$row=$result->fetch() ;
					
							if ($status!="" OR $gibbonFinanceBudgetID!="") {
								print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=$status&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'>" . _('Back to Search Results') . "</a>" ;
								print "</div>" ;
							}
							?>
							<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_approveProcess.php" ?>">
								<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Basic Information') ?></h3>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'> 
											<b><?php print _('Budget Cycle') ?> *</b><br/>
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
											<b><?php print _('Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=20 value="<?php print $row["budget"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print _('Title') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php print $row["title"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print _('Status') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php print $row["status"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php print _('Description') ?></b>
											<?php 
												print "<p>" ;
													print $row["body"] ;
												print "</p>"	
											?>
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php print _('Total Cost') ?> *</b><br/>
											<span style="font-size: 90%">
												<i>
												<?php
												if ($_SESSION[$guid]["currency"]!="") {
													print sprintf(_('Numeric value of the fee in %1$s.'), $_SESSION[$guid]["currency"]) ;
												}
												else {
													print _("Numeric value of the fee.") ;
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
											<b><?php print _('Purchase By') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php print $row["purchaseBy"] ; ?>" type="text" style="width: 300px">
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php print _('Purchase Details') ?></b>
											<?php 
												print "<p>" ;
													print $row["purchaseDetails"] ;
												print "</p>"	
											?>
										</td>
									</tr>
							
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Log') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<?php
											print getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2) ;
											?>
										</td>
									</tr>
									
									<tr class='break'>
										<td colspan=2> 
											<h3><?php print _('Action') ?></h3>
										</td>
									</tr>
									<?php
									$approvalRequired=approvalRequired($guid, $_SESSION[$guid]["gibbonPersonID"], $row["gibbonFinanceExpenseID"], $gibbonFinanceBudgetCycleID, $connection2) ;
									if ($approvalRequired!=TRUE) { //Approval not required
										?>
										<tr>
											<td colspan=2> 
												<div class='error'><?php print _('Your approval is not currently required: it is possible somone beat you to it, or you have already approved it.') ?></div>
											</td>
										</tr>
										<?php
									}
									else {
										?>
										<tr>
											<td style='width: 275px'> 
												<b><?php print _('Approval') ?> *</b><br/>
											</td>
											<td class="right">
												<?php
												print "<select name='approval' id='approval' style='width:302px'>" ;
													print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
													print "<option value='Approval - Partial'>" . _('Approve') . "</option>" ;
													print "<option value='Rejection'>" . _('Reject') . "</option>" ;
												print "</select>" ;
												?>
												<script type="text/javascript">
													var approval=new LiveValidation('approval');
													approval.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr>
											<td colspan=2> 
												<b><?php print _('Comment') ?></b><br/>
												<textarea name="comment" id="comment" rows=8 style="width: 100%"></textarea>
											</td>
										</tr>
										<tr>
											<td>
												<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
											</td>
											<td class="right">
												<input name="gibbonFinanceExpenseID" id="gibbonFinanceExpenseID" value="<?php print $gibbonFinanceExpenseID ?>" type="hidden">
												<input name="gibbonFinanceBudgetCycleID" id="gibbonFinanceBudgetCycleID" value="<?php print $gibbonFinanceBudgetCycleID ?>" type="hidden">
												<input name="gibbonFinanceBudgetID" id="gibbonFinanceBudgetID" value="<?php print $gibbonFinanceBudgetID ?>" type="hidden">
												<input name="status" id="status" value="<?php print $status ?>" type="hidden">
												<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
												<input type="submit" value="<?php print _("Submit") ; ?>">
											</td>
										</tr>
										<?
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