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

$action=$_POST["action"] ;
$countTotal=$_POST["count"] ;
		
if ($action=="" OR $countTotal=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/activities_payment.php" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_payment.php")==FALSE) {
		//Fail 0
		$URL.="&updateReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		$students=array() ;
		$count=0 ;
		for ($i=1; $i<=$countTotal; $i++) {
			if (isset($_POST["gibbonActivityStudentID-$i"])) {
				if ($_POST["gibbonActivityStudentID-$i"]!="") {
					$students[$count]=$_POST["gibbonActivityStudentID-$i"] ;
					$count++ ;
				}
			}
		}
		
		//Proceed!
		//Check if person specified
		if (count($students)<1) {
			//Fail4
			$URL.="&updateReturn=fail4" ;
			header("Location: {$URL}");
		}
		else {
			//LOCK TABLES
			try {
				$data=array(); 
				$sql="LOCK TABLES gibbonFinanceBillingSchedule WRITE, gibbonFinanceInvoicee WRITE, gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonActivity WRITE, gibbonActivityStudent WRITE, gibbonActivity AS gibbonActivity2 WRITE, gibbonActivityStudent AS gibbonActivityStudent2 WRITE, gibbonActivity AS gibbonActivity3 WRITE, gibbonActivityStudent AS gibbonActivityStudent3 WRITE" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) {
				//Fail 2
				$URL.="&updateReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}
			
			$partialFail=FALSE ;
			if ($action=="Generate Invoice - Simulate") {
				foreach ($students AS $gibbonActivityStudentID) {
					//Write generation back to gibbonActivityStudent
					try {
						$data=array("gibbonActivityStudentID"=>$gibbonActivityStudentID); 
						$sql="UPDATE gibbonActivityStudent SET invoiceGenerated='Y' WHERE gibbonActivityStudentID=:gibbonActivityStudentID" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) {
						$partialFail=TRUE ;
					}
				}
			}
			else {
				// Check billing schedule specified exists in the current year
				$checkFail=FALSE ;
				try {
					$dataCheck=array("gibbonFinanceBillingScheduleID"=>$action); 
					$sqlCheck="SELECT gibbonFinanceBillingScheduleID FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID" ;
					$resultCheck=$connection2->prepare($sqlCheck);
					$resultCheck->execute($dataCheck);
				}
				catch(PDOException $e) {
					$checkFail=TRUE ;
					$partialFail=TRUE ;
				}
				
				if ($checkFail==FALSE) {
					foreach ($students AS $gibbonActivityStudentID) {
						//Check student is invoicee
						$checkFail2=FALSE ;
						try {
							$dataCheck2=array("gibbonActivityStudentID"=>$gibbonActivityStudentID); 
							$sqlCheck2="SELECT * FROM gibbonFinanceInvoicee WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonActivityStudent WHERE gibbonActivityStudentID=:gibbonActivityStudentID)" ;
							$resultCheck2=$connection2->prepare($sqlCheck2);
							$resultCheck2->execute($dataCheck2);
						}
						catch(PDOException $e) {
							$checkFail2=TRUE ;
							$partialFail=TRUE ;
						}
				
						if ($checkFail2==FALSE) {
							if ($resultCheck2->rowCount()!=1) {
								$partialFail=TRUE ;
							}
							else {
								$rowCheck2=$resultCheck2->fetch() ;
								
								//Check for existing pending invoice for this student in this billing schedule
								$checkFail3=FALSE ;
								try {
									$dataCheck3=array("gibbonFinanceBillingScheduleID"=>$action, "gibbonFinanceInvoiceeID"=>$rowCheck2["gibbonFinanceInvoiceeID"]); 
									$sqlCheck3="SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND status='Pending'" ;
									$resultCheck3=$connection2->prepare($sqlCheck3);
									$resultCheck3->execute($dataCheck3);
								}
								catch(PDOException $e) {
									$checkFail3=TRUE ;
									$partialFail=TRUE ;
								}
				
								if ($checkFail3==FALSE) {
									if ($resultCheck3->rowCount()==0) { //No invoice, so create it
										//CREATE NEW INVOICE
										//Make and store unique code for confirmation
										$key="" ;
										$continue=FALSE ;
										$count=0 ;
										while ($continue==FALSE AND $count<100) {
											$key=randomPassword(40) ;
											try {
												$dataUnique=array("key"=>$key);  
												$sqlUnique="SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoice.`key`=:key" ; 
												$resultUnique=$connection2->prepare($sqlUnique);
												$resultUnique->execute($dataUnique); 
											}
											catch(PDOException $e) {}
	
											if ($resultUnique->rowCount()==0) {
												$continue=TRUE ;
											}
											$count++ ;
										}

										if ($continue==FALSE) {
											$partialFail=TRUE ;
										}
										else {
											$invoiceFail=FALSE ;
											try {
												$dataInvoice=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonFinanceInvoiceeID"=>$rowCheck2["gibbonFinanceInvoiceeID"], "gibbonFinanceBillingScheduleID"=>$action, "notes"=>'', "key"=>$key, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"]); 
												$sqlInvoice="INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Family', billingScheduleType='Scheduled', gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID, notes=:notes, `key`=:key, status='Pending', separated='N', gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='" . date("Y-m-d H:i:s") . "'" ;
												$resultInvoice=$connection2->prepare($sqlInvoice);
												$resultInvoice->execute($dataInvoice);
											}
											catch(PDOException $e) {
												$invoiceFail=TRUE ;
												$partialFail=TRUE ;
											}
						
											if ($invoiceFail==FALSE) {
												//Get invoice ID
												$gibbonFinanceInvoiceID=str_pad($connection2->lastInsertID(), 14, "0", STR_PAD_LEFT) ;
												
												//Add fees to invoice
												$invoiceFail2=FALSE ;
												try {
													$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "feeType"=>"Ad Hoc", "name"=>"Activity Fee", "gibbonActivityStudentID"=>$gibbonActivityStudentID, "gibbonFinanceFeeCategoryID"=>1, "gibbonActivityStudentID2"=>$gibbonActivityStudentID); 
													$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=(SELECT gibbonActivity.name FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID), gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=(SELECT gibbonActivity2.payment FROM gibbonActivity AS gibbonActivity2 JOIN gibbonActivityStudent AS gibbonActivityStudent2 ON (gibbonActivityStudent2.gibbonActivityID=gibbonActivity2.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID2), sequenceNumber=0" ;
													$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
													$resultInvoiceFee->execute($dataInvoiceFee);
												}
												catch(PDOException $e) {
													$invoiceFai2=TRUE ;
													$partialFail=TRUE ;
												}
												
												if ($invoiceFail2==FALSE) {
													//Write invoice and generation back to gibbonActivityStudent
													try {
														$data=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "gibbonActivityStudentID"=>$gibbonActivityStudentID); 
														$sql="UPDATE gibbonActivityStudent SET invoiceGenerated='Y', gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID WHERE gibbonActivityStudentID=:gibbonActivityStudentID" ;
														$result=$connection2->prepare($sql);
														$result->execute($data);
													}
													catch(PDOException $e) {
														$partialFail=TRUE ;
													}
												}
											}
										}
									}
									else if ($resultCheck3->rowCount()==1) { //Yes invoice, so update it
										$rowCheck3=$resultCheck3->fetch() ;
										
										//Get invoice ID
										$gibbonFinanceInvoiceID=$rowCheck3["gibbonFinanceInvoiceID"] ;
										
										//Add fees to invoice
										$invoiceFail2=FALSE ;
										try {
											$dataInvoiceFee=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "feeType"=>"Ad Hoc", "name"=>"Activity Fee", "gibbonActivityStudentID"=>$gibbonActivityStudentID, "gibbonFinanceFeeCategoryID"=>1, "gibbonActivityStudentID2"=>$gibbonActivityStudentID); 
											$sqlInvoiceFee="INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=(SELECT gibbonActivity3.name FROM gibbonActivity AS gibbonActivity3 JOIN gibbonActivityStudent AS gibbonActivityStudent3 ON (gibbonActivityStudent3.gibbonActivityID=gibbonActivity3.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID), gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=(SELECT gibbonActivity.payment FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID2), sequenceNumber=0" ;
											$resultInvoiceFee=$connection2->prepare($sqlInvoiceFee);
											$resultInvoiceFee->execute($dataInvoiceFee);
										}
										catch(PDOException $e) {
											$invoiceFai2=TRUE ;
											$partialFail=TRUE ;
										}
										
										if ($invoiceFail2==FALSE) {
											//Write invoice and generation back to gibbonActivityStudent
											try {
												$data=array("gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID, "gibbonActivityStudentID"=>$gibbonActivityStudentID); 
												$sql="UPDATE gibbonActivityStudent SET invoiceGenerated='Y', gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID WHERE gibbonActivityStudentID=:gibbonActivityStudentID" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) {
												$partialFail=TRUE ;
											}
										}
									}
									else { //Return error
										$partialFail=TRUE ;
									}
								}
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
			catch(PDOException $e) {}	
			
			if ($partialFail==TRUE) {
				$URL.="&updateReturn=fail5" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&updateReturn=success0" ;
				header("Location: {$URL}");
			}
		}
	}
}
?>