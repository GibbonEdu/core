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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage_reimburse.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . _('My Expense Requests') . "</a> > </div><div class='trailEnd'>" . _('Request Reimbursement') . "</div>" ;
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
		else if ($editReturn=="fail5") {
			$editReturnMessage=_("Your request failed due to an attachment error.") ;	
		}
		print "<div class='$class'>" ;
			print $editReturnMessage;
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
							WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.status='Approved'" ;
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
					<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenseRequest_manage_reimburseProcess.php" ?>" enctype="multipart/form-data">
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
											if ($row["status"]=="Approved") {
												print "<option value='Approved'>" . _('Approved') . "</option>" ;
												print "<option selected value='Paid'>" . _('Paid') . "</option>" ;
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
							
							<script type="text/javascript">
								$(document).ready(function(){
									$("#status").change(function(){
										if ($('#status option:selected').val()=="Paid" ) {
											$("#paidTitle").slideDown("fast", $("#paidTitle").css("display","table-row")); 
											$("#paymentDateRow").slideDown("fast", $("#paymentDateRow").css("display","table-row")); 
											$("#paymentAmountRow").slideDown("fast", $("#paymentAmountRow").css("display","table-row")); 
											$("#payeeRow").slideDown("fast", $("#payeeRow").css("display","table-row")); 
											$("#paymentMethodRow").slideDown("fast", $("#paymentMethodRow").css("display","table-row")); 
											$("#paymentIDRow").slideDown("fast", $("#paymentIDRow").css("display","table-row")); 
											paymentDate.enable() ;
											paymentAmount.enable() ;
											paymentMethod.enable() ;
											file.enable() ;
										} else {
											$("#paidTitle").css("display","none");
											$("#paymentDateRow").css("display","none");
											$("#paymentAmountRow").css("display","none");
											$("#payeeRow").css("display","none");
											$("#paymentMethodRow").css("display","none");
											$("#paymentIDRow").css("display","none");
											paymentDate.disable() ;
											paymentAmount.disable() ;
											paymentMethod.disable() ;
											file.disable() ;
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
									<input readonly name="name" id="name" value="<?php print formatName("", ($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]),"Staff", true, true) ?>" type="text" style="width: 300px">
									<input name="gibbonPersonIDPayment" id="gibbonPersonIDPayment" value="<?php print $_SESSION[$guid]["gibbonPersonID"] ?>" type="hidden">
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
									<b><?php print _('Payment Receipt') ?></b><br/>
									<span style="font-size: 90%"><i><?php print _('Digital copy of the receipt for this payment.') ?></i></span>
								</td>
								<td class="right">
									<input type="file" name="file" id="file"><br/><br/>
										<?php
										print getMaxUpload() ;
										$ext="'.png','.jpeg','.jpg','.gif'" ;
										?>
										<script type="text/javascript">
											var file=new LiveValidation('file');
											file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
											file.add(Validate.Presence);
										</script>
									</td>
								</td>
							</tr>
							
							<tr>
								<td>
									<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
								</td>
								<td class="right">
									<input name="gibbonFinanceExpenseID" id="gibbonFinanceExpenseID" value="<?php print $gibbonFinanceExpenseID ?>" type="hidden">
									<input name="gibbonFinanceBudgetID" id="gibbonFinanceBudgetID" value="<?php print $row["gibbonFinanceBudgetID"] ?>" type="hidden">
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
?>