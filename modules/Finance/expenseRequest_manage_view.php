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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . _('Manage My Expense Requests') . "</a> > </div><div class='trailEnd'>" . _('View Expense Request') . "</div>" ;
	print "</div>" ;
	
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
		//Check if have Full or Write in any budgets
		$budgets=getBudgetsByPerson($connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
		$budgetsAccess=FALSE ;
		foreach ($budgets AS $budget) {
			if ($budget[2]=="Full" OR $budget[2]=="Write") {
				$budgetsAccess=TRUE ;
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
						$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.gibbonPersonIDCreator=:gibbonPersonIDCreator" ; 
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
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=$status&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'>" . _('Back to Search Results') . "</a>" ;
							print "</div>" ;
						}
						?>
						<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_addProcess.php" ?>">
							<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print _('Basic Information') ?></h3>
									</td>
								</tr>
								<tr>
									<td style='width: 275px'> 
										<b><?php print _('Budget Cycle') ?> *</b><br/>
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
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
										<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=60 value="<?php print $row["status"] ; ?>" type="text" style="width: 300px">
									</td>
								</tr>
								<tr id="teachersNotesRow">
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
							
								<tr class='break'>
									<td colspan=2> 
										<h3><?php print _('Log') ?></h3>
									</td>
								</tr>
								<tr>
									<td colspan=2> 
										<div class='warning'><?php print _('Coming soon...') ?></div>
									</td>
								</tr>
							</table>
						</form>
						<?php
					}
				}
			}
		}
	}
}
?>