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

//Module includes
include "./moduleFunctions.php" ;

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

$gibbonFinanceBudgetCycleID=$_POST["gibbonFinanceBudgetCycleID"] ;
$gibbonFinanceBudgetID=$_POST["gibbonFinanceBudgetID"] ;
$gibbonFinanceExpenseID=$_POST["gibbonFinanceExpenseID"] ;
$status=$_POST["status"] ;
		
if ($gibbonFinanceBudgetCycleID=="" OR $gibbonFinanceBudgetID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenses_manage_edit.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID=$gibbonFinanceBudgetID&status=$status" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_add.php", "Manage Expenses_all")==FALSE) {
		//Fail 0
		$URL.="&editReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL.="&editReturn=fail0" ;
			header("Location: {$URL}");
		}
		else {
			if ($gibbonFinanceExpenseID=="" OR $status=="") {
				//Fail 0
				$URL.="&editReturn=fail0" ;
				header("Location: {$URL}");
			}
			else {
				//Get and check settings
				$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
				$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
				$expenseRequestTemplate=getSettingByScope($connection2, "Finance", "expenseRequestTemplate") ;
				if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
					//Fail 0
					$URL.="&editReturn=fail0" ;
					header("Location: {$URL}");
				}
				else {
					//Check if there are approvers
					try {
						$data=array(); 
						$sql="SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { print $e->getMessage() ; }
		
					if ($result->rowCount()<1) {
						//Fail 0
						$URL.="&editReturn=fail0" ;
						header("Location: {$URL}");
					}
					else {
						//Ready to go! Just check record exists and we have access, and load it ready to use...
						try {
							//Set Up filter wheres
							$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
							$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
								FROM gibbonFinanceExpense 
								JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
								JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
								WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ; 
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail2
							$URL.="&editReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}
	
						if ($result->rowCount()!=1) {
							//Fail 0
							$URL.="&editReturn=fail0" ;
							header("Location: {$URL}");
						}
						else {
							$row=$result->fetch() ;
							$statusOld=$row["status"] ;
							
							//Check if params are specified
							if ($status=="Paid" AND ($row["status"]=="Approved" OR $row["status"]=="Ordered")) {
								$paymentDate=dateConvert($guid, $_POST["paymentDate"]) ;
								$paymentAmount=$_POST["paymentAmount"] ;
								$gibbonPersonIDPayment=$_POST["gibbonPersonIDPayment"] ;
								$paymentMethod=$_POST["paymentMethod"] ;
								$paymentID=$_POST["paymentID"] ;
							}
							else {
									$paymentDate=$row["paymentDate"] ;
									$paymentAmount=$row["paymentAmount"] ;
									$gibbonPersonIDPayment=$row["gibbonPersonIDPayment"] ;
									$paymentMethod=$row["paymentMethod"] ;
									$paymentID=$row["paymentID"] ;
							}
							
							
							//Do Reimbursement work
							$paymentReimbursementStatus=NULL ;
							$reimbursementComment="" ;
							if (isset($_POST["paymentReimbursementStatus"])) {
								$paymentReimbursementStatus=$_POST["paymentReimbursementStatus"] ;
								if ($paymentReimbursementStatus!="Requested" AND $paymentReimbursementStatus!="Complete") {
									$paymentReimbursementStatus=NULL ;
								}
								if ($row["status"]=="Paid" AND $row["purchaseBy"]=="Self" AND $row["paymentReimbursementStatus"]=="Requested" AND $paymentReimbursementStatus=="Complete") {
									$paymentID=$_POST["paymentID"] ;
									$reimbursementComment=$_POST["reimbursementComment"] ;
									$notificationText=sprintf(_('Your reimbursement expense request for "%1$s" in budget "%2$s" has been completed.'), $row["title"], $row["budget"]) ;
									setNotification($connection2, $guid, $row["gibbonPersonIDCreator"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenseRequest_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=" . $row["gibbonFinanceBudgetID"]) ;
									//Write change to log
									try {
										$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "action"=>"Reimbursement Completion", "comment"=>$reimbursementComment); 
										$sql="INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='" . date("Y-m-d H:i:s") . "', action=:action, comment=:comment" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										//Fail2
										$URL.="&editReturn=fail2" ;
										header("Location: {$URL}");
										break ;
									}
								}
							}
			
							//Write back to gibbonFinanceExpense
							try {
								$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "status"=>$status, "paymentDate"=>$paymentDate, "paymentAmount"=>$paymentAmount, "gibbonPersonIDPayment"=>$gibbonPersonIDPayment, "paymentMethod"=>$paymentMethod, "paymentID"=>$paymentID, "paymentReimbursementStatus"=>$paymentReimbursementStatus); 
								$sql="UPDATE gibbonFinanceExpense SET status=:status, paymentDate=:paymentDate, paymentAmount=:paymentAmount, gibbonPersonIDPayment=:gibbonPersonIDPayment, paymentMethod=:paymentMethod, paymentID=:paymentID, paymentReimbursementStatus=:paymentReimbursementStatus WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								//Fail2
								$URL.="&editReturn=fail2" ;
								header("Location: {$URL}");
								break ;
							}
							
							if ($statusOld!=$status) {
								$action="" ;
								if ($status=="Requested") {
									$action="Request" ;
								}
								else if ($status=="Approved") {
									$action="Approval - Exempt" ;
									//Notify original creator that it is approved
									$notificationText=sprintf(_('Your expense request for "%1$s" in budget "%2$s" has been fully approved.'), $row["title"], $row["budget"]) ;
									setNotification($connection2, $guid, $row["gibbonPersonIDCreator"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=" . $row["gibbonFinanceBudgetID"]) ;
								}
								else if ($status=="Rejected") {
									$action="Rejection" ;
									//Notify original creator that it is rejected
									$notificationText=sprintf(_('Your expense request for "%1$s" in budget "%2$s" has been rejected.'), $row["title"], $row["budget"]) ;
									setNotification($connection2, $guid, $row["gibbonPersonIDCreator"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=" . $row["gibbonFinanceBudgetID"]) ;
								}
								else if ($status=="Ordered") {
									$action="Order" ;
								}
								else if ($status=="Paid") {
									$action="Payment" ;
								}
								else if ($status=="Cancelled") {
									$action="Cancellation" ;
								}
								
								//Write change to log
								try {
									$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "action"=>$action); 
									$sql="INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='" . date("Y-m-d H:i:s") . "', action=:action" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									//Fail2
									$URL.="&editReturn=fail2" ;
									header("Location: {$URL}");
									break ;
								}
							}
							
							//Success 0
							$URL.="&editReturn=success0" ;
							header("Location: {$URL}");
								
						}
					}
				}
			}
		}
	}
}
?>