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
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonFinanceBudgetCycleID=$_POST["gibbonFinanceBudgetCycleID"] ;
$gibbonFinanceExpenseID=$_POST["gibbonFinanceExpenseID"] ;
$status2=$_POST["status2"] ;
$gibbonFinanceBudgetID2=$_POST["gibbonFinanceBudgetID2"] ;
		
if ($gibbonFinanceBudgetCycleID=="") {
	print "Fatal error loading this page!" ;
}
else {
	$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/expenseRequest_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2" ;
	
	if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseRequest_manage_view.php")==FALSE) {
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
							$approvers=$result->fetchAll() ;
						
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
								exit() ;
							}
	
							if ($result->rowCount()!=1) {
								//Fail 0
								$URL.="&approveReturn=fail0" ;
								header("Location: {$URL}");
							}
							else {
								$row=$result->fetch() ;
								
								$gibbonFinanceBudgetID=$row["gibbonFinanceBudgetID"] ;
								$comment=$_POST["comment"] ;

								//Write comment to log
								try {
									$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "comment"=>$comment); 
									$sql="INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='" . date("Y-m-d H:i:s") . "', action='Comment', comment=:comment" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									//Fail2
									$URL.="&approveReturn=fail2" ;
									header("Location: {$URL}");
									exit() ;
								}
							
							
								//Notify budget holders
								if ($budgetLevelExpenseApproval=="Y") {
									try {
										$dataHolder=array("gibbonFinanceBudgetID"=>$gibbonFinanceBudgetID); 
										$sqlHolder="SELECT * FROM gibbonFinanceBudgetPerson WHERE access='Full' AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
										$resultHolder=$connection2->prepare($sqlHolder);
										$resultHolder->execute($dataHolder);
									}
									catch(PDOException $e) { }
									while ($rowHolder=$resultHolder->fetch()) {
										$notificationText=sprintf(__($guid, 'Someone has commented on the expense request for "%1$s" in budget "%2$s".'), $row["title"], $row["budget"]) ;
										setNotification($connection2, $guid, $rowHolder["gibbonPersonID"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=" . $row["gibbonFinanceBudgetID"]) ;
									}
								}
							
								//Notify approvers that it is commented upon
								foreach ($approvers AS $approver) {
									$notificationText=sprintf(__($guid, 'Someone has commented on the expense request for "%1$s" in budget "%2$s".'), $row["title"], $row["budget"]) ;
									setNotification($connection2, $guid, $approver["gibbonPersonID"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=" . $row["gibbonFinanceBudgetID"]) ;
								}
							
								//Success 0
								$URL.="&approveReturn=success0" ;
								header("Location: {$URL}");
							}
						}
					}
				}
			}
		}
	}
}
?>