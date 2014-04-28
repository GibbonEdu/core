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
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
$status=$_GET["status"] ;
$gibbonFinanceInvoiceeID=$_GET["gibbonFinanceInvoiceeID"] ;
$monthOfIssue=$_GET["monthOfIssue"] ;
$gibbonFinanceBillingScheduleID=$_GET["gibbonFinanceBillingScheduleID"] ;

if ($gibbonSchoolYearID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/invoices_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/invoices_manage_add.php")==FALSE) {
		//Fail 0
		$URL=$URL . "&addReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		$gibbonFinanceInvoiceeIDs=$_POST["gibbonFinanceInvoiceeIDs"] ;
		$scheduling=$_POST["scheduling"] ;
		if ($scheduling=="Scheduled") {
			$gibbonFinanceBillingScheduleID=$_POST["gibbonFinanceBillingScheduleID"] ;
			$invoiceDueDate=NULL ;
		}
		else if ($scheduling=="Ad Hoc") {
			$gibbonFinanceBillingScheduleID=NULL ;
			$invoiceDueDate=$_POST["invoiceDueDate"] ;
		}
		$notes=$_POST["notes"] ;
		$order=$_POST["order"] ;
			
		if (count($gibbonFinanceInvoiceeIDs)==0 OR $scheduling=="" OR ($scheduling=="Scheduled" AND $gibbonFinanceBillingScheduleID=="") OR ($scheduling=="Ad Hoc" AND $invoiceDueDate=="") OR count($order)==0) {
			//Fail 3
			$URL=$URL . "&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			$studentFailCount=0 ;
			$invoiceFailCount=0 ;
			$invoiceFeeFailCount=0 ;
			$feeFail=FALSE ;
			
			//PROCESS FEES
			$fess=array() ;
			foreach ($order AS $fee){
				$fees[$fee]["name"]=$_POST["name" . $fee] ;
				$fees[$fee]["gibbonFinanceFeeCategoryID"]=$_POST["gibbonFinanceFeeCategoryID" . $fee] ;
				$fees[$fee]["fee"]=$_POST["fee" . $fee] ;
				$fees[$fee]["feeType"]=$_POST["feeType" . $fee] ;
				$fees[$fee]["gibbonFinanceFeeID"]=$_POST["gibbonFinanceFeeID" . $fee] ;
				$fees[$fee]["description"]=$_POST["description" . $fee] ;
				
				if ($fees[$fee]["name"]=="" OR $fees[$fee]["gibbonFinanceFeeCategoryID"]=="" OR $fees[$fee]["fee"]=="" OR is_numeric($fees[$fee]["fee"])==FALSE OR $fees[$fee]["feeType"]=="" OR ($fees[$fee]["feeType"]=="Standard" AND $fees[$fee]["gibbonFinanceFeeID"]=="")) {
					$feeFail=TRUE ;
				}
			}
			
			if ($feeFail==TRUE) {
				//Fail3
				$URL=$URL . "&addReturn=fail3" ;
				header("Location: {$URL}");
				break ;
			}
			else {
				//LOCK INVOICE TABLES
				try {
					$data=array(); 
					$sql="LOCK TABLES gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonFinanceInvoicee WRITE" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL=$URL . "&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
			
				//CYCLE THROUGH STUDENTS
				foreach ($gibbonFinanceInvoiceeIDs AS $gibbonFinanceInvoiceeID) {
					$thisStudentFailed=FALSE ;
					$invoiceTo="" ;
					$companyAll="" ;
					$gibbonFinanceFeeCategoryIDList="" ;
					
					//GET INVOICE RECORD, set $invoiceTo and $companyCategories if required
					try {
						$data=array("gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
						$sql="SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$studentFailCount++ ;
						$thisStudentFailed=TRUE ;
					}
					if ($result->rowCount()!=1) {
						if ($thisStudentFailed!=TRUE) {
							$studentFailCount++ ;
							$thisStudentFailed=TRUE ;
						}
					}
					else {
						$row=$result->fetch() ;
						$invoiceTo=$row["invoiceTo"] ;
						if ($invoiceTo!="Family" AND $invoiceTo!="Company") {
							$studentFailCount++ ;
							$thisStudentFailed=TRUE ;
						}
						else {
							if ($invoiceTo=="Company") {
								$companyAll=$row["companyAll"] ;
								if ($companyAll=="N") {
									$gibbonFinanceFeeCategoryIDList=$row["gibbonFinanceFeeCategoryIDList"] ;
									if ($gibbonFinanceFeeCategoryIDList!="") {
										$gibbonFinanceFeeCategoryIDs=explode(",", $gibbonFinanceFeeCategoryIDList) ;
									}
									else {
										$gibbonFinanceFeeCategoryIDs=NULL ;
									}
								}
								
								$companyFamily=FALSE ; //This holds true when company is set, companyAll=N and there are some fees for the family to pay...
								foreach ($fees AS $fee) {
									if ($invoiceTo=="Company" AND $companyAll=="N" AND strpos($gibbonFinanceFeeCategoryIDList,$fee["gibbonFinanceFeeCategoryID"])===FALSE) {
										$companyFamily=TRUE ;
									}
								}		
								$companyFamilyCompanyHasCharges=FALSE ; //This holds true when company is set, companyAll=N and there are some fees for the company to pay...e.g.  they are not all held by the family
								if ($invoiceTo=="Company" AND $companyAll=="N") {
									foreach ($fees AS $fee) {
										if ($invoiceTo=="Company" AND $companyAll=="N" AND is_numeric(strpos($gibbonFinanceFeeCategoryIDList,$fee["gibbonFinanceFeeCategoryID"]))) {
											$companyFamilyCompanyHasCharges=TRUE ;
										}
									}		
								}				
							}
						}
					}
				
					if ($thisStudentFailed==FALSE) {
						//CHECK FOR INVOICE AND UPDATE/ADD FOR FAMILY (INC WHEN COMPANY IS PAYING ONLY SOME FEES)
						if ($invoiceTo=="Family" OR $companyFamily==TRUE) {
							$thisInvoiceFailed=FALSE ;
							try {
								if ($scheduling=="Scheduled") {
									$dataInvoice=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "gibbonFinanceBillingScheduleID"=>$gibbonFinanceBillingScheduleID); 
									$sqlInvoice="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Family' AND billingScheduleType='Scheduled' AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID AND status='Pending'" ;
								}
								else {
									$dataInvoice=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
									$sqlInvoice="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Family' AND billingScheduleType='Ad Hoc' AND status='Pending'" ;
								}
								$resultInvoice=$connection2->prepare($sqlInvoice);
								$resultInvoice->execute($dataInvoice);
							}
							catch(PDOException $e) { 
								$invoiceFailCount++ ;
								$thisInvoiceFailed=TRUE ;
							}
							if ($resultInvoice->rowCount()==0 AND $thisInvoiceFailed==FALSE) {
								//ADD INVOICE
								//Get next autoincrement
								try {
									$dataAI=array(); 
									$sqlAI="SHOW TABLE STATUS LIKE 'gibbonFinanceInvoice'";
									$resultAI=$connection2->prepare($sqlAI);
									$resultAI->execute($dataAI);
								}
								catch(PDOException $e) { 
									$invoiceFailCount++ ;
									$thisInvoiceFailed=TRUE ;
								}
								if ($resultAI->rowCount()==1) {
									$rowAI=$resultAI->fetch();
									$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
								}
							
								if ($AI=="") {
									if ($thisInvoiceFailed==FALSE) {
										$invoiceFailCount++ ;
										$thisInvoiceFailed=TRUE ;
									}
								}
								else {
									//Add invoice
									try {
										if ($scheduling=="Scheduled") {
											$dataInvoiceAdd=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "gibbonFinanceBillingScheduleID"=>$gibbonFinanceBillingScheduleID, "notes"=>$notes, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlInvoiceAdd="INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Family', billingScheduleType='Scheduled', gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID, notes=:notes, status='Pending', separated='N', gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='" . date("Y-m-d H:i:s") . "'" ;
										}
										else {
											$dataInvoiceAdd=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "invoiceDueDate"=>dateConvert($guid, $invoiceDueDate), "notes"=>$notes, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlInvoiceAdd="INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Family', billingScheduleType='Ad Hoc', status='Pending', invoiceDueDate=:invoiceDueDate, notes=:notes, gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='" . date("Y-m-d H:i:s") . "'" ;
										}
										$resultInvoiceAdd=$connection2->prepare($sqlInvoiceAdd);
										$resultInvoiceAdd->execute($dataInvoiceAdd);
									}
									catch(PDOException $e) { 
										print $e->getMessage() ;
										$invoiceFailCount++ ;
										$thisInvoiceFailed=TRUE ;
									}
									if ($thisInvoiceFailed==FALSE) {
										//Add fees to invoice
										$count=0 ;
										foreach ($fees AS $fee) {
											$count++ ;
											if ($invoiceTo=="Family" OR ($invoiceTo=="Company" AND $companyAll=="N" AND strpos($gibbonFinanceFeeCategoryIDList,$fee["gibbonFinanceFeeCategoryID"])===FALSE)) {
												try {
													if ($fee["feeType"]=="Standard") {
														$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$AI, "feeType"=>$fee["feeType"], "gibbonFinanceFeeID"=>$fee["gibbonFinanceFeeID"]); 
														$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count" ;
													}
													else {
														$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$AI, "feeType"=>$fee["feeType"], "name"=>$fee["name"], "description"=>$fee["description"], "gibbonFinanceFeeCategoryID"=>$fee["gibbonFinanceFeeCategoryID"], "fee"=>$fee["fee"]); 
														$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count" ;
													}
													$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
													$resultInvoiceFee->execute($dataInvoiceFee);
												}
												catch(PDOException $e) { 
													$invoiceFeeFailCount++ ;
												}
											}
										}
									}
								}
							}
							else if ($resultInvoice->rowCount()==1 AND $thisInvoiceFailed==FALSE) {
								$rowInvoice=$resultInvoice->fetch() ;
								
								//Add fees to invoice
								$count=0 ;
								foreach ($fees AS $fee) {
									$count++ ;
									if ($invoiceTo=="Family" OR ($invoiceTo=="Company" AND $companyAll=="N" AND strpos($gibbonFinanceFeeCategoryIDList,$fee["gibbonFinanceFeeCategoryID"])===FALSE)) {
										try {
											if ($fee["feeType"]=="Standard") {
												$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"], "feeType"=>$fee["feeType"], "gibbonFinanceFeeID"=>$fee["gibbonFinanceFeeID"]); 
												$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count" ;
											}
											else {
												$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"], "feeType"=>$fee["feeType"], "name"=>$fee["name"], "description"=>$fee["description"], "gibbonFinanceFeeCategoryID"=>$fee["gibbonFinanceFeeCategoryID"], "fee"=>$fee["fee"] ); 
												$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count" ;
											}
											$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
											$resultInvoiceFee->execute($dataInvoiceFee);
										}
										catch(PDOException $e) { 
											$invoiceFeeFailCount++ ;
										}
									}
								}
								
								//Update invoice
								try {
									if ($scheduling=="Scheduled") {
										$dataInvoiceAdd=array("gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "notes"=>$rowInvoice["notes"] . " " . $notes, "gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"]); 
										$sqlInvoiceAdd="UPDATE gibbonFinanceInvoice SET gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
									}
									else {
										$dataInvoiceAdd=array("invoiceDueDate"=>dateConvert($guid, $invoiceDueDate), "gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "notes"=>$rowInvoice["notes"] . " " . $notes, "gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"]); 
										$sqlInvoiceAdd="UPDATE gibbonFinanceInvoice SET invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
									}
									$resultInvoiceAdd=$connection2->prepare($sqlInvoiceAdd);
									$resultInvoiceAdd->execute($dataInvoiceAdd);
								}
								catch(PDOException $e) { 
									$invoiceFailCount++ ;
									$thisInvoiceFailed=TRUE ;
								}
							}
							else {
								if ($thisInvoiceFailed==FALSE) {
									$invoiceFailCount++ ;
									$thisInvoiceFailed=TRUE ;
								}
							}
						}
						
						//CHECK FOR INVOICE AND UPDATE/ADD FOR COMPANY
						if (($invoiceTo=="Company" AND $companyAll=="Y" ) OR ($invoiceTo=="Company" AND $companyAll=="N" AND $companyFamilyCompanyHasCharges==TRUE)) {
							$thisInvoiceFailed=FALSE ;
							try {
								if ($scheduling=="Scheduled") {
									$dataInvoice=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "gibbonFinanceBillingScheduleID"=>$gibbonFinanceBillingScheduleID); 
									$sqlInvoice="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Company' AND billingScheduleType='Scheduled' AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID AND status='Pending'" ;
								}
								else {
									$dataInvoice=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID); 
									$sqlInvoice="SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Company' AND billingScheduleType='Ad Hoc' AND status='Pending'" ;
								}
								$resultInvoice=$connection2->prepare($sqlInvoice);
								$resultInvoice->execute($dataInvoice);
							}
							catch(PDOException $e) { 
								$invoiceFailCount++ ;
								$thisInvoiceFailed=TRUE ;
							}
							if ($resultInvoice->rowCount()==0 AND $thisInvoiceFailed==FALSE) {
								//ADD INVOICE
								//Get next autoincrement
								try {
									$dataAI=array(); 
									$sqlAI="SHOW TABLE STATUS LIKE 'gibbonFinanceInvoice'";
									$resultAI=$connection2->prepare($sqlAI);
									$resultAI->execute($dataAI);
								}
								catch(PDOException $e) { 
									$invoiceFailCount++ ;
									$thisInvoiceFailed=TRUE ;
								}
								if ($resultAI->rowCount()==1) {
									$rowAI=$resultAI->fetch();
									$AI=str_pad($rowAI['Auto_increment'], 14, "0", STR_PAD_LEFT) ;
								}
							
								if ($AI=="") {
									if ($thisInvoiceFailed==FALSE) {
										$invoiceFailCount++ ;
										$thisInvoiceFailed=TRUE ;
									}
								}
								else {
									//Add invoice
									try {
										if ($scheduling=="Scheduled") {
											$dataInvoiceAdd=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "gibbonFinanceBillingScheduleID"=>$gibbonFinanceBillingScheduleID, "notes"=>$notes, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlInvoiceAdd="INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Company', billingScheduleType='Scheduled', gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID, notes=:notes, status='Pending', separated='N', gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='" . date("Y-m-d H:i:s") . "'" ;
										}
										else {
											$dataInvoiceAdd=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceeID"=>$gibbonFinanceInvoiceeID, "invoiceDueDate"=>dateConvert($guid, $invoiceDueDate), "notes"=>$notes, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlInvoiceAdd="INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Company', billingScheduleType='Ad Hoc', status='Pending', invoiceDueDate=:invoiceDueDate, notes=:notes, gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='" . date("Y-m-d H:i:s") . "'" ;
										}
										$resultInvoiceAdd=$connection2->prepare($sqlInvoiceAdd);
										$resultInvoiceAdd->execute($dataInvoiceAdd);
									}
									catch(PDOException $e) { 
										print $e->getMessage() ;
										$invoiceFailCount++ ;
										$thisInvoiceFailed=TRUE ;
									}
									if ($thisInvoiceFailed==FALSE) {
										//Add fees to invoice
										$count=0 ;
										foreach ($fees AS $fee) {
											$count++ ;
											if (($invoiceTo=="Company" AND $companyAll=="Y") OR ($invoiceTo=="Company" AND $companyAll=="N" AND is_numeric(strpos($gibbonFinanceFeeCategoryIDList,$fee["gibbonFinanceFeeCategoryID"])))) {
												try {
													if ($fee["feeType"]=="Standard") {
														$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$AI, "feeType"=>$fee["feeType"], "gibbonFinanceFeeID"=>$fee["gibbonFinanceFeeID"]); 
														$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count" ;
													}
													else {
														$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$AI, "feeType"=>$fee["feeType"], "name"=>$fee["name"], "description"=>$fee["description"], "gibbonFinanceFeeCategoryID"=>$fee["gibbonFinanceFeeCategoryID"], "fee"=>$fee["fee"] ); 
														$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count" ;
													}
													$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
													$resultInvoiceFee->execute($dataInvoiceFee);
												}
												catch(PDOException $e) { 
													$invoiceFeeFailCount++ ;
												}
											}
										}
									}
								}
							}
							else if ($resultInvoice->rowCount()==1 AND $thisInvoiceFailed==FALSE) {
								$rowInvoice=$resultInvoice->fetch() ;
								
								//Add fees to invoice
								$count=0 ;
								foreach ($fees AS $fee) {
									$count++ ;
									if (($invoiceTo=="Company" AND $companyAll=="Y") OR ($invoiceTo=="Company" AND $companyAll=="N" AND is_numeric(strpos($gibbonFinanceFeeCategoryIDList,$fee["gibbonFinanceFeeCategoryID"])))) {
										try {
											if ($fee["feeType"]=="Standard") {
												$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"], "feeType"=>$fee["feeType"], "gibbonFinanceFeeID"=>$fee["gibbonFinanceFeeID"]); 
												$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count" ;
											}
											else {
												$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"], "feeType"=>$fee["feeType"], "name"=>$fee["name"], "description"=>$fee["description"], "gibbonFinanceFeeCategoryID"=>$fee["gibbonFinanceFeeCategoryID"], "fee"=>$fee["fee"] ); 
												$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count" ;
											}
											$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
											$resultInvoiceFee->execute($dataInvoiceFee);
										}
										catch(PDOException $e) { 
											$invoiceFeeFailCount++ ;
										}
									}
								}
								
								//Update invoice
								try {
									if ($scheduling=="Scheduled") {
										$dataInvoiceAdd=array("gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "notes"=>$rowInvoice["notes"] . " " . $notes, "gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"]); 
										$sqlInvoiceAdd="UPDATE gibbonFinanceInvoice SET gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
									}
									else {
										$dataInvoiceAdd=array("invoiceDueDate"=>dateConvert($guid, $invoiceDueDate), "gibbonPersonIDUpdate"=>$_SESSION[$guid]["gibbonPersonID"], "notes"=>$rowInvoice["notes"] . " " . $notes, "gibbonFinanceInvoiceID"=>$rowInvoice["gibbonFinanceInvoiceID"]); 
										$sqlInvoiceAdd="UPDATE gibbonFinanceInvoice SET invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='" . date("Y-m-d H:i:s") . "' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ;
									}
									$resultInvoiceAdd=$connection2->prepare($sqlInvoiceAdd);
									$resultInvoiceAdd->execute($dataInvoiceAdd);
								}
								catch(PDOException $e) { 
									$invoiceFailCount++ ;
									$thisInvoiceFailed=TRUE ;
								}
							}
							else {
								if ($thisInvoiceFailed==FALSE) {
									$invoiceFailCount++ ;
									$thisInvoiceFailed=TRUE ;
								}
							}
						}
					}
				}
			
				//Unlock module table
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }	
				
				//Return results, include three types of fail and counts
				if ($studentFailCount!=0 OR $invoiceFailCount!=0 OR $invoiceFeeFailCount!=0) {
					//Fail 4
					$URL=$URL . "&addReturn=fail4&studentFailCount=$studentFailCount&invoiceFailCount=$invoiceFailCount&invoiceFeeFailCount=$invoiceFeeFailCount" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL=$URL . "&addReturn=success0" ;
					header("Location: {$URL}");	
				}
			}			
		}
	}
}
?>