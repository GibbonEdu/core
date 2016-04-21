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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$gibbonFinanceInvoiceID=$_POST["gibbonFinanceInvoiceID"] ;
$status=$_GET["status"] ;
$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
$monthOfIssue=$_GET["monthOfIssue"] ;
$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;

if ($gibbonFinanceInvoiceID=="" OR $gibbonSchoolYearID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/invoices_manage_edit.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_edit.php")==FALSE) {
		//Fail 0
		$URL.="&return=error0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonFinanceInvoiceID=="") {
			//Fail1
			$URL.="&return=error1" ;
			header("Location: {$URL}");
		}
		else {
			//LOCK INVOICE TABLES
			try {
				$data=array(); 
				$sql="LOCK TABLES gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonFinanceInvoicee WRITE, gibbonPayment READ" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
				
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
				$sql="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL.="&return=error2" ;
				header("Location: {$URL}");
			}
			else {
				$row=$result->fetch() ;
				$notes=$_POST["notes"] ;
				$status=$row["status"] ;
				if ($status!="Pending") {
					$status=$_POST["status"] ;
					if ($status=="Paid - Complete") {
						$status="Paid" ;
					}
				}
				$order=NULL ;
				if (isset($_POST["order"])) {
					$order=$_POST["order"] ;
				}
				if ($_POST["status"]=="Paid" OR $_POST["status"]=="Paid - Partial" OR $_POST["status"]=="Paid - Complete" OR $_POST["status"]=="Refunded") {
					$paidDate=dateConvert($guid, $_POST["paidDate"]) ;
				}
				else {
					$paidDate=NULL ;
				}
				if ($_POST["status"]=="Paid" OR $_POST["status"]=="Paid - Partial" OR $_POST["status"]=="Paid - Complete" OR $_POST["status"]=="Refunded") {
					$paidAmountLog=$_POST["paidAmount"] ;
					$paidAmount=$_POST["paidAmount"] ;
					//If some paid already, work out amount, and add it to total
					$alreadyPaid=getAmountPaid($connection2, $guid, "gibbonFinanceInvoice", $gibbonFinanceInvoiceID) ;
					$paidAmount+=$alreadyPaid ;
				}
				else {
					$paidAmount=NULL ;
				}
				$paymentType=NULL ;
				if ($_POST["status"]=="Paid" OR $_POST["status"]=="Paid - Partial" OR $_POST["status"]=="Paid - Complete") {
					$paymentType=$_POST["paymentType"] ;
				}
				$paymentTransactionID=NULL ;
				if ($_POST["status"]=="Paid" OR $_POST["status"]=="Paid - Partial" OR $_POST["status"]=="Paid - Complete") {
					$paymentTransactionID=$_POST["paymentTransactionID"] ;
				}
				if ($row["billingScheduleType"]=="Ad Hoc" AND ($row["status"]=="Pending" OR $row["status"]=="Issued")) {
					$invoiceDueDate=dateConvert($guid, $_POST["invoiceDueDate"]) ;
				}
				else {
					$invoiceDueDate=$row["invoiceDueDate"] ;
				}
				
				
				//Write to database
				try {
					$data=array("status"=>$status, "notes"=>$notes, "paidDate"=>$paidDate, "paidAmount"=>$paidAmount, "invoiceDueDate"=>$invoiceDueDate, "gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
					$sql="UPDATE gibbonFinanceInvoice SET status=:status, notes=:notes, paidDate=:paidDate, paidAmount=:paidAmount, invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&return=error2" ;
					header("Location: {$URL}");
					exit() ;
				}
				
				$partialFail=FALSE ;
					
				if ($status=="Pending") {
					if (is_null($order)) {
						$partialFail=TRUE ;
					}
					else {
						//Remove fees
						try {
							$data=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
							$sql="DELETE FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
				
						//Organise Fees
						$fess=array() ;
						foreach ($order AS $fee){
							$fees[$fee]["name"]=$_POST["name" . $fee] ;
							$fees[$fee]["gibbonFinanceFeeCategoryID"]=$_POST["gibbonFinanceFeeCategoryID" . $fee] ;
							$fees[$fee]["fee"]=$_POST["fee" . $fee] ;
							$fees[$fee]["feeType"]=$_POST["feeType" . $fee] ;
							$fees[$fee]["gibbonFinanceFeeID"]=$_POST["gibbonFinanceFeeID" . $fee] ;
							$fees[$fee]["description"]=$_POST["description" . $fee] ;
						}
						
						//Add fees to invoice
						$count=0 ;
						foreach ($fees AS $fee) {
							$count++ ;
							try {
								if ($fee["feeType"]=="Standard") {
									$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "feeType"=>$fee["feeType"], "gibbonFinanceFeeID"=>$fee["gibbonFinanceFeeID"]); 
									$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count" ;
								}
								else {
									$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "feeType"=>$fee["feeType"], "name"=>$fee["name"], "description"=>$fee["description"], "gibbonFinanceFeeCategoryID"=>$fee["gibbonFinanceFeeCategoryID"], "fee"=>$fee["fee"] ); 
									$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count" ;
								}
								$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
								$resultInvoiceFee->execute($dataInvoiceFee);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
					}
				}
				
				//Unlock tables
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }	
				
				$emailFail=FALSE ;
				//Email Receipt
				if (isset($_POST["emailReceipt"])) {	
					if ($_POST["emailReceipt"]=="Y") {
						$from=$_POST["email"] ;
						if ($partialFail==FALSE AND $from!="") { 
							//Send emails
							$emails=NULL ;
							if (isset($_POST["emails"])) {
								$emails=$_POST["emails"] ;
							}
							if (count($emails)>0) {
								require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/class.phpmailer.php';
				
								//Get receipt number
								try {
									$dataPayments=array("foreignTable"=>"gibbonFinanceInvoice", "foreignTableID"=>$gibbonFinanceInvoiceID); 
									$sqlPayments="SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp, gibbonPaymentID" ;
									$resultPayments=$connection2->prepare($sqlPayments);
									$resultPayments->execute($dataPayments);
								}
								catch(PDOException $e) { }
								$receiptCount=$resultPayments->rowCount() ;
								
								//Prep message
								$body=receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]["currency"], TRUE, $receiptCount) . "<p class='emphasis'>Email sent via " . $_SESSION[$guid]["systemName"] . " at " . $_SESSION[$guid]["organisationName"] . ".</p>" ;
								$bodyPlain="This email is not viewable in plain text: enable rich text/HTML in your email client to view the receipt. Please reply to this email if you have any questions." ;
		
								$mail=new PHPMailer;
								$mail->SetFrom($from, $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
								foreach ($emails AS $address) {
									$mail->AddBCC($address);
								}
								$mail->CharSet="UTF-8"; 
								$mail->Encoding="base64" ;
								$mail->IsHTML(true);                            
								$mail->Subject="Receipt From " . $_SESSION[$guid]["organisationNameShort"] . " via " . $_SESSION[$guid]["systemName"] ;
								$mail->Body=$body ;
								$mail->AltBody=$bodyPlain ;

								if(!$mail->Send()) {
									$emailFail=TRUE ;
								}
							}
							else {
								$emailFail=TRUE ;
							}
						}
					}
				}
				//Email reminder
				if (isset($_POST["emailReminder"])) {	
					if ($_POST["emailReminder"]=="Y") {
						$from=$_POST["email"] ;
						if ($partialFail==FALSE AND $from!="") { 
							//Send emails
							$emails=$_POST["emails2"] ;
							if (count($emails)>0) {
							
								require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/class.phpmailer.php';
				
								$body="" ;
								//Prep message
								if ($row["reminderCount"]=="0") {
									$reminderText=getSettingByScope( $connection2, "Finance", "reminder1Text" ) ;
								}
								else if ($row["reminderCount"]=="1") {
									$reminderText=getSettingByScope( $connection2, "Finance", "reminder2Text" ) ;
								}
								else if ($row["reminderCount"]>="2") {
									$reminderText=getSettingByScope( $connection2, "Finance", "reminder3Text" ) ;
								}
								if ($reminderText!="") {
									$reminderOutput=$row["reminderCount"]+1 ;
									if ($reminderOutput>3) {
										$reminderOutput="3+" ;
									}
									$body.="<p>Reminder " . $reminderOutput . ": " . $reminderText . "</p><br/>" ;
								}
								$body.=invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]["currency"], TRUE) . "<p class='emphasis'>Email sent via " . $_SESSION[$guid]["systemName"] . " at " . $_SESSION[$guid]["organisationName"] . ".</p>" ;
								$bodyPlain="This email is not viewable in plain text: enable rich text/HTML in your email client to view the reminder. Please reply to this email if you have any questions." ;
	
								//Update reminder count
								if ($row["reminderCount"]<3) {
									try {
										$data=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
										$sql="UPDATE gibbonFinanceInvoice SET reminderCount=" . ($row["reminderCount"]+1) . " WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { }
								} 
									
								$mail=new PHPMailer;
								$mail->SetFrom($from, $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
								foreach ($emails AS $address) {
									$mail->AddBCC($address);
								}
								$mail->CharSet="UTF-8"; 
								$mail->Encoding="base64" ;
								$mail->IsHTML(true);                            
								$mail->Subject="Reminder From " . $_SESSION[$guid]["organisationNameShort"] . " via " . $_SESSION[$guid]["systemName"] ;
								$mail->Body=$body ;
								$mail->AltBody=$bodyPlain ;

								if(!$mail->Send()) {
									$emailFail=TRUE ;
								}
							}
							else {
								$emailFail=TRUE ;
							}
						}
					}
				}
				
				if ($status=="Paid" OR $status=="Paid - Partial") {
					if ($_POST["status"]=="Paid") {
						$statusLog="Complete" ;
					}
					else if ($_POST["status"]=="Paid - Partial") {
						$statusLog="Partial" ;
					}
					else if ($_POST["status"]=="Paid - Complete") {
						$statusLog="Final" ;
					}
					$logFail=setPaymentLog($connection2, $guid, "gibbonFinanceInvoice", $gibbonFinanceInvoiceID, $paymentType, $statusLog, $paidAmountLog, NULL, NULL, NULL, NULL, $paymentTransactionID, NULL, $paidDate) ;
					if ($logFail==FALSE) {
						$partialFail=TRUE ;
					}
				}
				
				if ($partialFail==TRUE) { 
					//Fail 4
					$URL.="&return=error3" ;
					header("Location: {$URL}");
				}
				else if ($emailFail==TRUE) { 
					//Success 1
					$URL.="&return=success1" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL.="&return=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>