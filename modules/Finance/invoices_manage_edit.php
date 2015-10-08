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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonFinanceInvoiceID=$_GET["gibbonFinanceInvoiceID"] ;
	$status=$_GET["status"] ;
	$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
	$monthOfIssue=$_GET["monthOfIssue"] ;
	$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;
	
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" . _('Manage Invoices') . "</a> > </div><div class='trailEnd'>" . _('Edit Invoice') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		if ($updateReturn=="success1") {
			$updateReturnMessage=_("Your request was completed successfully, but one or more requested emails could not be sent.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	if ($gibbonFinanceInvoiceID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
			$sql="SELECT gibbonFinanceInvoice.*, companyName, companyContact, companyEmail, companyCCFamily FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
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
			
			if ($status!="" OR $gibbonFinanceInvoiceeID!="" OR $monthOfIssue!="" OR $gibbonFinanceBillingScheduleID!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" . _('Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('School Year') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<?php
							$yearName="" ;
							try {
								$dataYear=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
								$sqlYear="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
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
							<input readonly name="yearName" id="yearName" value="<?php print $yearName ?>" type="text" style="width: 300px">
					</tr>
					<tr>
						<td> 
							<b><?php print _('Invoicee') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<?php
							$personName="" ;
							try {
								$dataInvoicee=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
								$sqlInvoicee="SELECT surname, preferredName FROM gibbonPerson JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID" ;
								$resultInvoicee=$connection2->prepare($sqlInvoicee);
								$resultInvoicee->execute($dataInvoicee);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							if ($resultInvoicee->rowCount()==1) {
								$rowInvoicee=$resultInvoicee->fetch() ;
								$personName=formatName("", htmlPrep($rowInvoicee["preferredName"]), htmlPrep($rowInvoicee["surname"]), "Student", true) ;
							}
							?>
							<input readonly name="personName" id="personName" value="<?php print $personName ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?php //BILLING TYPE CHOOSER ?>
					<tr>
						<td> 
							<b><?php print _('Scheduling') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="billingScheduleType" id="billingScheduleType" value="<?php print $row["billingScheduleType"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?php
					if ($row["billingScheduleType"]=="Scheduled") {
						?>
						<tr>
							<td> 
								<b><?php print _('Billing Schedule') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
							</td>
							<td class="right">
								<?php
								$schedule="" ;
								try {
									$dataSchedule=array("gibbonFinanceBillingScheduleID"=>$row["gibbonFinanceBillingScheduleID"]); 
									$sqlSchedule="SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID" ;
									$resultSchedule=$connection2->prepare($sqlSchedule);
									$resultSchedule->execute($dataSchedule);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultSchedule->rowCount()==1) {
									$rowSchedule=$resultSchedule->fetch() ;
									$schedule=$rowSchedule["name"] ;
								}
								?>
								<input readonly name="schedule" id="schedule" value="<?php print $schedule ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					else {
						if ($row["status"]=="Pending" OR $row["status"]=="Issued") {
							?>
							<tr>
								<td> 
									<b><?php print _('Invoice Due Date') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="invoiceDueDate" id="invoiceDueDate" value="<?php print dateConvertBack($guid, $row["invoiceDueDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var invoiceDueDate=new LiveValidation('invoiceDueDate');
										invoiceDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
										invoiceDueDate.add(Validate.Presence);
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#invoiceDueDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<?php
						}
						else {
							?>
							<tr>
								<td> 
									<b><?php print _('Invoice Due Date') ?> *</b><br/>
									<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
								</td>
								<td class="right">
									<input readonly name="invoiceDueDate" id="invoiceDueDate" value="<?php print dateConvertBack($guid, $row["invoiceDueDate"]) ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<?php
						}
					}
					?>
					<tr>
						<td> 
							<b><?php print _('Status') ?> *</b><br/>
							<?php
							if ($row["status"]=="Pending") {
								print "<span style=\"font-size: 90%\"><i>" . _('This value cannot be changed. Use the Issue function to change the status from "Pending" to "Issued".') . "</i></span>" ;
							}
							else {
								print "<span style=\"font-size: 90%\"><i>" . _('Available options are limited according to current status.') . "</i></span>" ;
							}
							?>
						</td>
						<td class="right">
							<?php
							if ($row["status"]=="Pending" OR $row["status"]=="Cancelled" OR $row["status"]=="Refunded") {
								print "<input readonly name=\"status\" id=\"status\" value=\"" . $row["status"] . "\" type=\"text\" style=\"width: 300px\">" ;
							}
							else if ($row["status"]=="Issued") {
								print "<select name='status' id='status' style='width:302px'>" ;
									print "<option selected value='Issued'>" . _('Issued') . "</option>" ;
									print "<option value='Paid'>" . _('Paid') . "</option>" ;
									print "<option value='Paid - Partial'>" . _('Paid - Partial') . "</option>" ;
									print "<option value='Cancelled'>" . _('Cancelled') . "</option>" ;
								print "</select>" ;
							}
							else if ($row["status"]=="Paid" OR $row["status"]=="Paid - Partial") {
								print "<select name='status' id='status' style='width:302px'>" ;
									if ($row["status"]=="Paid") {
										print "<option selected value='Paid'>" . _('Paid') . "</option>" ;
									}
									if ($row["status"]=="Paid - Partial") {
										print "<option value='Paid - Partial'>" . _('Paid - Partial') . "</option>" ;
										print "<option value='Paid - Complete'>" . _('Paid - Complete') . "</option>" ;
									}
									print "<option value='Refunded'>" . _('Refunded') . "</option>" ;
								print "</select>" ;
							}
							?>
						</td>
					</tr>
					<?php
					if ($row["status"]=="Issued" OR $row["status"]=="Paid - Partial") {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								<?php
								if ($row["status"]=="Issued") {
									?>
									$("#paidDateRow").css("display","none");
									$("#paidAmountRow").css("display","none");
									$("#paymentTypeRow").css("display","none");
									$("#paymentTransactionIDRow").css("display","none");
									paidDate.disable() ;
									paidAmount.disable() ;
									paymentType.disable() ;
									<?php
								}
								?>
								$("#status").change(function(){
									if ($('#status option:selected').val()=="Paid" || $('#status option:selected').val()=="Paid - Partial" || $('#status option:selected').val()=="Paid - Complete") {
										$("#paidDateRow").slideDown("fast", $("#paidDateRow").css("display","table-row")); 
										$("#paidAmountRow").slideDown("fast", $("#paidAmountRow").css("display","table-row")); 
										$("#paymentTypeRow").slideDown("fast", $("#paymentTypeRow").css("display","table-row")); 
										$("#paymentTransactionIDRow").slideDown("fast", $("#paymentTransactionIDRow").css("display","table-row")); 
										paidDate.enable() ;
										paidAmount.enable() ;
										paymentType.enable() ;
									} else {
										$("#paidDateRow").css("display","none");
										$("#paidAmountRow").css("display","none");
										$("#paymentTypeRow").css("display","none");
										$("#paymentTransactionIDRow").css("display","none");
										paidDate.disable() ;
										paidAmount.disable() ;
										paymentType.disable() ;
									}
								 });
							});
						</script>
						<tr id="paymentTypeRow">
							<td> 
								<b><?php print _('Payment Type') ?> *</b><br/>
							</td>
							<td class="right">
								<?php
								print "<select name='paymentType' id='paymentType' style='width:302px'>" ;
									print "<option value='Please select...'>" . _('Please select...') . "</option>" ;
									print "<option value='Online'>" . _('Online') . "</option>" ;
									print "<option value='Bank Transfer'>" . _('Bank Transfer') . "</option>" ;
									print "<option value='Cash'>" . _('Cash') . "</option>" ;
									print "<option value='Cheque'>" . _('Cheque') . "</option>" ;
									print "<option value='Other'>" . _('Other') . "</option>" ;
								print "</select>" ;
								?>
								<script type="text/javascript">
									var paymentType=new LiveValidation('paymentType');
									paymentType.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								</script>
							</td>
						</tr>
						<tr id="paymentTransactionIDRow">
							<td> 
								<b><?php print _('Transaction ID') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Date of payment, not entry to system.') ?></i></span>
							</td>
							<td class="right">
								<input name="paymentTransactionID" id="paymentTransactionID" maxlength=50 value="" type="text" style="width: 300px">
							</td>
						</tr>
						<tr id="paidDateRow">
							<td> 
								<b><?php print _('Date Paid') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Date of payment, not entry to system.') ?></i></span>
							</td>
							<td class="right">
								<input name="paidDate" id="paidDate" maxlength=10 value="" type="text" style="width: 300px">
								<script type="text/javascript">
									var paidDate=new LiveValidation('paidDate');
									paidDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									paidDate.add(Validate.Presence);
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#paidDate" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr id="paidAmountRow">
							<td> 
								<b><?php print _('Amount Paid') ?> *</b><br/>
								<span style="font-size: 90%"><i><?php print _('Amount in current payment.') ?>
								<?php
								if ($_SESSION[$guid]["currency"]!="") {
									print "<span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
								}
								?>
								</i></span>
							</td>
							<td class="right">
								<?php
								//Get default paidAmount
								try {
									$dataFees["gibbonFinanceInvoiceID"]=$row["gibbonFinanceInvoiceID"]; 
									$sqlFees="SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber" ;
									$resultFees=$connection2->prepare($sqlFees);
									$resultFees->execute($dataFees);
								}
								catch(PDOException $e) { }
								$paidAmountDefault=0 ;
								while ($rowFees=$resultFees->fetch()) {
									$paidAmountDefault=$paidAmountDefault+$rowFees["fee"] ;
								}
								//If some paid already, work out amount, and subtract it off
								if ($row["status"]=="Paid - Partial") {
									$alreadyPaid=getAmountPaid($connection2, $guid, "gibbonFinanceInvoice", $gibbonFinanceInvoiceID) ;
									$paidAmountDefault-=$alreadyPaid ;
								}
								?>
								<input name="paidAmount" id="paidAmount" maxlength=14 value="<?php print number_format($paidAmountDefault,2,'.','') ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var paidAmount=new LiveValidation('paidAmount');
									paidAmount.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
									paidAmount.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td colspan=2> 
							<b><?php print _('Notes') ?></b> 
							<textarea name='notes' id='notes' rows=5 style='width: 300px'><?php print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Fees') ?></h3>
						</td>
					</tr>
					<?php 
					if ($row["status"]=="Pending") {
						$type="fee" ; 
						?> 
						<style>
							#<?php print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
							#<?php print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							html>body #<?php print $type ?> li { min-height: 58px; line-height: 1.2em; }
							.<?php print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							.<?php print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
						</style>
						<tr>
							<td colspan=2> 
								<div class="fee" id="fee" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
									<?php
									$feeCount=0 ;
									try {
										//Standard
										$dataFees["gibbonFinanceInvoiceID1"]=$row["gibbonFinanceInvoiceID"]; 
										$sqlFees="(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID1 AND feeType='Standard')" ;
										$sqlFees.=" UNION " ;
										//Ad Hoc
										$dataFees["gibbonFinanceInvoiceID2"]=$row["gibbonFinanceInvoiceID"]; 
										$sqlFees.="(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID2 AND feeType='Ad Hoc')" ;
										$sqlFees.=" ORDER BY sequenceNumber" ;
										$resultFees=$connection2->prepare($sqlFees);
										$resultFees->execute($dataFees);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									while ($rowFees=$resultFees->fetch()) {
										makeFeeBlock($guid, $connection2, $feeCount, "edit", $rowFees["feeType"], $rowFees["gibbonFinanceFeeID"], $rowFees["name"], $rowFees["description"], $rowFees["gibbonFinanceFeeCategoryID"], $rowFees["fee"], $rowFees["category"]) ;
										$feeCount++ ;
									}
									?>
								</div>
								<div style='width: 100%; padding: 0px 0px 0px 0px'>
									<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
										<table class='blank' cellspacing='0' style='width: 100%'>
											<tr>
												<td style='width: 50%'>
													<script type="text/javascript">
														var feeCount=<?php print $feeCount ?> ;
													</script>
													<select id='newFee' onChange='feeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
														<option class='all' value='0'><?php print _('Choose a fee to add it') ?></option>
														<?php
														print "<option value='Ad Hoc'>Ad Hoc Fee</option>" ;
														$switchContents="case \"Ad Hoc\": " ;
														$switchContents.="$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
														$switchContents.="$(\"#feeOuter\" + feeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Finance/invoices_manage_add_blockFeeAjax.php\",\"mode=add&id=\" + feeCount + \"&feeType=" . urlencode("Ad Hoc") . "&gibbonFinanceFeeID=&name=" . urlencode("Ad Hoc Fee") . "&description=&gibbonFinanceFeeCategoryID=1&fee=\") ;" ;
														$switchContents.="feeCount++ ;" ;
														$switchContents.="$('#newFee').val('0');" ;
														$switchContents.="break;" ;
														$currentCategory="" ;
														$lastCategory="" ;
														for ($i=0; $i<2; $i++) {
															try {
																$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
																if ($i==0) {
																	$sqlSelect="SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name" ;
																}
																else {
																	$sqlSelect="SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name" ;
																}
																$resultSelect=$connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															while ($rowSelect=$resultSelect->fetch()) {
																$currentCategory=$rowSelect["category"] ;
																if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																	print "<optgroup label='--" . $currentCategory . "--'>" ;
																	$categories[$categoryCount]=$currentCategory ;
																	$categoryCount++ ;
																}
																print "<option value='" . $rowSelect["gibbonFinanceFeeID"] . "'>" . $rowSelect["name"] . "</option>" ;
																$switchContents.="case \"" . $rowSelect["gibbonFinanceFeeID"] . "\": " ;
																$switchContents.="$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
																$switchContents.="$(\"#feeOuter\" + feeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Finance/invoices_manage_add_blockFeeAjax.php\",\"mode=add&id=\" + feeCount + \"&feeType=Standard&gibbonFinanceFeeID=" .  urlencode($rowSelect["gibbonFinanceFeeID"]) . "&name=" . urlencode($rowSelect["name"]) . "&description=" . urlencode($rowSelect["description"]) . "&gibbonFinanceFeeCategoryID=" . urlencode($rowSelect["gibbonFinanceFeeCategoryID"]) . "&fee=" . urlencode($rowSelect["fee"]) . "&category=" . urlencode($rowSelect["category"]) . "\") ;" ;
																$switchContents.="feeCount++ ;" ;
																$switchContents.="$('#newFee').val('0');" ;
																$switchContents.="break;" ;
																$lastCategory=$rowSelect["category"] ;
															}
														}
														?>
													</select>
													<script type='text/javascript'>
														function feeDisplayElements(number) {
															$("#<?php print $type ?>Outer0").css("display", "none") ;
															switch(number) {
																<?php print $switchContents ?>
															}
														}
													</script>
												</td>
											</tr>
										</table>
									</div>
								</div>
							</td>
						</tr>
					<?php
					}
					else {
						print "<tr>" ;
							print "<td colspan=2>" ;
								$feeTotal=0 ;
								try {
									$dataFees["gibbonFinanceInvoiceID"]=$row["gibbonFinanceInvoiceID"]; 
									$sqlFees="SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber" ;
									$resultFees=$connection2->prepare($sqlFees);
									$resultFees->execute($dataFees);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultFees->rowCount()<1) {
									print "<div class='error'>" ;
										print _("There are no records to display.") ;
									print "</div>" ;
								}
								else {
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print _("Name") ;
											print "</th>" ;
											print "<th>" ;
												print _("Category") ;
											print "</th>" ;
											print "<th>" ;
												print _("Description") ;
											print "</th>" ;
											print "<th>" ;
												print _("Fee") . "<br/>" ;
												if ($_SESSION[$guid]["currency"]!="") {
													print "<span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
												}
											print "</th>" ;
										print "</tr>" ;
				
										$count=0;
										$rowNum="odd" ;
										while ($rowFees=$resultFees->fetch()) {
											if ($count%2==0) {
												$rowNum="even" ;
											}
											else {
												$rowNum="odd" ;
											}
											$count++ ;
					
											print "<tr style='height: 25px' class=$rowNum>" ;
												print "<td>" ;
													print $rowFees["name"] ;
												print "</td>" ;
												print "<td>" ;
													print $rowFees["category"] ;
												print "</td>" ;
												print "<td>" ;
													print $rowFees["description"] ;
												print "</td>" ;
												print "<td>" ;
													if (substr($_SESSION[$guid]["currency"],4)!="") {
														print substr($_SESSION[$guid]["currency"],4) . " " ;
													}
													print number_format($rowFees["fee"], 2, ".", ",") ;
													$feeTotal+=$rowFees["fee"] ;
												print "</td>" ;
											print "</tr>" ;
										}
										print "<tr style='height: 35px' class='current'>" ;
											print "<td colspan=3 style='text-align: right'>" ;
												print "<b>" . _('Invoice Total:') . "</b>";
											print "</td>" ;
											print "<td>" ;
												if (substr($_SESSION[$guid]["currency"],4)!="") {
													print substr($_SESSION[$guid]["currency"],4) . " " ;
												}
												print "<b>" . number_format($feeTotal, 2, ".", ",") . "</b>" ;
											print "</td>" ;
										print "</tr>" ;
									print "</table>" ;
								print "</td>" ;
							print "</tr>" ;
						}
					}
					
					//PUT PAYMENT LOG
					print "<tr class='break'>" ;
						print "<td colspan=2>" ; 
							print "<h3>" . _('Payment Log') . "</h3>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td colspan=2>" ;
							print getPaymentLog($connection2, $guid, "gibbonFinanceInvoice", $gibbonFinanceInvoiceID) ;
						print "</td>" ;
					print "</tr>" ;
					
					//Receipt emailing
					if ($row["status"]=="Issued" OR $row["status"]=="Paid - Partial") {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								if ($('#status option:selected').val()!="Paid - Partial") {
									$(".emailReceipt").css("display","none");
								}
								$("#status").change(function(){
									if ($('#status option:selected').val()=="Paid" || $('#status option:selected').val()=="Paid - Partial"  || $('#status option:selected').val()=="Paid - Complete") {
										$(".emailReceipt").slideDown("fast", $(".emailReceipt").css("display","table-row")); 
										$("#emailReceipt").val('Y');
									}
									else {
										$(".emailReceipt").css("display","none");
										$("#emailReceipt").val('N');
									}
								 });
							});
						</script>
						<tr class='break emailReceipt'>
							<td colspan=2> 
								<h3><?php print _('Email Receipt') ?></h3>
								<input type='hidden' id='emailReceipt' name='emailReceipt' value='N'/>
							</td>
						</tr>
						<?php
						$email=getSettingByScope($connection2, "Finance", "email") ;
						if ($email=="") {
							print "<tr class='emailReceipt'>" ;
								print "<td colspan=2>" ; 
									print "<div class='error'>" ;
										print _("An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.") ;
									print "</div>" ;
									print "<input type='hidden' name='email' value='$email'/>" ;
								print "<td>" ; 
							print "<tr>" ;
						}
						else {
							print "<input type='hidden' name='email' value='$email'/>" ;
							if ($row["invoiceTo"]=="Company") {
								if ($row["companyEmail"]!="" AND $row["companyContact"]!="" AND $row["companyName"]!="") {
									?>
									<tr class='emailReceipt'>
										<td> 
											<b><?php print $row["companyContact"] ?></b> (<?php print $row["companyName"] ; ?>)
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<?php print $row["companyEmail"] ; ?> <input checked type='checkbox' name='emails[]' value='<?php print htmlPrep($row["companyEmail"]) ; ?>'/>
											<input type='hidden' name='names[]' value='<?php print htmlPrep($row["companyContact"]) ; ?>'/>
										</td>
									</tr>
									<?php
									if ($row["companyCCFamily"]=="Y") {
										try {
											$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
											$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
											$resultParents=$connection2->prepare($sqlParents);
											$resultParents->execute($dataParents);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultParents->rowCount()<1) {
											print "<div class='warning'>" . _('There are no family members available to send this receipt to.') . "</div>" ; 
										}
										else {
											while ($rowParents=$resultParents->fetch()) {
												if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
													?>
													<tr class='emailReceipt'>
														<td> 
															<b><?php print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b> <i><?php print _('(Family CC)') ?></i>
															<span style="font-size: 90%"><i></i></span>
														</td>
														<td class="right">
															<?php print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails[]' value='<?php print htmlPrep($rowParents["email"]) ; ?>'/>
															<input type='hidden' name='names[]' value='<?php print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
														</td>
													</tr>
													<?php
												}
											}
										}
									}
								}
								else {
									print "<div class='warning'>" . _('There is no company contact available to send this invoice to.') . "</div>" ; 
								}
							}
							else {
								try {
									$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
									$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
									$resultParents=$connection2->prepare($sqlParents);
									$resultParents->execute($dataParents);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultParents->rowCount()<1) {
									print "<div class='warning'>" . _('There are no family members available to send this receipt to.') . "</div>" ; 
								}
								else {
									while ($rowParents=$resultParents->fetch()) {
										if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
											?>
											<tr class='emailReceipt'>
												<td> 
													<b><?php print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<?php print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails[]' value='<?php print htmlPrep($rowParents["email"]) ; ?>'/>
													<input type='hidden' name='names[]' value='<?php print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
												</td>
											</tr>
											<?php
										}
									}
								}
							}
							//CC self?
							if ($_SESSION[$guid]["email"]!="") {
								?>
								<tr class='emailReceipt'>
									<td> 
										<b><?php print formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Parent", false) ?></b>
										<span style="font-size: 90%"><i><?php print _('(CC Self?)') ?></i></span>
									</td>
									<td class="right">
										<?php print $_SESSION[$guid]["email"] ; ?> <input type='checkbox' name='emails[]' value='<?php print $_SESSION[$guid]["email"] ; ?>'/>
										<input type='hidden' name='names[]' value='<?php print formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Parent", FALSE) ; ?>'/>
									</td>
								</tr>
								<?php
							}
						}
					}
					
					//Reminder emailing
					if ($row["status"]=="Issued" AND $row["invoiceDueDate"]<date("Y-m-d")) {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$("#status").change(function(){
									if ($('#status option:selected').val()=="Paid" || $('#status option:selected').val()=="Cancelled" ) {
										$(".emailReminder").css("display","none");
										$("#emailReminder").val('N');
									}
									else {
										$(".emailReminder").slideDown("fast", $(".emailReminder").css("display","table-row")); 
										$("#emailReminder").val('Y');
									}
								 });
							});
						</script>
						<tr class='break emailReminder'>
							<td colspan=2> 
								<h3><?php print _('Email Reminder') ?></h3>
								<input type='hidden' id='emailReminder' name='emailReminder' value='Y'/>
							</td>
						</tr>
						<?php
						$email=getSettingByScope($connection2, "Finance", "email" ) ;
						if ($email=="") {
							print "<tr class='emailReminder'>" ;
								print "<td colspan=2>" ; 
									print "<div class='error'>" ;
										print _("An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.") ;
									print "</div>" ;
									print "<input type='hidden' name='email' value='$email'/>" ;
								print "<td>" ; 
							print "<tr>" ;
						}
						else {
							print "<input type='hidden' name='email' value='$email'/>" ;
							if ($row["invoiceTo"]=="Company") {
								if ($row["companyEmail"]!="" AND $row["companyContact"]!="" AND $row["companyName"]!="") {
									?>
									<tr class='emailReminder'>
										<td> 
											<b><?php print $row["companyContact"] ?></b> (<?php print $row["companyName"] ; ?>)
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<?php print $row["companyEmail"] ; ?> <input checked type='checkbox' name='emails2[]' value='<?php print htmlPrep($row["companyEmail"]) ; ?>'/>
											<input type='hidden' name='names[]' value='<?php print htmlPrep($row["companyContact"]) ; ?>'/>
										</td>
									</tr>
									<?php
									if ($row["companyCCFamily"]=="Y") {
										try {
											$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
											$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
											$resultParents=$connection2->prepare($sqlParents);
											$resultParents->execute($dataParents);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultParents->rowCount()<1) {
											print "<div class='warning'>" . _('There are no family members available to send this receipt to.') . "</div>" ; 
										}
										else {
											while ($rowParents=$resultParents->fetch()) {
												if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
													?>
													<tr class='emailReminder'>
														<td> 
															<b><?php print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b> <i><?php print _('(Family CC)') ?></i>
															<span style="font-size: 90%"><i></i></span>
														</td>
														<td class="right">
															<?php print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails2[]' value='<?php print htmlPrep($rowParents["email"]) ; ?>'/>
															<input type='hidden' name='names[]' value='<?php print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
														</td>
													</tr>
													<?php
												}
											}
										}
									}
								}
								else {
									print "<div class='warning'>" . _('There is no company contact available to send this invoice to.') . "</div>" ; 
								}
							}
							else {
								try {
									$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
									$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
									$resultParents=$connection2->prepare($sqlParents);
									$resultParents->execute($dataParents);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultParents->rowCount()<1) {
									print "<div class='warning'>" . _('There are no family members available to send this receipt to.') . "</div>" ; 
								}
								else {
									while ($rowParents=$resultParents->fetch()) {
										if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
											?>
											<tr class='emailReminder'>
												<td> 
													<b><?php print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<?php print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails2[]' value='<?php print htmlPrep($rowParents["email"]) ; ?>'/>
													<input type='hidden' name='names[]' value='<?php print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
												</td>
											</tr>
											<?php
										}
									}
								}
							}
							//CC self?
							if ($_SESSION[$guid]["email"]!="") {
								?>
								<tr class='emailReminder'>
									<td> 
										<b><?php print formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Parent", false) ?></b>
										<span style="font-size: 90%"><i><?php print _('(CC Self?)') ?></i></span>
									</td>
									<td class="right">
										<?php print $_SESSION[$guid]["email"] ; ?> <input type='checkbox' name='emails[]' value='<?php print $_SESSION[$guid]["email"] ; ?>'/>
										<input type='hidden' name='names[]' value='<?php print formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Parent", FALSE) ; ?>'/>
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
							<input name="gibbonFinanceInvoiceID" id="gibbonFinanceInvoiceID" value="<?php print $gibbonFinanceInvoiceID ?>" type="hidden">
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