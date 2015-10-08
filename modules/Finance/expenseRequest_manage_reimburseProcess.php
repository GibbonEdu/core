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
$gibbonFinanceBudgetID2=$_POST["gibbonFinanceBudgetID2"] ;
$status2=$_POST["status2"] ;

if ($gibbonFinanceBudgetCycleID=="" OR $gibbonFinanceBudgetID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenseRequest_manage_reimburse.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2" ;
	$URLSuccess=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenseRequest_manage.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage_reimburse.php")==FALSE) {
		//Fail 0
		$URL.="&editReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		if ($gibbonFinanceExpenseID=="" OR $status=="" OR $status!="Paid" OR $_FILES['file']["tmp_name"]=="") {
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
							WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.status='Approved'" ; 
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
						
						//Get relevant 
						$paymentDate=dateConvert($guid, $_POST["paymentDate"]) ;
						$paymentAmount=$_POST["paymentAmount"] ;
						$gibbonPersonIDPayment=$_POST["gibbonPersonIDPayment"] ;
						$paymentMethod=$_POST["paymentMethod"] ;
							
						//Move attached file
						$time=time() ;
						$attachment="" ;
						//Check for folder in uploads based on today's date
						$path=$_SESSION[$guid]["absolutePath"] ;
						if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
							mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
						}
						$unique=FALSE;
						$count=0 ;
						while ($unique==FALSE AND $count<100) {
							$suffix=randomPassword(16) ;
							$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $row["title"]) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
							if (!(file_exists($path . "/" . $attachment))) {
								$unique=TRUE ;
							}
							$count++ ;
						}
		
						if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
							//Fail 5
							$URL.="&editReturn=fail5" ;
							header("Location: {$URL}");
							break ;
						}
						
						//Write back to gibbonFinanceExpense
						try {
							$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "status"=>'Paid', "paymentDate"=>$paymentDate, "paymentAmount"=>$paymentAmount, "gibbonPersonIDPayment"=>$gibbonPersonIDPayment, "paymentMethod"=>$paymentMethod, "paymentReimbursementReceipt"=>$attachment, "paymentReimbursementStatus"=>"Requested"); 
							$sql="UPDATE gibbonFinanceExpense SET status=:status, paymentDate=:paymentDate, paymentAmount=:paymentAmount, gibbonPersonIDPayment=:gibbonPersonIDPayment, paymentMethod=:paymentMethod, paymentReimbursementReceipt=:paymentReimbursementReceipt, paymentReimbursementStatus=:paymentReimbursementStatus WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							//Fail2
							$URL.="&editReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}
						
						//Notify reimbursement officer that action is required
						$reimbursementOfficer=getSettingByScope($connection2, "Finance", "reimbursementOfficer") ;
						if ($reimbursementOfficer!=FALSE AND $reimbursementOfficer!="") {
							$notificationText=sprintf(_('Someone has requested reimbursement for "%1$s" in budget "%2$s".'), $row["title"], $row["budget"]) ;
							setNotification($connection2, $guid, $reimbursementOfficer, $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_edit.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID2=" . $row["gibbonFinanceBudgetID"]) ;
						}
						
						//Write paid change to log
						try {
							$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "action"=>"Payment"); 
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
						
						//Write reimbursement request change to log
						try {
							$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "action"=>"Reimbursement Request"); 
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
						
						//Success 0
						$URLSuccess.="&editReturn=success0" ;
						header("Location: {$URLSuccess}");
							
					}
				}
			}
		}
	}
}
?>