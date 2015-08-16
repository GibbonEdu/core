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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_add.php", "Manage Expenses_all")==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . _('Manage Expenses') . "</a> > </div><div class='trailEnd'>" . _('Edit Expense') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["editReturn"])) { $editReturn=$_GET["editReturn"] ; } else { $editReturn="" ; }
		$editReturnMessage="" ;
		$class="error" ;
		if (!($editReturn=="")) {
			if ($editReturn=="fail0") {
				$editReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($editReturn=="fail2") {
				$editReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($editReturn=="fail3") {
				$editReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			if ($editReturn=="success0") {
				$editReturnMessage=_("Your request was completed successfully.") ;		
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $editReturnMessage;
			print "</div>" ;
		} 
	
		//Check if params are specified
		$gibbonFinanceExpenseID=$_GET["gibbonFinanceExpenseID"] ;
		$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
		$status2=$_GET["status2"] ;
		$gibbonFinanceBudgetID2=$_GET["gibbonFinanceBudgetID2"] ;
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
							$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
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
					
							if ($status2!="" OR $gibbonFinanceBudgetID2!="") {
								print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>" . _('Back to Search Results') . "</a>" ;
								print "</div>" ;
							}
							?>
							<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_editProcess.php" ?>">
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
											<b><?php print _('Status') ?> *</b><br/><?php print $row["status"] ?>
										</td>
										<td class="right">
											<?php
											if ($row["status"]=="Requested" OR $row["status"]=="Approved" OR $row["status"]=="Ordered") {
												print "<select name='status' id='status' style='width:302px'>" ;
													print "<option  value='Please select...'>" . _('Please select...') . "</option>" ;
													if ($row["status"]=="Requested") {
														$selected="" ;
														if ($row["status"]=="Requested") {
															$selected="selected" ;
														}
														print "<option $selected value='Requested'>" . _('Requested') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Approved") {
															$selected="selected" ;
														}
														print "<option $selected value='Approved'>" . _('Approved') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Rejected") {
															$selected="selected" ;
														}
														print "<option $selected value='Rejected'>" . _('Rejected') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Ordered") {
															$selected="selected" ;
														}
														print "<option $selected value='Ordered'>" . _('Ordered') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Paid") {
															$selected="selected" ;
														}
														print "<option $selected value='Paid'>" . _('Paid') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Cancelled") {
															$selected="selected" ;
														}
														print "<option $selected value='Cancelled'>" . _('Cancelled') . "</option>" ;
													}
													else if ($row["status"]=="Approved") {
														$selected="" ;
														if ($row["status"]=="Approved") {
															$selected="selected" ;
														}
														print "<option $selected value='Approved'>" . _('Approved') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Ordered") {
															$selected="selected" ;
														}
														print "<option $selected value='Ordered'>" . _('Ordered') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Paid") {
															$selected="selected" ;
														}
														print "<option $selected value='Paid'>" . _('Paid') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Cancelled") {
															$selected="selected" ;
														}
														print "<option $selected value='Cancelled'>" . _('Cancelled') . "</option>" ;
													}
													else if ($row["status"]=="Ordered") {
														$selected="" ;
														if ($row["status"]=="Ordered") {
															$selected="selected" ;
														}
														print "<option $selected value='Ordered'>" . _('Ordered') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Paid") {
															$selected="selected" ;
														}
														print "<option $selected value='Paid'>" . _('Paid') . "</option>" ;
														$selected="" ;
														if ($row["status"]=="Cancelled") {
															$selected="selected" ;
														}
														print "<option $selected value='Cancelled'>" . _('Cancelled') . "</option>" ;
													}
												print "</select>" ;
											}
											else {
												?>
												<input readonly name="status" id="status" maxlength=60 value="<?php print $row["status"] ; ?>" type="text" style="width: 300px">
												<?php
											}
											?>
											<script type="text/javascript">
												var status=new LiveValidation('status');
												status.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
											</script>
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
							
									<?php
									if ($row["status"]=="Requested" OR $row["status"]=="Approved" OR $row["status"]=="Ordered" OR $row["status"]=="Paid") {
										?>
										<tr class='break'>
											<td colspan=2> 
												<h3><?php print _('Budget Tracking') ?></h3>
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
												<input readonly name="name" id="name" maxlength=60 value="<?php print number_format($row["cost"], 2, ".", ",") ; ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php print _('Count Against Budget') ?> *</b><br/>
												<span style="font-size: 90%"><i>
													<?php print _("For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.") ; ?>
												</i></span>
											</td>
											<td class="right">
												<select name="countAgainstBudget" id="countAgainstBudget" style="width: 302px">
													<?php
													$selected="" ;
													if ($row["countAgainstBudget"]=="Y") {
														$selected="selected" ;
													}
													print "<option $selected value='Y'>" . ynExpander('Y') . "</option>" ;
													$selected="" ;
													if ($row["countAgainstBudget"]=="N") {
														$selected="selected" ;
													}
													print "<option $selected value='N'>" . ynExpander('N') . "</option>" ;
													?>			
												</select>
											</td>
										</tr>

										<?php
										if ($row["countAgainstBudget"]=="Y") {
											?>
											<tr>
												<td> 
													<b><?php print _('Budget For Cycle') ?> *</b><br/>
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
													<?php
													$budgetAllocation=NULL ;
													$budgetAllocationFail=FALSE ;
													try {
														$dataCheck=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceBudgetID"=>$row["gibbonFinanceBudgetID"]); 
														$sqlCheck="SELECT * FROM gibbonFinanceBudgetCycleAllocation WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
														$resultCheck=$connection2->prepare($sqlCheck);
														$resultCheck->execute($dataCheck);
													}
													catch(PDOException $e) {
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														$budgetAllocationFail=TRUE ;
													}
													if ($resultCheck->rowCount()!=1) {
														print "<input readonly name=\"name\" id=\"name\" maxlength=60 value=\"" . _('NA') . "\" type=\"text\" style=\"width: 300px\">" ;
														$budgetAllocationFail=TRUE ;
													}
													else {
														$rowCheck=$resultCheck->fetch() ;
														$budgetAllocation=$rowCheck["value"] ;
														?>
														<input readonly name="name" id="name" maxlength=60 value="<?php print number_format($budgetAllocation, 2, ".", ",") ; ?>" type="text" style="width: 300px">
														<?php
													}
													?>
												</td>
											</tr>
											<tr>
												<td> 
													<b><?php print _('Amount already approved or spent') ?> *</b><br/>
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
													<?php
													$budgetAllocated=0 ;
													$budgetAllocatedFail=FALSE ;
													try {
														$dataCheck=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceBudgetID"=>$row["gibbonFinanceBudgetID"]); 
														$sqlCheck="(SELECT cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Approved', 'Order'))
														UNION
														(SELECT paymentAmount AS cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Paid'))
														" ;
														$resultCheck=$connection2->prepare($sqlCheck);
														$resultCheck->execute($dataCheck);
													}
													catch(PDOException $e) {
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														$budgetAllocatedFail=TRUE ;
													}
													if ($budgetAllocatedFail==FALSE) {
														while ($rowCheck=$resultCheck->fetch()) {
															$budgetAllocated=$budgetAllocated+$rowCheck["cost"] ;
														}
														?>
														<input readonly name="name" id="name" maxlength=60 value="<?php print number_format($budgetAllocated, 2, ".", ",") ; ?>" type="text" style="width: 300px">
														<?php
													}
											
													?>
												</td>
											</tr>
											<?php
											if ($budgetAllocationFail==FALSE AND $budgetAllocatedFail==FALSE) {
												?>
												<tr>
												<td> 
													<b><?php print _('Budget Remaining For Cycle') ?> *</b><br/>
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
													<?php
													$color="red" ;
													if (($budgetAllocation-$budgetAllocated)-$row["cost"]>0) {
														$color="green" ;
													}
													?>
													<input readonly name="name" id="name" maxlength=60 value="<?php print number_format(($budgetAllocation-$budgetAllocated), 2, ".", ",") ; ?>" type="text" style="width: 300px; font-weight: bold; color: <?php print $color ?>">
												</td>
											</tr>
											<?php
											}	
										}
									}
									?>
									
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
									
									<?php 
									if ($row["status"]=="Approved" OR $row["status"]=="Ordered") {
										?>
										<script type="text/javascript">
											$(document).ready(function(){
												$("#paidTitle").css("display","none");
												$("#paymentDateRow").css("display","none");
												$("#paymentAmountRow").css("display","none");
												$("#payeeRow").css("display","none");
												$("#paymentMethodRow").css("display","none");
												$("#paymentIDRow").css("display","none");
												$("#reimbursementRow").css("display","none");
												$("#reimbursementCommentRow").css("display","none");
												paymentDate.disable() ;
												paymentAmount.disable() ;
												gibbonPersonIDPayment.disable() ;
												paymentMethod.disable() ;
												$("#status").change(function(){
													if ($('#status option:selected').val()=="Paid" ) {
														$("#paidTitle").slideDown("fast", $("#paidTitle").css("display","table-row")); 
														$("#paymentDateRow").slideDown("fast", $("#paymentDateRow").css("display","table-row")); 
														$("#paymentAmountRow").slideDown("fast", $("#paymentAmountRow").css("display","table-row")); 
														$("#payeeRow").slideDown("fast", $("#payeeRow").css("display","table-row")); 
														$("#paymentMethodRow").slideDown("fast", $("#paymentMethodRow").css("display","table-row")); 
														$("#paymentIDRow").slideDown("fast", $("#paymentIDRow").css("display","table-row")); 
														$("#reimbursementRow").slideDown("fast", $("#reimbursementRow").css("display","table-row")); 
														$("#reimbursementCommentRow").slideDown("fast", $("#reimbursementCommentRow").css("display","table-row")); 
														paymentDate.enable() ;
														paymentAmount.enable() ;
														gibbonPersonIDPayment.enable() ;
														paymentMethod.enable() ;
													} else {
														$("#paidTitle").css("display","none");
														$("#paymentDateRow").css("display","none");
														$("#paymentAmountRow").css("display","none");
														$("#payeeRow").css("display","none");
														$("#paymentMethodRow").css("display","none");
														$("#paymentIDRow").css("display","none");
														$("#reimbursementRow").css("display","none");
														$("#reimbursementCommentRow").css("display","none");
														paymentDate.disable() ;
														paymentAmount.disable() ;
														gibbonPersonIDPayment.disable() ;
														paymentMethod.disable() ;
													}
												 });
											});
										</script>
										<tr class='break' id="paidTitle">
											<td colspan=2> 
												<h3><?php print _('Payment Information') ?></h3>
											</td>
										</tr>
										<tr id="paymentDateRow">
											<td> 
												<b><?php print _('Date Paid') ?> *</b><br/>
												<span style="font-size: 90%"><i><?php print _('Date of payment, not entry to system.') ?></i></span>
											</td>
											<td class="right">
												<input name="paymentDate" id="paymentDate" maxlength=10 value="" type="text" style="width: 300px">
												<script type="text/javascript">
													var paymentDate=new LiveValidation('paymentDate');
													paymentDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
													paymentDate.add(Validate.Presence);
												 </script>
												 <script type="text/javascript">
													$(function() {
														$( "#paymentDate" ).datepicker();
													});
												</script>
											</td>
										</tr>
										<tr id="paymentAmountRow">
											<td> 
												<b><?php print _('Amount Paid') ?> *</b><br/>
												<span style="font-size: 90%"><i><?php print _('Final amount paid.') ?>
												<?php
												if ($_SESSION[$guid]["currency"]!="") {
													print "<span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
												}
												?>
												</i></span>
											</td>
											<td class="right">
												<input name="paymentAmount" id="paymentAmount" maxlength=15 value="" type="text" style="width: 300px">
												<script type="text/javascript">
													var paymentAmount=new LiveValidation('paymentAmount');
													paymentAmount.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
													paymentAmount.add(Validate.Presence);
												 </script>
											</td>
										</tr>
										<tr id="payeeRow">
											<td> 
												<b><?php print _('Payee') ?> *</b><br/>
												<span style="font-size: 90%"><i><?php print _('Staff who made, or arranged, the payment.') ?></i></span>
											</td>
											<td class="right">
												<select name="gibbonPersonIDPayment" id="gibbonPersonIDPayment" style="width: 302px">
													<?php
													print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
													try {
														$dataSelect=array(); 
														$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }	
													while ($rowSelect=$resultSelect->fetch()) {
														print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), ($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]),"Staff", true, true) . "</option>" ;
													}
													?>
												</select>
												<script type="text/javascript">
													var gibbonPersonIDPayment=new LiveValidation('gibbonPersonIDPayment');
													gibbonPersonIDPayment.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr id="paymentMethodRow">
											<td> 
												<b><?php print _('Payment Method') ?> *</b><br/>
											</td>
											<td class="right">
												<?
												print "<select name='paymentMethod' id='paymentMethod' style='width:302px'>" ;
													print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
													print "<option value='Bank Transfer'>Bank Transfer</option>" ;
													print "<option value='Cash'>Cash</option>" ;
													print "<option value='Cheque'>Cheque</option>" ;
													print "<option value='Credit Card'>Credit Card</option>" ;
													print "<option value='Other'>Other</option>" ;
												print "</select>" ;
												?>
												<script type="text/javascript">
													var paymentMethod=new LiveValidation('paymentMethod');
													paymentMethod.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr id="paymentIDRow">
											<td> 
												<b><?php print _('Payment ID') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Transaction ID to identify this payment.') ?></i></span>
											</td>
											<td class="right">
												<input name="paymentID" id="paymentID" maxlength=100 value="" type="text" style="width: 300px">
											</td>
										</tr>
										<?php
									}
									else if ($row["status"]=="Paid") {
										?>
										<tr class='break' id="paidTitle">
											<td colspan=2> 
												<h3><?php print _('Payment Information') ?></h3>
											</td>
										</tr>
										<tr id="paymentDateRow">
											<td> 
												<b><?php print _('Date Paid') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Date of payment, not entry to system.') ?></i></span>
											</td>
											<td class="right">
												<input readonly name="paymentDate" id="paymentDate" maxlength=10 value="<?php print dateConvertBack($guid, $row["paymentDate"]) ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr id="paymentAmountRow">
											<td> 
												<b><?php print _('Amount Paid') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Final amount paid.') ?>
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
												<b><?php print _('Payee') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Staff who made, or arranged, the payment.') ?></i></span>
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
												<b><?php print _('Payment Method') ?></b><br/>
											</td>
											<td class="right">
												<input readonly name="paymentMethod" id="paymentMethod" maxlength=10 value="<?php print $row["paymentMethod"] ?>" type="text" style="width: 300px">
											</td>
										</tr>
										<tr id="paymentIDRow">
											<td> 
												<b><?php print _('Payment ID') ?></b><br/>
												<span style="font-size: 90%"><i><?php print _('Transaction ID to identify this payment.') ?></i></span>
											</td>
											<td class="right">
												<?php
												if ($row["paymentReimbursementReceipt"]!="") {
													if(is_file("./" . $row["paymentReimbursementReceipt"])) {
														print "<a target='_blank' href=\"./" . $row["paymentReimbursementReceipt"] . "\">" . _("Payment Receipt") . "</a><br/>" ;
													}
												}
												
												if ($row["paymentID"]=="" AND $row["status"]=="Paid" AND $row["purchaseBy"]=="Self" AND $row["paymentReimbursementStatus"]=="Requested") {
													?>
													<input name="paymentID" id="paymentID" maxlength=100 value="" type="text" style="width: 300px">
													<?php
												}
												else {
													?>
													<input readonly name="paymentID" id="paymentID" maxlength=100 value="<?php print $row["paymentID"] ?>" type="text" style="width: 300px">
													<?php
												}
												?>
											</td>
										</tr>
										<?php
										if ($row["status"]=="Paid" AND $row["purchaseBy"]=="Self" AND $row["paymentReimbursementStatus"]!="") {
											?>
											<tr id="reimbursementRow">
												<td> 
													<b><?php print _('Reimbursement Status') ?></b><br/>
												</td>
												<td class="right">
													<?php
													if ($row["paymentReimbursementStatus"]=="Requested") {
														print "<select name='paymentReimbursementStatus' id='paymentReimbursementStatus' style='width:302px'>" ;
															$selected="" ;
															if ($row["status"]=="Requested") {
																$selected="selected" ;
															}
															print "<option $selected value='Requested'>" . _('Requested') . "</option>" ;
															$selected="" ;
															if ($row["status"]=="Complete") {
																$selected="selected" ;
															}
															print "<option $selected value='Complete'>" . _('Complete') . "</option>" ;
														print "</select>" ;
													}
													else {
														?>
														<input readonly name="paymentReimbursementStatus" id="paymentReimbursementStatus" maxlength=60 value="<?php print $row["paymentReimbursementStatus"] ; ?>" type="text" style="width: 300px">
														<?php
													}
													?>
												</td>
											</tr>
											<?php
											if ($row["paymentReimbursementStatus"]=="Requested") {
												?>
												<tr id="reimbursementCommentRow">
													<td colspan=2> 
														<b><?php print _('Reimbursement Comment') ?></b><br/>
														<textarea name="reimbursementComment" id="reimbursementComment" rows=4 style="width: 100%"></textarea>
													</td>
												</tr>
												<?php
											}
										}
									}
									?>
									
									<tr>
										<td>
											<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
										</td>
										<td class="right">
											<input name="gibbonFinanceExpenseID" id="gibbonFinanceExpenseID" value="<?php print $gibbonFinanceExpenseID ?>" type="hidden">
											<input name="gibbonFinanceBudgetID" id="gibbonFinanceBudgetID" value="<?php print $gibbonFinanceBudgetID ?>" type="hidden">
											<input name="status2" id="status2" value="<?php print $status2 ?>" type="hidden">
											<input name="gibbonFinanceBudgetID2" id="gibbonFinanceBudgetID2" value="<?php print $gibbonFinanceBudgetID2 ?>" type="hidden">
											<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
											<input type="submit" value="<?php print _("Submit") ; ?>">
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
}
?>