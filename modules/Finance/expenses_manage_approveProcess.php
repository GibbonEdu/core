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
$status2=$_POST["status2"] ;
$gibbonFinanceBudgetID2=$_POST["gibbonFinanceBudgetID2"] ;
		
if ($gibbonFinanceBudgetCycleID=="" OR $gibbonFinanceBudgetID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenses_manage_approve.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2" ;
	$URLApprove=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage_approve.php")==FALSE) {
		//Fail 0
		$URL.="&approveReturn=fail0" ;
		header("Location: {$URL}");
	}
	else {
		$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL.="&approveReturn=fail0" ;
			header("Location: {$URL}");
		}
		else {
			//Check if params are specified
			if ($gibbonFinanceExpenseID=="" OR $gibbonFinanceBudgetCycleID=="") {
				//Fail 0
				$URL.="&approveReturn=fail0" ;
				header("Location: {$URL}");
			}
			else {
				$budgetsAccess=FALSE ;
				if ($highestAction=="Manage Expenses_all") { //Access to everything {
					$budgetsAccess=TRUE ;
				}
				else {
					//Check if have Full or Write in any budgets
					$budgets=getBudgetsByPerson($connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
					foreach ($budgets AS $budget) {
						if ($budget[2]=="Full" OR $budget[2]=="Write") {
							$budgetsAccess=TRUE ;
						}
					}
				}
				
				if ($budgetsAccess==FALSE) {
					//Fail 0
					$URL.="&approveReturn=fail0" ;
					header("Location: {$URL}");
				}
				else {
					//Get and check settings
					$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
					$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
					$expenseRequestTemplate=getSettingByScope($connection2, "Finance", "expenseRequestTemplate") ;
					if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
						//Fail 0
						$URL.="&approveReturn=fail0" ;
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
							$URL.="&approveReturn=fail0" ;
							header("Location: {$URL}");
						}
						else {
							//Ready to go! Just check record exists and we have access, and load it ready to use...
							try {
								//Set Up filter wheres
								$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID, "gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
								//GET THE DATA ACCORDING TO FILTERS
								if ($highestAction=="Manage Expenses_all") { //Access to everything
									$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
										FROM gibbonFinanceExpense 
										JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
										JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
										WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ; 
								}
								else { //Access only to own budgets
									$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
									$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, access
										FROM gibbonFinanceExpense 
										JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
										JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
										JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
										WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID AND access='Full'" ; 
								}
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								//Fail2
								$URL.="&approveReturn=fail2" ;
								header("Location: {$URL}");
								break ;
							}
		
							if ($result->rowCount()!=1) {
								//Fail 0
								$URL.="&approveReturn=fail0" ;
								header("Location: {$URL}");
							}
							else {
								$row=$result->fetch() ;
	
								$approval=$_POST["approval"] ;
								if ($approval=="Approval - Partial") {
									if ($row["statusApprovalBudgetCleared"]=="N") {
										$approval="Approval - Partial - Budget" ;
									}
									else {
										//Check if school approver, if not, abort
										try {
											$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sql="SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFinanceExpenseApprover.gibbonPersonID=:gibbonPersonID" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { }
			
										if ($result->rowCount()==1) { 
											$approval="Approval - Partial - School" ;
										}
										else {
											//Fail 0
											$URL.="&approveReturn=fail0" ;
											header("Location: {$URL}");
											break ;
										}
									}
								}
								$comment=$_POST["comment"] ;
		
								if ($approval=="") {
									//Fail 3
									$URL.="&approveReturn=fail3" ;
									header("Location: {$URL}");
								}
								else {
									if ($approval=="Rejection") { //REJECT!
										//Write back to gibbonFinanceExpense
										try {
											$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
											$sql="UPDATE gibbonFinanceExpense SET status='Rejected' WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											//Fail2
											$URL.="&approveReturn=fail2" ;
											header("Location: {$URL}");
											break ;
										}
										
										//Write rejection to log
										try {
											$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "comment"=>$comment); 
											$sql="INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='" . date("Y-m-d H:i:s") . "', action='Rejection', comment=:comment" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											//Fail2
											$URL.="&approveReturn=fail2" ;
											header("Location: {$URL}");
											break ;
										}
										
										//Notify original creator that it is rejected
										$notificationText=sprintf(_('Your expense request for "%1$s" in budget "%2$s" has been rejected.'), $row["title"], $row["budget"]) ;
										setNotification($connection2, $guid, $row["gibbonPersonIDCreator"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=" . $row["gibbonFinanceBudgetID"]) ;
										
										//Success 0
										$URLApprove.="&approveReturn=success0" ;
										header("Location: {$URLApprove}");
									}
									else { //APPROVE!
										if (approvalRequired($guid, $_SESSION[$guid]["gibbonPersonID"], $row["gibbonFinanceExpenseID"], $gibbonFinanceBudgetCycleID, $connection2, TRUE)==FALSE) {
											//Fail 0
											$URL.="&approveReturn=fail0" ;
											header("Location: {$URL}");
										}
										else {
											//Add log entry
											try {
												$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "action"=>$approval, "comment"=>$comment); 
												$sql="INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='" . date("Y-m-d H:i:s") . "', action=:action, comment=:comment" ;
												$result=$connection2->prepare($sql);
												$result->execute($data);
											}
											catch(PDOException $e) { 
												//Fail2
												$URL.="&approveReturn=fail2" ;
												header("Location: {$URL}");
												break ;
											}
											
											if ($approval="Approval - Partial - Budget") { //If budget-level approval, write that budget passed to expense record 
												try {
													$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
													$sql="UPDATE gibbonFinanceExpense SET statusApprovalBudgetCleared='Y' WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													//Fail2
													$URL.="&approveReturn=fail2" ;
													header("Location: {$URL}");
													break ;
												}
											}
										
											//Check for completion status (returns FALSE, none, budget, school) based on log
											$partialFail=FALSE ;
											$completion=checkLogForApprovalComplete($guid, $gibbonFinanceExpenseID, $connection2) ;
											if ($completion==FALSE) { //If false
												//Fail2
												$URL.="&approveReturn=fail2" ;
												header("Location: {$URL}");
												break ;
											}
											else if ($completion=="none") { //If none
												//Fail2
												$URL.="&approveReturn=fail2" ;
												header("Location: {$URL}");
												break ;
											}
											else if ($completion=="budget") { //If budget completion met
												//Issue Notifications
												if (setExpenseNotification($guid, $gibbonFinanceExpenseID, $gibbonFinanceBudgetCycleID, $connection2)==FALSE) {
													$partialFail=TRUE ;
												}
										
												//Write back to gibbonFinanceExpense
												try {
													$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
													$sql="UPDATE gibbonFinanceExpense SET statusApprovalBudgetCleared='Y' WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													//Fail2
													$URL.="&approveReturn=fail2" ;
													header("Location: {$URL}");
													break ;
												}
										
												if ($partialFail==TRUE) {
													//Success 1
													$URLApprove.="&approveReturn=success1" ;
													header("Location: {$URLApprove}");
												}
												else {
													//Success 0
													$URLApprove.="&approveReturn=success0" ;
													header("Location: {$URLApprove}");
												}
											}
											else if ($completion=="school") { //If school completion met
												//Write completion to log
												try {
													$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
													$sql="INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='" . date("Y-m-d H:i:s") . "', action='Approval - Final'" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													//Fail2
													$URL.="&approveReturn=fail2" ;
													header("Location: {$URL}");
													break ;
												}
												
												//Write back to gibbonFinanceExpense
												try {
													$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
													$sql="UPDATE gibbonFinanceExpense SET status='Approved' WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													//Fail2
													$URL.="&approveReturn=fail2" ;
													header("Location: {$URL}");
													break ;
												}
												
												$notificationExtra="" ;
												//Notify purchasing officer, if a school purchase, and officer set
												$purchasingOfficer=getSettingByScope($connection2, "Finance", "purchasingOfficer") ;
												if ($purchasingOfficer!=FALSE AND $purchasingOfficer!="" AND $row["purchaseBy"]=="School") {
													$notificationText=sprintf(_('A newly approved expense (%1$s) needs to be purchased from budget "%2$s".'), $row["title"], $row["budget"]) ;
													setNotification($connection2, $guid, $purchasingOfficer, $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=" . $row["gibbonFinanceBudgetID"]) ;
													$notificationExtra=". " . _("The Purchasing Officer has been alerted, and will purchase the item on your behalf.") ;
												}
												
												//Notify original creator that it is approved
												$notificationText=sprintf(_('Your expense request for "%1$s" in budget "%2$s" has been fully approved.') . $notificationExtra, $row["title"], $row["budget"]) ;
												setNotification($connection2, $guid, $row["gibbonPersonIDCreator"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=" . $row["gibbonFinanceBudgetID"]) ;
										
												
												//Success 0
												$URLApprove.="&approveReturn=success0" ;
												header("Location: {$URLApprove}");
											}		
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
?>