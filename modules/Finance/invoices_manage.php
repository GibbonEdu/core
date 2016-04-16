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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Invoices') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, array("success0" => "Your request was completed successfully.", "success1" => "Your request was completed successfully, but one or more requested emails could not be sent.", "error3" => "Some elements of your request failed, but others were successful.")); }
	
	print "<p>" ;
		print __($guid, "This section allows you to generate, view, edit and delete invoices, either for an individual or in bulk. You can use the filters below to pick up certain invoices types (e.g. those that are overdue) or view all invoices for a particular user. Invoices, reminders and receipts can be sent out using the Email function, shown in the right-hand side menu.") . "<br/>" ;
		print "<br/>" ;
		print __($guid, "When you create invoices using the billing schedule or pre-defined fee features, the invoice will remain linked to these areas whilst pending. Thus, changes made to the billing schedule and pre-defined fees will be reflected in any pending invoices. Once invoices are issued, this link is removed, and the values are fixed at the levels when the invoice was issued.") ;
	print "</p>" ;
	
	$gibbonSchoolYearID="" ;
	if (isset($_GET["gibbonSchoolYearID"])) {
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	}
	if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
		$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
	}
	
	if ($gibbonSchoolYearID!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowcount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	
	if ($gibbonSchoolYearID!="") {
		print "<h2>" ;
			print $gibbonSchoolYearName ;
		print "</h2>" ;
		
		print "<div class='linkTop'>" ;
			//Print year picker
			if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Previous Year') . "</a> " ;
			}
			else {
				print __($guid, "Previous Year") . " " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Next Year') . "</a> " ;
			}
			else {
				print __($guid, "Next Year") . " " ;
			}
		print "</div>" ;
	
		$status=NULL ;
		if (isset($_GET["status"])) {
			$status=$_GET["status"] ;
		}
		if ($status=="") {
			$status="Pending" ;
		}
		$gibbonFinanceInvoiceeID=NULL ;
		if (isset($_GET["gibbonFinanceInvoiceeID"])) {
			$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
		}
		$monthOfIssue=NULL ;
		if (isset($_GET["monthOfIssue"])) {
			$monthOfIssue=$_GET["monthOfIssue"] ;
		}
		$gibbonFinanceBillingScheduleID=NULL ;
		if (isset($_GET["gibbonFinanceBillingScheduleID"])) {
			$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;
		}
		
		print "<h3>" ;
			print __($guid, "Filters") ;
		print "</h3>" ;
		print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php'>" ;
			print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Status') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						print "<select name='status' id='status' style='width:302px'>" ;
							$selected="" ;
							if ($status=="%") {
								$selected="selected" ;
							}
							print "<option $selected value='%'>" . __($guid, 'All') . "</option>" ;
							$selected="" ;
							if ($status=="Pending") {
								$selected="selected" ;
							}
							print "<option $selected value='Pending'>" . __($guid, 'Pending') . "</option>" ;
							$selected="" ;
							if ($status=="Issued") {
								$selected="selected" ;
							}
							print "<option $selected value='Issued'>" . __($guid, 'Issued') . "</option>" ;
							$selected="" ;
							if ($status=="Issued - Overdue") {
								$selected="selected" ;
							}
							print "<option $selected value='Issued - Overdue'>" . __($guid, 'Issued - Overdue') . "</option>" ;
							$selected="" ;
							if ($status=="Paid") {
								$selected="selected" ;
							}
							print "<option $selected value='Paid'>" . __($guid, 'Paid') . "</option>" ;
							$selected="" ;
							if ($status=="Paid - Partial") {
								$selected="selected" ;
							}
							print "<option $selected value='Paid - Partial'>" . __($guid, 'Paid - Partial') . "</option>" ;
							$selected="" ;
							if ($status=="Paid - Late") {
								$selected="selected" ;
							}
							print "<option $selected value='Paid - Late'>" . __($guid, 'Paid - Late') . "</option>" ;
							$selected="" ;
							if ($status=="Cancelled") {
								$selected="selected" ;
							}
							print "<option $selected value='Cancelled'>" . __($guid, 'Cancelled') . "</option>" ;
							$selected="" ;
							if ($status=="Refunded") {
								$selected="selected" ;
							}
							print "<option $selected value='Refunded'>" . __($guid, 'Refunded') . "</option>" ;
						print "</select>" ;
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Student') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						try {
							$dataPurpose=array(); 
							$sqlPurpose="SELECT surname, preferredName, gibbonFinanceInvoiceeID FROM gibbonFinanceInvoicee JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY surname, preferredName" ;
							$resultPurpose=$connection2->prepare($sqlPurpose);
							$resultPurpose->execute($dataPurpose);
						}
						catch(PDOException $e) { }
					
						print "<select name='gibbonFinanceInvoiceeID' id='gibbonFinanceInvoiceeID' style='width:302px'>" ;
							print "<option value=''></option>" ;
							while ($rowPurpose=$resultPurpose->fetch()) {
								$selected="" ;
								if ($rowPurpose["gibbonFinanceInvoiceeID"]==$gibbonFinanceInvoiceeID) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowPurpose["gibbonFinanceInvoiceeID"] . "'>" .  formatName("", htmlPrep($rowPurpose["preferredName"]), htmlPrep($rowPurpose["surname"]), "Student", true) . "</option>" ;
							}
						print "</select>" ;
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Month of Issue') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						print "<select name='monthOfIssue' id='monthOfIssue' style='width:302px'>" ;
							print "<option value=''></option>" ;
							for ($i=1; $i<=12; $i++) {
								$selected="" ;
								if ($monthOfIssue==$i) {
									$selected="selected" ;
								}
								print "<option $selected value=\"" . date("m",mktime(0,0,0,$i,1,0)) . "\">" . date("m",mktime(0,0,0,$i,1,0)) . " - " . date("F",mktime(0,0,0,$i,1,0)) . "</option>" ;
							}
						print "</select>" ;
						?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Billing Schedule') ?></b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?php
						try {
							$dataPurpose=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
							$sqlPurpose="SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
							$resultPurpose=$connection2->prepare($sqlPurpose);
							$resultPurpose->execute($dataPurpose);
						}
						catch(PDOException $e) { }
					
						print "<select name='gibbonFinanceBillingScheduleID' id='gibbonFinanceBillingScheduleID' style='width:302px'>" ;
							print "<option value=''></option>" ;
							while ($rowPurpose=$resultPurpose->fetch()) {
								$selected="" ;
								if ($rowPurpose["gibbonFinanceBillingScheduleID"]==$gibbonFinanceBillingScheduleID) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowPurpose["gibbonFinanceBillingScheduleID"] . "'>" .  $rowPurpose["name"] . "</option>" ;
							}
							$selected="" ;
							if ($gibbonFinanceBillingScheduleID=="Ad Hoc") {
								$selected="selected" ;
							}
							print "<option $selected value='Ad Hoc'>" . __($guid, 'Ad Hoc') . "</option>" ;
						print "</select>" ;
						?>
					</td>
				</tr>
				<?php
				
				print "<tr>" ;
					print "<td class='right' colspan=2>" ;
						print "<input type='hidden' name='gibbonSchoolYearID' value='$gibbonSchoolYearID'>" ;
						print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID'>" . __($guid, 'Clear Filters') . "</a> " ;
						print "<input type='submit' value='" . __($guid, 'Go') . "'>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
		print "</form>" ;
		
		try {
			//Add in filter wheres
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonSchoolYearID2"=>$gibbonSchoolYearID); 
			$whereSched="" ;
			$whereAdHoc="" ;
			$whereNotPending="" ;
			$today=date("Y-m-d") ;
			if ($status!="") {
				if ($status=="Pending") {
					$data["status1"]="Pending" ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1" ;
					$data["status2"]="Pending" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2" ;
					$data["status3"]="Pending" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3" ;
				}
				else if ($status=="Issued") {
					$data["status1"]="Issued" ;
					$data["dateTest1"]=$today ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate>=:dateTest1" ;
					$data["status2"]="Issued" ;
					$data["dateTest2"]=$today ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate>=:dateTest2" ;
					$data["status3"]="Issued" ;
					$data["dateTest3"]=$today ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate>=:dateTest3" ;
				}
				else if ($status=="Issued - Overdue") {
					$data["status1"]="Issued" ;
					$data["dateTest1"]=$today ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate<:dateTest1" ;
					$data["status2"]="Issued" ;
					$data["dateTest2"]=$today ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate<:dateTest2" ;
					$data["status3"]="Issued" ;
					$data["dateTest3"]=$today ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate<:dateTest3" ;
				}
				else if ($status=="Paid") {
					$data["status1"]="Paid" ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate" ;
					$data["status2"]="Paid" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate" ;
					$data["status3"]="Paid" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate" ;
				}
				else if ($status=="Paid - Partial") {
					$data["status1"]="Paid - Partial" ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate" ;
					$data["status2"]="Paid - Partial" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate" ;
					$data["status3"]="Paid - Partial" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate" ;
				}
				else if ($status=="Paid - Late") {
					$data["status1"]="Paid" ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate<paidDate" ;
					$data["status2"]="Paid" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate<paidDate" ;
					$data["status3"]="Paid" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate<paidDate" ;
				}
				else if ($status=="Cancelled") {
					$data["status1"]="Cancelled" ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1" ;
					$data["status2"]="Cancelled" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2" ;
					$data["status3"]="Cancelled" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3" ;
				}
				
				else if ($status=="Refunded") {
					$data["status1"]="Refunded" ;
					$whereSched.=" AND gibbonFinanceInvoice.status=:status1" ;
					$data["status2"]="Refunded" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.status=:status2" ;
					$data["status3"]="Refunded" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.status=:status3" ;
				}
			}
			if ($gibbonFinanceInvoiceeID!="") {
				$data["gibbonFinanceInvoiceeID1"]=$gibbonFinanceInvoiceeID ;
				$whereSched.=" AND gibbonFinanceInvoice.gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID1" ;
				$data["gibbonFinanceInvoiceeID2"]=$gibbonFinanceInvoiceeID ;
				$whereAdHoc.=" AND gibbonFinanceInvoice.gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID2" ;
				$data["gibbonFinanceInvoiceeID3"]=$gibbonFinanceInvoiceeID ;
				$whereNotPending.=" AND gibbonFinanceInvoice.gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID3" ;
			}
			if ($monthOfIssue!="") {
				$data["monthOfIssue1"]="%-$monthOfIssue-%" ;
				$whereSched.=" AND gibbonFinanceInvoice.invoiceIssueDate LIKE :monthOfIssue1" ;
				$data["monthOfIssue2"]="%-$monthOfIssue-%" ;
				$whereAdHoc.=" AND gibbonFinanceInvoice.invoiceIssueDate LIKE :monthOfIssue2" ;
				$data["monthOfIssue3"]="%-$monthOfIssue-%" ;
				$whereNotPending.=" AND gibbonFinanceInvoice.invoiceIssueDate LIKE :monthOfIssue3" ;
			}
			if ($gibbonFinanceBillingScheduleID!="") {
				if ($gibbonFinanceBillingScheduleID=="Ad Hoc") {
					$data["billingScheduleType1"]="Ah Hoc" ;
					$whereSched.=" AND gibbonFinanceInvoice.billingScheduleType=:billingScheduleType1" ;
					$data["billingScheduleType2"]="Ad Hoc" ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.billingScheduleType=:billingScheduleType2" ;
					$data["billingScheduleType3"]="Ad Hoc" ;
					$whereNotPending.=" AND gibbonFinanceInvoice.billingScheduleType=:billingScheduleType3" ;
				}
				else if ($gibbonFinanceBillingScheduleID!="") {
					$data["gibbonFinanceBillingScheduleID1"]=$gibbonFinanceBillingScheduleID ;
					$whereSched.=" AND gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID1" ;
					$data["gibbonFinanceBillingScheduleID2"]=$gibbonFinanceBillingScheduleID ;
					$whereAdHoc.=" AND gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID2" ;
					$data["gibbonFinanceBillingScheduleID3"]=$gibbonFinanceBillingScheduleID ;
					$whereNotPending.=" AND gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID3" ;
				}
			}
			//SQL for billing schedule AND pending
			$sql="(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceBillingSchedule.invoiceDueDate, paidDate, paidAmount, gibbonFinanceBillingSchedule.name AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Scheduled' AND gibbonFinanceInvoice.status='Pending' $whereSched)" ; 
			$sql.=" UNION " ; 
			//SQL for Ad Hoc AND pending
			$sql.="(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, invoiceIssueDate, invoiceDueDate, paidDate, paidAmount, 'Ad Hoc' AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)  WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Ad Hoc' AND gibbonFinanceInvoice.status='Pending' $whereAdHoc)" ; 
			$sql.=" UNION " ; 
			//SQL for NOT Pending
			$sql.="(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)  WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' $whereNotPending)" ; 
			$sql.=" ORDER BY FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled'), invoiceIssueDate, surname, preferredName" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()<1) {
			print "<h3>" ;
			print __($guid, "View") ;
			print "</h3>" ;
			
			print "<div class='linkTop' style='text-align: right'>" ;
				print "<a style='margin-right: 3px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new_multi.png'/></a><br/>" ;
			print "</div>" ;
			
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			print "<h3>" ;
			print __($guid, "View") ;
			print "<span style='font-weight: normal; font-style: italic; font-size: 55%'> " . sprintf(__($guid, '%1$s records(s) in current view'), $result->rowCount()) . "</span>" ;
			print "</h3>" ;

			print "<form onsubmit='return confirm(\"" .__($guid, 'Are you sure you wish to process this action? It cannot be undone.') . "\")' method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_processBulk.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" ;
				print "<fieldset style='border: none'>" ;
					print "<div class='linkTop' style='text-align: right; margin-bottom: 40px'>" ;
						print "<div style='margin: 0 0 3px 0'>" ;
							print "<a style='margin-right: 3px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new_multi.png'/></a><br/>" ;
						print "</div>" ;
						?>
						<input style='margin-top: 0px; float: right' type='submit' value='<?php print __($guid, 'Go') ?>'>
						<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action"><?php print __($guid, 'Select action') ?></option>
							<?php
							if ($status=="Pending") {
								print "<option value=\"delete\">" . __($guid, 'Delete') . "</option>" ;
								print "<option value=\"issue\">" . __($guid, 'Issue') . "</option>" ;
							}
							if ($status=="Issued - Overdue") {
								print "<option value=\"reminders\">" . __($guid, 'Issue Reminders') . "</option>" ;
							}
							print "<option value=\"export\">" . __($guid, 'Export') . "</option>" ;
							?>
						</select>
						<script type="text/javascript">
							var action=new LiveValidation('action');
							action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
						<?php
					print "</div>" ;	
					
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th style='width: 110px'>" ;
								print __($guid, "Student") . "<br/>" ;
								print "<span style='font-style: italic; font-size: 85%'>" . __($guid, 'Invoice To') . "</span>" ;
							print "</th>" ;
							print "<th style='width: 110px'>" ;
								print __($guid, "Roll Group") ;
							print "</th>" ;
							print "<th style='width: 100px'>" ;
								print __($guid, "Status") ;
							print "</th>" ;
							print "<th style='width: 90px'>" ;
								print __($guid, "Schedule") ;
							print "</th>" ;
							print "<th style='width: 120px'>" ;
								print __($guid, "Total") . " <span style='font-style: italic; font-size: 75%'>(" . $_SESSION[$guid]["currency"] . ")</span><br/>" ;
								print "<span style='font-style: italic; font-size: 75%'>" . __($guid, 'Paid') . " (" . $_SESSION[$guid]["currency"] . ")</span>" ;
							print "</th>" ;
							print "<th style='width: 80px'>" ;
								print __($guid, "Issue Date") . "<br/>" ;
								print "<span style='font-style: italic; font-size: 75%'>" . __($guid, 'Due Date') . "</span>" ;
							print "</th>" ;
							print "<th style='width: 140px'>" ;
								print __($guid, "Actions") ;
							print "</th>" ;
							print "<th>" ;
								?>
								<script type="text/javascript">
									$(function () {
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?php
								print "<input type='checkbox' class='checkall'>" ;
							print "</th>" ;
						print "</tr>" ;
			
						$count=0;
						$rowNum="odd" ;
						while ($row=$result->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
				
							//Work out extra status information
							$statusExtra="" ;
							if ($row["status"]=="Issued" AND $row["invoiceDueDate"]<date("Y-m-d")) {
								$statusExtra="Overdue" ;
							}
							if ($row["status"]=="Paid" AND $row["invoiceDueDate"]<$row["paidDate"]) {
								$statusExtra="Late" ;
							}
				
							//Color row by status
							if ($row["status"]=="Paid") {
								$rowNum="current" ;	
							}
							if ($row["status"]=="Issued" AND $statusExtra=="Overdue") {
								$rowNum="error" ;	
							}
				
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<b>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) . "</b><br/>" ;
									print "<span style='font-style: italic; font-size: 85%'>" . $row["invoiceTo"] . "</span>" ;
								print "</td>" ;
								print "<td>" ;
									print $row["rollGroup"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["status"] ;
									if ($statusExtra!="") {
										print " - $statusExtra" ;
									}
								print "</td>" ;
								print "<td>" ;
									if ($row["billingScheduleExtra"]!="")  {
										print $row["billingScheduleExtra"] ;
									}
									else { 
										print $row["billingSchedule"] ;
									}
								print "</td>" ;
								print "<td>" ;
									//Calculate total value
									$totalFee=0 ;
									$feeError=FALSE ;
									try {
										$dataTotal=array("gibbonFinanceInvoiceID"=>$row["gibbonFinanceInvoiceID"]); 
										if ($row["status"]=="Pending") {
											$sqlTotal="SELECT gibbonFinanceInvoiceFee.fee AS fee, gibbonFinanceFee.fee AS fee2 FROM gibbonFinanceInvoiceFee LEFT JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
										}
										else {
											$sqlTotal="SELECT gibbonFinanceInvoiceFee.fee AS fee, NULL AS fee2 FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
										}
										$resultTotal=$connection2->prepare($sqlTotal);
										$resultTotal->execute($dataTotal);
									}
									catch(PDOException $e) { print $e->getMessage() ; print "<i>Error calculating total</i>" ; $feeError=TRUE ;}
									while ($rowTotal=$resultTotal->fetch()) {
										if (is_numeric($rowTotal["fee2"])) {
											$totalFee+=$rowTotal["fee2"] ;
										}
										else {
											$totalFee+=$rowTotal["fee"] ;
										}
									}
									if ($feeError==FALSE) {
										if (substr($_SESSION[$guid]["currency"],4)!="") {
											print substr($_SESSION[$guid]["currency"],4) . " " ;
										}
										print number_format($totalFee, 2, ".", ",") . "<br/>" ;
										if ($row["paidAmount"]!="") {
											$styleExtra="" ;
											if ($row["paidAmount"]!=$totalFee) {
												$styleExtra="color: #c00;" ;
											}
											print "<span style='$styleExtra font-style: italic; font-size: 85%'>" ;
											if (substr($_SESSION[$guid]["currency"],4)!="") {
												print substr($_SESSION[$guid]["currency"],4) . " " ;
											}
											print number_format($row["paidAmount"], 2, ".", ",") . "</span>" ;
										}
									}
								print "</td>" ;
								print "<td>" ;
									if (is_null($row["invoiceIssueDate"])) {
										print "NA<br/>" ;
									}
									else {
										print dateConvertBack($guid, $row["invoiceIssueDate"]) . "<br/>" ;
									}
									print "<span style='font-style: italic; font-size: 75%'>" . dateConvertBack($guid, $row["invoiceDueDate"]) . "</span>" ;
								print "</td>" ;
								print "<td>" ;
									if ($row["status"]!="Cancelled" AND $row["status"]!="Refunded") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_edit.php&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									}
									if ($row["status"]=="Pending") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_issue.php&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'><img title='Issue' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_right.png'/></a><br/>" ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_delete.php&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
										print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print_print.php&type=invoice&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&preview=true'><img title='Preview Invoice' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
									}
									if ($row["status"]!="Pending") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_print.php&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'><img title='Print Invoices, Receipts & Reminders' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
									}
									print "<script type='text/javascript'>" ;	
										print "$(document).ready(function(){" ;
											print "\$(\".comment-$count\").hide();" ;
											print "\$(\".show_hide-$count\").fadeIn(1000);" ;
											print "\$(\".show_hide-$count\").click(function(){" ;
											print "\$(\".comment-$count\").fadeToggle(1000);" ;
											print "});" ;
										print "});" ;
									print "</script>" ;
									if ($row["notes"]!="") {
										print "<a title='View Notes' class='show_hide-$count' onclick='false' href='#'><img style='margin-left: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . __($guid, 'Show Comment') . "' onclick='return false;' /></a>" ;
									}
								print "</td>" ;
								print "<td>" ;
									print "<input type='checkbox' name='gibbonFinanceInvoiceIDs[]' value='" . $row["gibbonFinanceInvoiceID"] . "'>" ;
								print "</td>" ;
							print "</tr>" ;
							if ($row["notes"]!="") {
								print "<tr class='comment-$count' id='comment-$count'>" ;
									print "<td colspan=7>" ;
										print $row["notes"] ;
									print "</td>" ;
								print "</tr>" ;
							}
						}
						print "<input type=\"hidden\" name=\"address\" value=\"" . $_SESSION[$guid]["address"] . "\">" ;
						
					print "</fieldset>" ;
				print "</table>" ;
			print "</form>" ;
		}
	}
}
?>