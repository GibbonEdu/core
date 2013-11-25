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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

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
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/invoices_manage_issue.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ;
	$URLSuccess=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/invoices_manage.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_edit.php")==FALSE) {
		//Fail 0
		$URL=$URL . "&issueReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Check if person specified
		if ($gibbonFinanceInvoiceID=="") {
			//Fail1
			$URL=$URL . "&issueReturn=fail1" ;
			header("Location: {$URL}");
		}
		else {
			//LOCK INVOICE TABLES
			try {
				$data=array(); 
				$sql="LOCK TABLES gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonFinanceInvoicee WRITE, gibbonFinanceFee WRITE, gibbonFinanceFeeCategory WRITE" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL=$URL . "&issueReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
				$sql="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Pending'" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail2
				$URL=$URL . "&issueReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			if ($result->rowCount()!=1) {
				//Fail 2
				$URL=$URL . "&issueReturn=fail2" ;
				header("Location: {$URL}");
			}
			else {
				$row=$result->fetch() ;
				$notes=$_POST["notes"] ;
				$status="Issued" ;
				$invoiceDueDate=$_POST["invoiceDueDate"] ;
				if ($row["billingScheduleType"]=="Scheduled") {
					$separated="Y" ;
				}
				else {
					$separated=NULL ;
				}
				$invoiceIssueDate=date("Y-m-d") ;
				
				if ($invoiceDueDate=="") {
					//Fail 3
					$URL=$URL . "&issueReturn=fail3" ;
					header("Location: {$URL}");
				}
				else {
					//Write to database
					try {
						$data=array("status"=>$status, "notes"=>$notes, "separated"=>$separated, "invoiceDueDate"=>dateConvert($invoiceDueDate), "invoiceIssueDate"=>$invoiceIssueDate, "gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
						$sql="UPDATE gibbonFinanceInvoice SET status=:status, notes=:notes, separated=:separated, invoiceDueDate=:invoiceDueDate, invoiceIssueDate=:invoiceIssueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL=$URL . "&issueReturn=fail2" ;
						header("Location: {$URL}");
						break ;
					}
				
					$partialFail=FALSE ;
					
					//Read & Organise Fees
					$fess=array() ;
					$count=0 ;
					//Standard Fees
					try {
						$dataFees["gibbonFinanceInvoiceID"]=$gibbonFinanceInvoiceID; 
						$sqlFees="SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND feeType='Standard' ORDER BY sequenceNumber" ;
						$resultFees=$connection2->prepare($sqlFees);
						$resultFees->execute($dataFees);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
					while ($rowFees=$resultFees->fetch()) {
						$fees[$count]["name"]=$rowFees["name"] ;
						$fees[$count]["gibbonFinanceFeeCategoryID"]=$rowFees["gibbonFinanceFeeCategoryID"] ;
						$fees[$count]["fee"]=$rowFees["fee"] ;
						$fees[$count]["feeType"]="Standard" ;
						$fees[$count]["gibbonFinanceFeeID"]=$rowFees["gibbonFinanceFeeID"] ;
						$fees[$count]["separated"]="Y" ;
						$fees[$count]["description"]=$rowFees["description"] ;
						$fees[$count]["sequenceNumber"]=$rowFees["sequenceNumber"] ;
						$count++ ;
					}
				
					//Ad Hoc Fees
					try {
						$dataFees["gibbonFinanceInvoiceID"]=$gibbonFinanceInvoiceID; 
						$sqlFees="SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND feeType='Ad Hoc' ORDER BY sequenceNumber" ;
						$resultFees=$connection2->prepare($sqlFees);
						$resultFees->execute($dataFees);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE ;
					}
					while ($rowFees=$resultFees->fetch()) {
						$fees[$count]["name"]=$rowFees["name"] ;
						$fees[$count]["gibbonFinanceFeeCategoryID"]=$rowFees["gibbonFinanceFeeCategoryID"] ;
						$fees[$count]["fee"]=$rowFees["fee"] ;
						$fees[$count]["feeType"]="Ad Hoc" ;
						$fees[$count]["gibbonFinanceFeeID"]=NULL ;
						$fees[$count]["separated"]=NULL ;
						$fees[$count]["description"]=$rowFees["description"] ;
						$fees[$count]["sequenceNumber"]=$rowFees["sequenceNumber"] ;
						$count++ ;
					}
				
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
		
					//Add fees to invoice
					foreach ($fees AS $fee) {
						try {
							$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "feeType"=>$fee["feeType"], "gibbonFinanceFeeID"=>$fee["gibbonFinanceFeeID"], "name"=>$fee["name"], "description"=>$fee["description"], "gibbonFinanceFeeCategoryID"=>$fee["gibbonFinanceFeeCategoryID"], "fee"=>$fee["fee"], "separated"=>$fee["separated"], "sequenceNumber"=>$fee["sequenceNumber"] ); 
							$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, separated=:separated, sequenceNumber=:sequenceNumber" ;
							$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
							$resultInvoiceFee->execute($dataInvoiceFee);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
					}
					
					//Unlock module table
					try {
						$sql="UNLOCK TABLES" ;
						$result=$connection2->query($sql);   
					}
					catch(PDOException $e) { }	
					
					$from=$_POST["email"] ;
					if ($partialFail==FALSE AND $from!="") { 
						//Send emails
						$emailFail=FALSE ;
						$emails=NULL ;
						if (isset($_POST["emails"])) {
							$emails=$_POST["emails"] ;
						}
						if (count($emails)>0) {
							require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/class.phpmailer.php';
				
							//Prep message
							$body=invoiceContents($connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]["currency"]) . "<p style='font-style: italic;'>Email sent via " . $_SESSION[$guid]["systemName"] . " at " . $_SESSION[$guid]["organisationName"] . ".</p>" ;
							$bodyPlain="This email is not viewable in plain text: enable rich text/HTML in your email client to view the invoice. Please reply to this email if you have any questions." ;

							$mail=new PHPMailer;
							$mail->SetFrom($from, $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
							foreach ($emails AS $address) {
								$mail->AddBCC($address);
							}
							$mail->CharSet="UTF-8"; 
							$mail->IsHTML(true);                            
							$mail->Subject="Invoice From " . $_SESSION[$guid]["organisationNameShort"] . " via " . $_SESSION[$guid]["systemName"] ;
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
				
					if ($partialFail==TRUE) { 
						//Fail 4
						$URL=$URL . "&issueReturn=fail4" ;
						header("Location: {$URL}");
					}
					else if ($emailFail==TRUE) { 
						//Success 1
						$URLSuccess=$URLSuccess . "&issueReturn=success1" ;
						header("Location: {$URLSuccess}");
					}
					else {
						//Success 0
						$URLSuccess=$URLSuccess . "&issueReturn=success0" ;
						header("Location: {$URLSuccess}");
					}
				}
			}
		}
	}
}
?>