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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_print.php")==FALSE) {
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
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" . _('Manage Invoices') . "</a> > </div><div class='trailEnd'>" . _('Print Invoices, Receipts & Reminders') . "</div>" ;
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
			$sql="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
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
			
			if ($row["status"]=="Pending") {
				print "<div class='error'>" ;
					print _("There is nothing to print, as the invoice has yet to be issued.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print _("Item") ;
						print "</th>" ;
						print "<th style='width: 120px'>" ;
							print _("Actions") ;
						print "</th>" ;
					print "</tr>" ;
				
					$count=0;
					$rowNum="even" ;
					
					?>
					<tr class='<?php print $rowNum ?>'>
						<td> 
							<b><?php print _('Invoice') ?></b><br/>
						</td>
						<td class="left">
							<?php
							print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=invoice&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  _('Print') . "<img style='margin-left: 5px' title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
							?>
						</td>
					</tr>
					<?php
					$count++ ;
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					?>
					<?php
					if ($row["status"]=="Issued") {
						if ($row["reminderCount"]>=0) {
							?>
							<tr class='<?php print $rowNum ?>'>
								<td> 
									<b><?php print _('Reminder 1') ?></b><br/>
								</td>
								<td class="left">
									<?php
									print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=reminder1&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  _('Print') . "<img style='margin-left: 5px' title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
									?>
								</td>
							</tr>
							<?php
						}
						$count++ ;
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						if ($row["reminderCount"]>=1) {
							?>
							<tr class='<?php print $rowNum ?>'>
								<td> 
									<b><?php print _('Reminder 2') ?></b><br/>
								</td>
								<td class="left">
									<?php
									print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=reminder2&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  _('Print') . "<img style='margin-left: 5px' title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
									?>
								</td>
							</tr>
							<?php
						}
						$count++ ;
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						if ($row["reminderCount"]>=2) {
							?>
							<tr class='<?php print $rowNum ?>'>
								<td> 
									<b><?php print _('Reminder 3') ?></b><br/>
								</td>
								<td class="left">
									<?php
									print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=reminder3&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  _('Print') . "<img style='margin-left: 5px' title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
									?>
								</td>
							</tr>
							<?php
						}
					}
					if ($row["status"]=="Paid") {
						//Get individual payments that make up receipt
						try {
							$data=array("foreignTable"=>"gibbonFinanceInvoice", "foreignTableID"=>$gibbonFinanceInvoiceID); 
							$sql="SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp, gibbonPaymentID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($result->rowCount()<1) {
							?>
							<tr class='<?php print $rowNum ?>'>
								<td> 
									<b><?php print _('Receipt') ?></b><br/>
								</td>
								<td class="left">
									<?php
									print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=receipt&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  _('Print') . "<img style='margin-left: 5px' title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
									?>
								</td>
							</tr>
							<?php
						}
						else {
							$count2=0; 
							while ($row=$result->fetch()) {
								if ($count%2==0) {
									$rowNum="even" ;
								}
								else {
									$rowNum="odd" ;
								}?>
								<tr class='<?php print $rowNum ?>'>
									<td> 
										<b><?php print sprintf(_('Receipt %1$s'), ($count2+1)) ?></b><br/>
									</td>
									<td class="left">
										<?php
										print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=receipt&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&receiptNumber=$count2'>" .  _('Print') . "<img style='margin-left: 5px' title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
										?>
									</td>
								</tr>
								<?php
								$count++ ;
								$count2++ ;
							}
						}
					}
				print "</table>" ;
			}
		}
	}
}
?>