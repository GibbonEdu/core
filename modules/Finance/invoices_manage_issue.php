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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_issue.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	//Check if school year specified
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonFinanceInvoiceID=$_GET["gibbonFinanceInvoiceID"] ;
	$status=$_GET["status"] ;
	$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
	$monthOfIssue=$_GET["monthOfIssue"] ;
	$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;
	
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>Manage Invoices</a> > </div><div class='trailEnd'>Issue Invoice</div>" ;
	print "</div>" ;
	
	print "<p>" ;
	print "Issuing an invoice confirms it in the system, meaning the financial details within the invoice can no longer be edited. On issue, you also have the choice to email the invoice to the appropriate family and company recipients." ;
	print "</p>" ;
	
	if (isset($_GET["issueReturn"])) { $issueReturn=$_GET["issueReturn"] ; } else { $issueReturn="" ; }
	$issueReturnMessage ="" ;
	$class="error" ;
	if (!($issueReturn=="")) {
		if ($issueReturn=="fail0") {
			$issueReturnMessage ="Issue failed because you do not have access to this action." ;	
		}
		else if ($issueReturn=="fail1") {
			$issueReturnMessage ="Issue failed because a required parameter was not set." ;	
		}
		else if ($issueReturn=="fail2") {
			$issueReturnMessage ="Issue failed due to a database error." ;	
		}
		else if ($issueReturn=="fail3") {
			$issueReturnMessage ="Issue failed because your inputs were invalid." ;	
		}
		else if ($issueReturn=="fail4") {
			$issueReturnMessage ="Some aspects of your issue failed, but others were successful. Because of the errors, the system did not attempt to send any requested emails." ;	
		}
		print "<div class='$class'>" ;
			print $issueReturnMessage;
		print "</div>" ;
	} 
	
	if ($gibbonFinanceInvoiceID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
			$sql="SELECT gibbonFinanceInvoice.*, companyName, companyContact, companyEmail, companyCCFamily FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Pending'" ; 
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
			
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoices_manage_issueProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
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
									$invoiceDueDate=$rowSchedule["invoiceDueDate"] ;
								}
								?>
								<input readonly name="schedule" id="schedule" value="<? print $schedule ?>" type="text" style="width: 300px">
								<input name="invoiceDueDate" id="invoiceDueDate" value="<? print dateConvertBack($guid, $invoiceDueDate) ?>" type="hidden" style="width: 300px">
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
								<input readonly name="invoiceDueDate" id="invoiceDueDate" value="<? print dateConvertBack($guid, $row["invoiceDueDate"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?
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
							if ($row["status"]=="Pending") {
								print "<input readonly name=\"status\" id=\"status\" value=\"" . $row["status"] . "\" type=\"text\" style=\"width: 300px\">" ;
							}
							else {
							
							}
							?>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b>Notes</b> 
							<textarea name='notes' id='notes' rows=5 style='width: 300px'><? print htmlPrep($row["notes"]) ?></textarea>
						</td>
					</tr>
					
					<tr>
						<td colspan=2> 
							<h3>Email Invoice</h3>
						</td>
					</tr>
					<?
					$email=getSettingByScope($connection2, "Finance", "email" ) ;
					if ($email=="") {
						print "<tr>" ;
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
								<tr>
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
								//CC family
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
												<tr>
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
										<tr>
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
					//CC self?
					if ($_SESSION[$guid]["email"]!="") {
						?>
						<tr>
							<td> 
								<b><? print formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Parent", false) ?></b>
								<span style="font-size: 90%"><i>(CC Self?)</i></span>
							</td>
							<td class="right">
								<? print $_SESSION[$guid]["email"] ; ?> <input type='checkbox' name='emails[]' value='<? print $_SESSION[$guid]["email"] ; ?>'/>
								<input type='hidden' name='names[]' value='<? print formatName("", htmlPrep($_SESSION[$guid]["preferredName"]), htmlPrep($_SESSION[$guid]["surname"]), "Parent", FALSE) ; ?>'/>
							</td>
						</tr>
						<?
					}
					?>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input name="gibbonFinanceInvoiceID" id="gibbonFinanceInvoiceID" value="<? print $gibbonFinanceInvoiceID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<? print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>