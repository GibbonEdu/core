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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_add.php", "Manage Expenses_all")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$allowExpenseAdd=getSettingByScope($connection2, "Finance", "allowExpenseAdd") ;
	if ($allowExpenseAdd!="Y") {
		print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=" . $_GET["gibbonFinanceBudgetCycleID"] . "'>" . _('Manage Expenses') . "</a> > </div><div class='trailEnd'>" . _('Add Expense') . "</div>" ;
		print "</div>" ;
	
		print "<div class='warning'>" ;
			print _("Expenses added here do not require authorisation: this is for pre-authorised, or recurring expenses only.") ;
		print "</div>" ;
	
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="success1") {
				$addReturnMessage=_("Your request was completed successfully, but notifications could not be sent out.") ;	
				$class="success" ;
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully.") ;	
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
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			if ($status2!="" OR $gibbonFinanceBudgetID2!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_addProcess.php" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<tr class='break'>
							<td colspan=2> 
								<h3><?php print _('Basic Information') ?></h3>
							</td>
						</tr>
					
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
							<?php
							try {
								$data=array();
								$sql="SELECT * FROM gibbonFinanceBudget WHERE active='Y' ORDER BY name" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { }
						
							print "<select name='gibbonFinanceBudgetID' id='gibbonFinanceBudgetID' style='width:302px'>" ;
								$selected="" ;
								if ($gibbonFinanceBudgetID=="") {
									$selected="selected" ;
								}
								print "<option $selected value='Please select...'>" . _('Please select...') . "</option>" ;
								while ($row=$result->fetch()) {
									$selected="" ;
									if ($gibbonFinanceBudgetID==$row["gibbonFinanceBudgetID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $row["gibbonFinanceBudgetID"] . "'>" . $row["name"] . "</option>" ;
								}
							print "</select>" ;
							?>
							<script type="text/javascript">
								var gibbonFinanceBudgetID=new LiveValidation('gibbonFinanceBudgetID');
								gibbonFinanceBudgetID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Title') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="title" id="title" maxlength=60 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var title=new LiveValidation('title');
								title.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Status') ?> *</b><br/>
						</td>
						<td class="right">
							<?php
							print "<select name='status' id='status3' style='width:302px'>" ;
								print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
								print "<option value='Approved'>" . _('Approved') . "</option>" ;
								print "<option value='Ordered'>" . _('Ordered') . "</option>" ;
								print "<option value='Paid'>" . _('Paid') . "</option>" ;
							print "</select>" ;
							?>
							<script type="text/javascript">
								var status3=new LiveValidation('status3');
								status3.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b><?php print _('Description') ?></b>
							<?php $expenseRequestTemplate=getSettingByScope($connection2, "Finance", "expenseRequestTemplate" ) ?>
							<?php print getEditor($guid,  TRUE, "body", $expenseRequestTemplate, 25, true, false, false ) ?>
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
							<input name="cost" id="cost" maxlength=15 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var cost=new LiveValidation('cost');
								cost.add(Validate.Presence);
								cost.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
							</script>
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
								print "<option selected value='Y'>" . ynExpander('Y') . "</option>" ;
								print "<option value='N'>" . ynExpander('N') . "</option>" ;
								?>			
							</select>
						</td>
					</tr>
				
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Purchase By') ?> *</b><br/>
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
							<b><?php print _('Purchase Details') ?></b><br/>
							<textarea name="purchaseDetails" id="purchaseDetails" rows=8 style="width: 100%"></textarea>
						</td>
					</tr>
					<script type="text/javascript">
						$(document).ready(function(){
							$("#paidTitle").css("display","none");
							$("#paymentDateRow").css("display","none");
							$("#paymentAmountRow").css("display","none");
							$("#payeeRow").css("display","none");
							$("#paymentMethodRow").css("display","none");
							$("#paymentIDRow").css("display","none");
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
				
	
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
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
?>