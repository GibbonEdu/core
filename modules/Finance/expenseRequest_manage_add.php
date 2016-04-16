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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . __($guid, 'My Expense Requests') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Expense Request') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="success1") {
			$addReturnMessage=__($guid, "Your request was completed successfully, but notifications could not be sent out.") ;	
			$class="success" ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	}
	
	//Check if school year specified
	$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
	$status2=$_GET["status2"] ;
	$gibbonFinanceBudgetID2=$_GET["gibbonFinanceBudgetID2"] ;
	if ($gibbonFinanceBudgetCycleID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
					//Ready to go!
					if ($status2!="" OR $gibbonFinanceBudgetID2!="") {
						print "<div class='linkTop'>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>" . __($guid, 'Back to Search Results') . "</a>" ;
						print "</div>" ;
					}
					?>
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_addProcess.php" ?>">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr>
								<td style='width: 275px'> 
									<b><?php print __($guid, 'Budget Cycle') ?> *</b><br/>
									<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
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
									<input readonly name="name" id="name" maxlength=20 value="<?php print $yearName ?>" type="text" class="standardWidth">
									<input name="gibbonFinanceBudgetCycleID" id="gibbonFinanceBudgetCycleID" maxlength=20 value="<?php print $gibbonFinanceBudgetCycleID ?>" type="hidden" class="standardWidth">
									<script type="text/javascript">
										var gibbonFinanceBudgetCycleID=new LiveValidation('gibbonFinanceBudgetCycleID');
										gibbonFinanceBudgetCycleID.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php print __($guid, 'Budget') ?> *</b><br/>
								</td>
								<td class="right">
									<?php
									print "<select name='gibbonFinanceBudgetID' id='gibbonFinanceBudgetID' style='width:302px'>" ;
										$selected="" ;
										if ($gibbonFinanceBudgetID=="") {
											$selected="selected" ;
										}
										print "<option $selected value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
										foreach ($budgets AS $budget) {
											$selected="" ;
											if ($gibbonFinanceBudgetID==$budget[0]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $budget[0] . "'>" . $budget[1] . "</option>" ;
										}
									print "</select>" ;
									?>
									<script type="text/javascript">
										var gibbonFinanceBudgetID=new LiveValidation('gibbonFinanceBudgetID');
										gibbonFinanceBudgetID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Title') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="title" id="title" maxlength=60 value="" type="text" class="standardWidth">
									<script type="text/javascript">
										var title=new LiveValidation('title');
										title.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Status') ?> *</b><br/>
									<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
								</td>
								<td class="right">
									<input readonly name="status" id="status" value="Requested" type="text" class="standardWidth">
									<script type="text/javascript">
										var status=new LiveValidation('status');
										status.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="teachersNotesRow">
								<td colspan=2> 
									<b><?php print __($guid, 'Description') ?></b>
									<?php $expenseRequestTemplate=getSettingByScope($connection2, "Finance", "expenseRequestTemplate" ) ?>
									<?php print getEditor($guid,  TRUE, "body", $expenseRequestTemplate, 25, true, false, false ) ?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Total Cost') ?> *</b><br/>
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
									<input name="cost" id="cost" maxlength=15 value="" type="text" class="standardWidth">
									<script type="text/javascript">
										var cost=new LiveValidation('cost');
										cost.add(Validate.Presence);
										cost.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
									</script>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php print __($guid, 'Count Against Budget') ?> *</b><br/>
									<span class="emphasis small">
										<?php print __($guid, "For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.") ; ?>
									</span>
								</td>
								<td class="right">
									<select name="countAgainstBudget" id="countAgainstBudget" class="standardWidth">
										<?php
										print "<option selected value='Y'>" . ynExpander($guid, 'Y') . "</option>" ;
										print "<option value='N'>" . ynExpander($guid, 'N') . "</option>" ;
										?>			
									</select>
								</td>
							</tr>
							
							<tr>
								<td style='width: 275px'> 
									<b><?php print __($guid, 'Purchase By') ?> *</b><br/>
								</td>
								<td class="right">
									<?php
									print "<select name='purchaseBy' id='purchaseBy' style='width:302px'>" ;
										print "<option value='School'>School</option>" ;
										print "<option value='Self'>Self</option>" ;
									print "</select>" ;
									?>
								</td>
							</tr>
							
							<tr>
								<td colspan=2> 
									<b><?php print __($guid, 'Purchase Details') ?></b><br/>
									<textarea name="purchaseDetails" id="purchaseDetails" rows=8 style="width: 100%"></textarea>
								</td>
							</tr>
				
							<tr>
								<td>
									<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
								</td>
								<td class="right">
									<input name="gibbonFinanceInvoiceID" id="gibbonFinanceInvoiceID" value="<?php print $gibbonFinanceInvoiceID ?>" type="hidden">
									<input name="status2" id="status2" value="<?php print $status2 ?>" type="hidden">
									<input name="gibbonFinanceBudgetID2" id="gibbonFinanceBudgetID2" value="<?php print $gibbonFinanceBudgetID2 ?>" type="hidden">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
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
?>