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

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_print.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonFinanceInvoiceID=$_GET["gibbonFinanceInvoiceID"] ;
	$type=$_GET["type"] ;
	$preview=NULL ;
	if (isset($_GET["preview"])) {
		$preview=$_GET["preview"] ;
	}
	
	if ($gibbonFinanceInvoiceID=="" OR $gibbonSchoolYearID=="" OR $type=="") {
		print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
			$sql="SELECT surname, preferredName, gibbonFinanceInvoice.* FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
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
			
			$statusExtra="" ;
			if ($row["status"]=="Issued" AND $row["invoiceDueDate"]<date("Y-m-d")) {
				$statusExtra= "Overdue" ;
			}
			if ($row["status"]=="Paid" AND $row["invoiceDueDate"]<$row["paidDate"]) {
				$statusExtra= "Late" ;
			}
			
			if ($type=="invoice") {
				print "<h2>" ;
					print "Invoice" ;
				print "</h2>" ;
				if ($preview) {
					print "<p style='font-weight: bold; color: #c00; font-size: 100%; letter-spacing: -0.5px'>" ;
						print "THIS INVOICE IS A PREVIEW: IT HAS NOT YET BEEN ISSUED AND IS FOR TESTING PURPOSES ONLY!" ;
					print "</p>" ;
				}
				
				$invoiceContents=invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]["currency"], FALSE, TRUE) ;
				if ($invoiceContents==FALSE) {
					print "<div class='error'>" ;
						print "An error occurred in retrieving the invoice." ;
					print "</div>" ;
				}
				else {
					print $invoiceContents ;
				}
			}
			else if ($type=="reminder1" OR $type=="reminder2" OR $type=="reminder3") {
				//Update reminder count
				if ($row["reminderCount"]<3) {
					try {
						$data=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
						$sql="UPDATE gibbonFinanceInvoice SET reminderCount=" . ($row["reminderCount"]+1) . " WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				} 
				
				//Reminder Text
				if ($type=="reminder1") {
					print "<h2>" ;
						print "Reminder 1" ;
					print "</h2>" ;
					$reminderText=getSettingByScope( $connection2, "Finance", "reminder1Text" ) ;
				}
				else if ($type=="reminder2") {
					print "<h2>" ;
						print "Reminder 2" ;
					print "</h2>" ;
					$reminderText=getSettingByScope( $connection2, "Finance", "reminder2Text" ) ;
				}
				else if ($type=="reminder3") {
					print "<h2>" ;
						print "Reminder 3" ;
					print "</h2>" ;
					$reminderText=getSettingByScope( $connection2, "Finance", "reminder3Text" ) ;
				}
				if ($reminderText!="") {
					print "<p>" ;
						print $reminderText ;
					print "</p>" ;
				}
				
				print "<h2>" ;
					print "Invoice" ;
				print "</h2>" ;
				$invoiceContents=invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]["currency"]) ;
				if ($invoiceContents==FALSE) {
					print "<div class='error'>" ;
						print "An error occurred in retrieving the invoice." ;
					print "</div>" ;
				}
				else {
					print $invoiceContents ;
				}
				
				
			}
			else if ($type="Receipt") {
				print "<h2>" ;
					print "Receipt" ;
				print "</h2>" ;
				
				$receiptContents=receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]["currency"]) ;
				if ($receiptContents==FALSE) {
					print "<div class='error'>" ;
						print "An error occurred in retrieving the invoice." ;
					print "</div>" ;
				}
				else {
					print $receiptContents ;
				}
			}
		}
	}
}
?>