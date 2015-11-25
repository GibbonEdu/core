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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$entryCount=0; 
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Invoices') . "</div>" ;
	print "</div>" ;

	print "<p>" ;
		print _("This section allows you to view and invoices for children within your family.") . "<br/>" ;
	print "</p>" ;
	
	//Test data access field for permission
	try {
		$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("Access denied.") ;
		print "</div>" ;
	}
	else {
		//Get child list
		$count=0 ;
		$options="" ;
		while ($row=$result->fetch()) {
			try {
				$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName " ;
				$resultChild=$connection2->prepare($sqlChild);
				$resultChild->execute($dataChild);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowChild=$resultChild->fetch()) {
				$select="" ;
				if (isset($_GET["search"])) {
					if ($rowChild["gibbonPersonID"]==$_GET["search"]) {
						$select="selected" ;
					}
				}
				
				$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student", true). "</option>" ;
				$gibbonPersonID[$count]=$rowChild["gibbonPersonID"] ;
				$count++ ;
			}
		}
		
		if ($count==0) {
			print "<div class='error'>" ;
			print _("Access denied.") ;
			print "</div>" ;
		}
		else if ($count==1) {
			$_GET["search"]=$gibbonPersonID[0] ;
		}
		else {
			print "<h2>" ;
			print "Choose Student" ;
			print "</h2>" ;
			
			?>
			<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
				<table class='noIntBorder' cellspacing='0' style="width: 100%">	
					<tr><td style="width: 30%"></td><td></td></tr>
					<tr>
						<td> 
							<b><?php print _('Search For') ?></b><br/>
							<span style="font-size: 90%"><i>Preferred, surname, username.</i></span>
						</td>
						<td class="right">
							<select name="search" id="search" style="width: 302px">
								<option value=""></value>
								<?php print $options ; ?> 
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/invoices_view.php">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<?php
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_view.php'>" . _('Clear Search') . "</a>" ;
							?>
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
		
		$gibbonPersonID=NULL ;
		if (isset($_GET["search"])) {
			$gibbonPersonID=$_GET["search"] ;
		}
		
		if ($gibbonPersonID!="" AND $count>0) {
			//Confirm access to this student
			try {
				$dataChild=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
				$resultChild=$connection2->prepare($sqlChild);
				$resultChild->execute($dataChild);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultChild->rowCount()<1) {
				print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$rowChild=$resultChild->fetch() ;

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
							print _("The specified record does not exist.") ;
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
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_view.php&search=$gibbonPersonID&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . _('Previous Year') . "</a> " ;
						}
						else {
							print _("Previous Year") . " " ;
						}
						print " | " ;
						if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_view.php&search=$gibbonPersonID&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . _('Next Year') . "</a> " ;
						}
						else {
							print _("Next Year") . " " ;
						}
					print "</div>" ;

					try {
						//Add in filter wheres
						$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonSchoolYearID2"=>$gibbonSchoolYearID, "gibbonPersonID"=>$gibbonPersonID); 
						//SQL for NOT Pending
						$sql="SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' AND gibbonFinanceInvoicee.gibbonPersonID=:gibbonPersonID ORDER BY invoiceIssueDate, surname, preferredName" ; 
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
	
					if ($result->rowCount()<1) {
						print "<h3>" ;
						print _("View") ;
						print "</h3>" ;
		
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<h3>" ;
						print _("View") ;
						print "<span style='font-weight: normal; font-style: italic; font-size: 55%'> " . sprintf(_('%1$s invoice(s) in current view'), $result->rowCount()) . "</span>" ;
						print "</h3>" ;

						print "<form onsubmit='return confirm(\"" ._('Are you sure you wish to process this action? It cannot be undone.') . "\")' method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoices_view_processBulk.php?gibbonSchoolYearID=$gibbonSchoolYearID'>" ;
							print "<fieldset style='border: none'>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th style='width: 110px'>" ;
											print _("Student") . "<br/>" ;
											print "<span style='font-style: italic; font-size: 85%'>" . _('Invoice To') . "</span>" ;
										print "</th>" ;
										print "<th style='width: 110px'>" ;
											print _("Roll Group") ;
										print "</th>" ;
										print "<th style='width: 100px'>" ;
											print _("Status") ;
										print "</th>" ;
										print "<th style='width: 90px'>" ;
											print _("Schedule") ;
										print "</th>" ;
										print "<th style='width: 120px'>" ;
											print _("Total") . " <span style='font-style: italic; font-size: 75%'>(" . $_SESSION[$guid]["currency"] . ")</span><br/>" ;
											print "<span style='font-style: italic; font-size: 75%'>" . _('Paid') . " (" . $_SESSION[$guid]["currency"] . ")</span>" ;
										print "</th>" ;
										print "<th style='width: 80px'>" ;
											print _("Issue Date") . "<br/>" ;
											print "<span style='font-style: italic; font-size: 75%'>" . _('Due Date') . "</span>" ;
										print "</th>" ;
										print "<th style='width: 140px'>" ;
											print _("Actions") ;
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
												if ($row["status"]=="Issued") {
													print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_view_print.php&type=invoice&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID'><img title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
												}
												else if ($row["status"]=="Paid" OR $row["status"]=="Paid - Partial") {
													print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/invoices_view_print.php&type=receipt&gibbonFinanceInvoiceID=" . $row["gibbonFinanceInvoiceID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID'><img title='" . _('Print') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
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
													print "<a title='View Notes' class='show_hide-$count' onclick='false' href='#'><img style='margin-left: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
												}
											print "</td>" ;
										print "</tr>" ;
										if ($row["notes"]!="") {
											print "<tr class='comment-$count' id='comment-$count'>" ;
												print "<td colspan=6>" ;
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
		}		
	}
}
?>