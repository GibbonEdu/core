<?
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
		print "You do not have access to this action." ;
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>Manage Invoices</a> > </div><div class='trailEnd'>Edit Invoice</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Some aspects of your update failed, but others were successful." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		if ($updateReturn=="success1") {
			$updateReturnMessage ="Update was successful, but one or more requested emails could not be sent." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	if ($gibbonFinanceInvoiceID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print "You have not specified an invoice or school year." ;
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
				print "The specified invoice cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($status!="" OR $gibbonFinanceInvoiceeID!="" OR $monthOfIssue!="" OR $gibbonFinanceBillingScheduleID!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>Back to Search Results</a>" ;
				print "</div>" ;
			}
			?>
			
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>Basic Information</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>School Year *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<?
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
							<input readonly name="yearName" id="yearName" value="<? print $yearName ?>" type="text" style="width: 300px">
					</tr>
					<tr>
						<td> 
							<b>Invoicee *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<?
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
							<input readonly name="personName" id="personName" value="<? print $personName ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<? //BILLING TYPE CHOOSER ?>
					<tr>
						<td> 
							<b>Scheduling *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="billingScheduleType" id="billingScheduleType" value="<? print $row["billingScheduleType"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?
					if ($row["billingScheduleType"]=="Scheduled") {
						?>
						<tr>
							<td> 
								<b>Billing Schedule *</b><br/>
								<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
							</td>
							<td class="right">
								<?
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
								<input readonly name="schedule" id="schedule" value="<? print $schedule ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?
					}
					else {
						if ($row["status"]=="Pending" OR $row["status"]=="Issued") {
							?>
							<tr>
								<td> 
									<b>Invoice Due Date *</b><br/>
								</td>
								<td class="right">
									<input name="invoiceDueDate" id="invoiceDueDate" value="<? print dateConvertBack($row["invoiceDueDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var invoiceDueDate=new LiveValidation('invoiceDueDate');
										invoiceDueDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
										invoiceDueDate.add(Validate.Presence);
									 </script>
									 <script type="text/javascript">
										$(function() {
											$( "#invoiceDueDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<?
						}
						else {
							?>
							<tr>
								<td> 
									<b>Invoice Due Date *</b><br/>
									<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
								</td>
								<td class="right">
									<input readonly name="invoiceDueDate" id="invoiceDueDate" value="<? print dateConvertBack($row["invoiceDueDate"]) ?>" type="text" style="width: 300px">
								</td>
							</tr>
							<?
						}
					}
					?>
					<tr>
						<td> 
							<b>Status *</b><br/>
							<?
							if ($row["status"]=="Pending") {
								print "<span style=\"font-size: 90%\"><i>This value cannot be changed. Use the Issue function to change the status from \"Pending\" to \"Issued\".</i></span>" ;
							}
							else {
								print "<span style=\"font-size: 90%\"><i>Available options are limited according to current status.</i></span>" ;
							}
							?>
						</td>
						<td class="right">
							<?
							if ($row["status"]=="Pending" OR $row["status"]=="Cancelled" OR $row["status"]=="Refunded") {
								print "<input readonly name=\"status\" id=\"status\" value=\"" . $row["status"] . "\" type=\"text\" style=\"width: 300px\">" ;
							}
							else if ($row["status"]=="Issued") {
								print "<select name='status' id='status' style='width:302px'>" ;
									print "<option selected value='Issued'>Issued</option>" ;
									print "<option value='Paid'>Paid</option>" ;
									print "<option value='Cancelled'>Cancelled</option>" ;
								print "</select>" ;
							}
							else if ($row["status"]=="Paid") {
								print "<select name='status' id='status' style='width:302px'>" ;
									print "<option selected value='Paid'>Paid</option>" ;
									print "<option value='Refunded'>Refunded</option>" ;
								print "</select>" ;
							}
							else {
								
							}
							?>
						</td>
					</tr>
					<?
					if ($row["status"]=="Issued") {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$("#paidDateRow").css("display","none");
								paidDate.disable() ;
								$("#status").change(function(){
									if ($('#status option:selected').val() == "Paid" ) {
										$("#paidDateRow").slideDown("fast", $("#paidDateRow").css("display","table-row")); 
										paidDate.enable() ;
									} else {
										$("#paidDateRow").css("display","none");
										paidDate.disable() ;
									}
								 });
							});
						</script>
						<tr id="paidDateRow">
							<td> 
								<b>Date Paid *</b><br/>
								<span style="font-size: 90%"><i>Date of payment, not entry to system.</i></span>
							</td>
							<td class="right">
								<input name="paidDate" id="paidDate" maxlength=10 value="" type="text" style="width: 300px">
								<script type="text/javascript">
									var paidDate=new LiveValidation('paidDate');
									paidDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
									paidDate.add(Validate.Presence);
								 </script>
								 <script type="text/javascript">
									$(function() {
										$( "#paidDate" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<?
					}
					else if ($row["status"]=="Paid") {
						?>
						<tr>
							<td> 
								<b>Date Paid *</b><br/>
								<span style="font-size: 90%"><i>Date of payment, not entry to system.</i></span>
							</td>
							<td class="right">
								<input readonly name="paidDate" id="paidDate" maxlength=10 value="<? print dateConvertBack($row["paidDate"]) ; ?> " type="text" style="width: 300px">
							</td>
						</tr>
						<?
					}
					?>
					<tr>
						<td colspan=2> 
							<b>Notes</b> 
							<textarea name='notes' id='notes' rows=5 style='width: 300px'><? print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>
					
					<tr class='break'>
						<td colspan=2> 
							<h3>Fees</h3>
						</td>
					</tr>
					<? 
					if ($row["status"]=="Pending") {
						$type="fee" ; 
						?> 
						<style>
							#<? print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
							#<? print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							html>body #<? print $type ?> li { min-height: 58px; line-height: 1.2em; }
							.<? print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							.<? print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
						</style>
						<tr>
							<td colspan=2> 
								<div class="fee" id="fee" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
									<?
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
														var feeCount=<? print $feeCount ?> ;
													</script>
													<select id='newFee' onChange='feeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
														<option class='all' value='0'>Choose a fee to add it</option>
														<?
														print "<option value='Ad Hoc'>Ad Hoc Fee</option>" ;
														$switchContents.="case \"Ad Hoc\": " ;
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
																	$categories[$categoryCount]= $currentCategory ;
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
															$("#<? print $type ?>Outer0").css("display", "none") ;
															switch(number) {
																<? print $switchContents ?>
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
					<?
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
										print "There are no fees to display" ;
									print "</div>" ;
								}
								else {
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print "Name" ;
											print "</th>" ;
											print "<th>" ;
												print "Category" ;
											print "</th>" ;
											print "<th>" ;
												print "Description" ;
											print "</th>" ;
											print "<th>" ;
												print "Fee<br/>" ;
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
												print "<b>Invoice Total  : </b>";
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
					
					//Receipt emailing
					if ($row["status"]=="Issued") {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$(".emailReceipt").css("display","none");
								$("#status").change(function(){
									if ($('#status option:selected').val() == "Paid" ) {
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
								<h3>Email Receipt</h3>
								<input type='hidden' id='emailReceipt' name='emailReceipt' value='N'/>
							</td>
						</tr>
						<?
						$email=getSettingByScope($connection2, "Finance", "email" ) ;
						if ($email=="") {
							print "<tr class='emailReceipt'>" ;
								print "<td colspan=2>" ; 
									print "<div class='error'>" ;
										print "An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent." ;
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
											<b><?print $row["companyContact"] ?></b> (<? print $row["companyName"] ; ?>)
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<? print $row["companyEmail"] ; ?> <input checked type='checkbox' name='emails[]' value='<? print htmlPrep($row["companyEmail"]) ; ?>'/>
											<input type='hidden' name='names[]' value='<? print htmlPrep($row["companyContact"]) ; ?>'/>
										</td>
									</tr>
									<?
									if ($row["companyCCFamily"]=="Y") {
										try {
											$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
											$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
											$resultParents=$connection2->prepare($sqlParents);
											$resultParents->execute($dataParents);
										}
										catch(PDOException $e) { 
											$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultParents->rowCount()<1) {
											$return.="<div class='warning'>There are no family members available to send this receipt to.</div>" ; 
										}
										else {
											while ($rowParents=$resultParents->fetch()) {
												if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
													?>
													<tr class='emailReceipt'>
														<td> 
															<b><? print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b> <i>(Family CC)</i>
															<span style="font-size: 90%"><i></i></span>
														</td>
														<td class="right">
															<? print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails[]' value='<? print htmlPrep($rowParents["email"]) ; ?>'/>
															<input type='hidden' name='names[]' value='<? print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
														</td>
													</tr>
													<?
												}
											}
										}
									}
								}
								else {
									$return.="<div class='warning'>There is no company contact available to send this invoice to.</div>" ; 
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
									$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultParents->rowCount()<1) {
									$return.="<div class='warning'>There are no family members available to send this receipt to.</div>" ; 
								}
								else {
									while ($rowParents=$resultParents->fetch()) {
										if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
											?>
											<tr class='emailReceipt'>
												<td> 
													<b><? print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<? print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails[]' value='<? print htmlPrep($rowParents["email"]) ; ?>'/>
													<input type='hidden' name='names[]' value='<? print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
												</td>
											</tr>
											<?
										}
									}
								}
							}
						}
					}
					
					//Reminder emailing
					if ($row["status"]=="Issued" AND $row["invoiceDueDate"]<date("Y-m-d")) {
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$("#status").change(function(){
									if ($('#status option:selected').val() == "Paid" || $('#status option:selected').val() == "Cancelled" ) {
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
								<h3>Email Reminder</h3>
								<input type='hidden' id='emailReminder' name='emailReminder' value='Y'/>
							</td>
						</tr>
						<?
						$email=getSettingByScope($connection2, "Finance", "email" ) ;
						if ($email=="") {
							print "<tr class='emailReminder'>" ;
								print "<td colspan=2>" ; 
									print "<div class='error'>" ;
										print "An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent." ;
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
											<b><?print $row["companyContact"] ?></b> (<? print $row["companyName"] ; ?>)
											<span style="font-size: 90%"><i></i></span>
										</td>
										<td class="right">
											<? print $row["companyEmail"] ; ?> <input checked type='checkbox' name='emails2[]' value='<? print htmlPrep($row["companyEmail"]) ; ?>'/>
											<input type='hidden' name='names[]' value='<? print htmlPrep($row["companyContact"]) ; ?>'/>
										</td>
									</tr>
									<?
									if ($row["companyCCFamily"]=="Y") {
										try {
											$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
											$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
											$resultParents=$connection2->prepare($sqlParents);
											$resultParents->execute($dataParents);
										}
										catch(PDOException $e) { 
											$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultParents->rowCount()<1) {
											$return.="<div class='warning'>There are no family members available to send this receipt to.</div>" ; 
										}
										else {
											while ($rowParents=$resultParents->fetch()) {
												if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
													?>
													<tr class='emailReminder'>
														<td> 
															<b><? print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b> <i>(Family CC)</i>
															<span style="font-size: 90%"><i></i></span>
														</td>
														<td class="right">
															<? print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails2[]' value='<? print htmlPrep($rowParents["email"]) ; ?>'/>
															<input type='hidden' name='names[]' value='<? print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
														</td>
													</tr>
													<?
												}
											}
										}
									}
								}
								else {
									$return.="<div class='warning'>There is no company contact available to send this invoice to.</div>" ; 
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
									$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultParents->rowCount()<1) {
									$return.="<div class='warning'>There are no family members available to send this receipt to.</div>" ; 
								}
								else {
									while ($rowParents=$resultParents->fetch()) {
										if ($rowParents["preferredName"]!="" AND $rowParents["surname"]!="" AND $rowParents["email"]!="") {
											?>
											<tr class='emailReminder'>
												<td> 
													<b><? print formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) ?></b>
													<span style="font-size: 90%"><i></i></span>
												</td>
												<td class="right">
													<? print $rowParents["email"] ; ?> <input checked type='checkbox' name='emails2[]' value='<? print htmlPrep($rowParents["email"]) ; ?>'/>
													<input type='hidden' name='names[]' value='<? print htmlPrep(formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false)) ; ?>'/>
												</td>
											</tr>
											<?
										}
									}
								}
							}
						}
					}
					?>			
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input name="gibbonFinanceInvoiceID" id="gibbonFinanceInvoiceID" value="<? print $gibbonFinanceInvoiceID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>