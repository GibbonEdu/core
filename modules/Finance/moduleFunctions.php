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

//Checks log to see if approval is complete. Returns false (on error), none (if no completion), budget (if budget completion done or not required), school (if all complete)
function checkLogForApprovalComplete($guid, $gibbonFinanceExpenseID, $connection2) {
	//Lock tables
	$lock=true ;
	try {
		$sqlLock="LOCK TABLE gibbonFinanceExpense WRITE, gibbonFinanceExpenseApprover WRITE, gibbonFinanceExpenseLog WRITE, gibbonFinanceBudget WRITE, gibbonFinanceBudgetPerson WRITE, gibbonSetting WRITE, gibbonNotification WRITE, gibbonPerson READ, gibbonModule READ" ;
		$resultLock=$connection2->query($sqlLock);   
	}
	catch(PDOException $e) { 
		$lock=FALSE ;
		return FALSE ;
	}	
	
	if ($lock) {
		try {
			$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
			$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			return FALSE ;
		}

		if ($result->rowCount()!=1) {
			return FALSE ; 
		}
		else {
			$row=$result->fetch() ;
			
			//Get settings for budget-level and school-level approval
			$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
			$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
	
			if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
				return FALSE ;
			}
			else {
				if ($row["status"]!="Requested") { //Finished? Return
					return FALSE ;
				}
				else { //Not finished
					if ($row["statusApprovalBudgetCleared"]=="N") { //Notify budget holders (e.g. access Full)
						return "none" ;
					}
					else { //School-level approval, what type is it?
						if ($expenseApprovalType=="One Of" OR $expenseApprovalType=="Two Of") { //One Of or Two Of, so alert all approvers
							if ($expenseApprovalType=="One Of") {
								$expected=1 ;
							}
							else {
								$expected=2 ;
							}
							//Do we have correct number of approvals
							try {
								$dataTest=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
								$sqlTest="SELECT DISTINCT * FROM gibbonFinanceExpenseLog WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND action='Approval - Partial - School'" ;
								$resultTest=$connection2->prepare($sqlTest);
								$resultTest->execute($dataTest);
							}
							catch(PDOException $e) { 
								return FALSE ;
							}
							if ($resultTest->rowCount()>=$expected) { //Yes - return "school"
								return "school" ;
							}
							else { //No - return "budget" 	
								return "budget" ;
							}
						}
						else if ($expenseApprovalType=="Chain Of All") { //Chain of all	
							try {
								$dataApprovers=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
								$sqlApprovers="SELECT gibbonPerson.gibbonPersonID AS g1, gibbonFinanceExpenseLog.gibbonPersonID AS g2 FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonFinanceExpenseApprover.gibbonPersonID AND gibbonFinanceExpenseLog.action='Approval - Partial - School' AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName" ; 
								$resultApprovers=$connection2->prepare($sqlApprovers);
								$resultApprovers->execute($dataApprovers);
							}
							catch(PDOException $e) { 
								return FALSE ;
							}
							$approvers=$resultApprovers->fetchAll() ;
							$countTotal=$resultApprovers->rowCount() ;
							$count=0 ;
							foreach ($approvers AS $approver) {
								if ($approver["g1"]==$approver["g2"]) {
									$count++ ;
								}
							}
							
							if ($count>=$countTotal) { //Yes - return "school"
								return "school" ;
							}
							else { //No - return "budget" 	
								return "budget" ;
							}	
						}
						else {
							return FALSE ;
						}
					}
				}
			}
		}
	}
}

//Checks a certain expense request, and returns FALSE on error, TRUE if specified person can approve it.
function approvalRequired($guid, $gibbonPersonID, $gibbonFinanceExpenseID, $gibbonFinanceBudgetCycleID, $connection2, $locking=TRUE) {
	//Lock tables
	$lock=true ;
	if ($locking) {
		try {
			$sqlLock="LOCK TABLE gibbonFinanceExpense WRITE, gibbonFinanceExpenseApprover WRITE, gibbonFinanceExpenseLog WRITE, gibbonFinanceBudget WRITE, gibbonFinanceBudgetPerson WRITE, gibbonSetting WRITE, gibbonNotification WRITE, gibbonPerson READ, gibbonModule READ" ;
			$resultLock=$connection2->query($sqlLock);   
		}
		catch(PDOException $e) { 
			$lock=FALSE ;
			return FALSE ;
		}	
	}
	
	if ($lock) {
		try {
			$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
			$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			return FALSE ;
		}

		if ($result->rowCount()!=1) {
			return FALSE ; 
		}
		else {
			$row=$result->fetch() ;
			
			//Get settings for budget-level and school-level approval
			$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
			$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
						
			if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
				return FALSE ;
			}
			else {
				if ($row["status"]!="Requested") { //Finished? Return
					return FALSE ;
				}
				else { //Not finished
					if ($row["statusApprovalBudgetCleared"]=="N") {
						//Get Full budget people
						try {
							$dataBudget=array("gibbonFinanceBudgetID"=>$row["gibbonFinanceBudgetID"], "gibbonPersonID"=>$gibbonPersonID); 
							$sqlBudget="SELECT gibbonPersonID FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE access='Full' AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID" ;
							$resultBudget=$connection2->prepare($sqlBudget);
							$resultBudget->execute($dataBudget);
						}
						catch(PDOException $e) { 
							return FALSE ;
						}
						
						if ($resultBudget->rowCount()!=1) {
							return FALSE ;
						}
						else {
							return TRUE ;
						}
					}
					else { //School-level approval, what type is it?
						if ($expenseApprovalType=="One Of" OR $expenseApprovalType=="Two Of") { //One Of or Two Of, so alert all approvers
							try {
								$dataApprovers=array("gibbonPersonID"=>$gibbonPersonID); 
								$sqlApprovers="SELECT gibbonPerson.gibbonPersonID FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFinanceExpenseApprover.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ; 
								$resultApprovers=$connection2->prepare($sqlApprovers);
								$resultApprovers->execute($dataApprovers);
							}
							catch(PDOException $e) { 
								return FALSE ;
							}
							
							if ($resultApprovers->rowCount()!=1) {
								return FALSE ;
							}
							else {
								//Check of already approved at school-level
								try {
									$dataApproval=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID, "gibbonPersonID"=>$gibbonPersonID); 
									$sqlApproval="SELECT * FROM gibbonFinanceExpenseLog WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonPersonID=:gibbonPersonID AND action='Approval - Partial - School'" ; 
									$resultApproval=$connection2->prepare($sqlApproval);
									$resultApproval->execute($dataApproval);
								}
								catch(PDOException $e) { 
									return FALSE ;
								}
								if ($resultApproval->rowCount()>0) {
									return FALSE ;
								}
								else {
									return TRUE ;
								}
							}
							
						}
						else if ($expenseApprovalType=="Chain Of All") { //Chain of all	
							//Get notifiers in sequence
							try {
								$dataApprovers=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
								$sqlApprovers="SELECT gibbonPerson.gibbonPersonID AS g1, gibbonFinanceExpenseLog.gibbonPersonID AS g2 FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonFinanceExpenseApprover.gibbonPersonID AND gibbonFinanceExpenseLog.action='Approval - Partial - School' AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName" ; 
								$resultApprovers=$connection2->prepare($sqlApprovers);
								$resultApprovers->execute($dataApprovers);
							}
							catch(PDOException $e) { 
								return FALSE ;
							}
							if ($resultApprovers->rowCount()<1) {
								return FALSE ;
							}
							else {
								$approvers=$resultApprovers->fetchAll() ;
								$gibbonPersonIDNext=NULL ;
								foreach ($approvers AS $approver) {
									if ($approver["g1"]!=$approver["g2"]) {
										if (is_null($gibbonPersonIDNext)) {
											$gibbonPersonIDNext=$approver["g1"] ;
										}
									}
								}
								
								if (is_null($gibbonPersonIDNext)) {
									return FALSE ;
								}
								else {
									if ($gibbonPersonIDNext!=$gibbonPersonID) {
										return FALSE ;
									}
									else {
										return TRUE ;
									}
								}
							}
						}
						else {
							return FALSE ;
						}
					}
				}
			}
		}
	}
}


//Issues correct notificaitons for give expense, depending on circumstances. Returns FALSE on error, TRUE if it did its job.
//Tries to avoid issue duplicate notifications
function setExpenseNotification($guid, $gibbonFinanceExpenseID, $gibbonFinanceBudgetCycleID, $connection2) {
	//Lock tables
	$lock=true ;
	try {
		$sqlLock="LOCK TABLE gibbonFinanceExpense WRITE, gibbonFinanceExpenseApprover WRITE, gibbonFinanceExpenseLog WRITE, gibbonFinanceBudget WRITE, gibbonFinanceBudgetPerson WRITE, gibbonSetting WRITE, gibbonNotification WRITE, gibbonPerson READ, gibbonModule READ" ;
		$resultLock=$connection2->query($sqlLock);   
	}
	catch(PDOException $e) { 
		$lock=FALSE ;
		return FALSE ;
	}	
	
	if ($lock) {
		try {
			$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
			$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			return FALSE ;
		}

		if ($result->rowCount()!=1) {
			return FALSE ; 
		}
		else {
			$row=$result->fetch() ;
			
			//Get settings for budget-level and school-level approval
			$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
			$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
	
			if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
				return FALSE ;
			}
			else {
				if ($row["status"]!="Requested") { //Finished? Return
					return TRUE ;
				}
				else { //Not finished
					$notificationText=sprintf(_('Someone has requested expense approval for "%1$s" in budget "%2$s".'), $row["title"], $row["budget"]) ;
							
					if ($row["statusApprovalBudgetCleared"]=="N") { //Notify budget holders (e.g. access Full)
						//Get Full budget people, and notify them
						try {
							$dataBudget=array("gibbonFinanceBudgetID"=>$row["gibbonFinanceBudgetID"]); 
							$sqlBudget="SELECT gibbonPersonID FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE access='Full' AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
							$resultBudget=$connection2->prepare($sqlBudget);
							$resultBudget->execute($dataBudget);
						}
						catch(PDOException $e) { 
							return FALSE ;
						}
						if ($resultBudget->rowCount()<1) {
							return FALSE ;
						}
						else {
							while ($rowBudget=$resultBudget->fetch()) {
								setNotification($connection2, $guid, $rowBudget["gibbonPersonID"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_approve.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=" . $row["gibbonFinanceBudgetID"]) ;
								return TRUE ;
							}
						}
					}
					else { //School-level approval, what type is it?
						if ($expenseApprovalType=="One Of" OR $expenseApprovalType=="Two Of") { //One Of or Two Of, so alert all approvers
							try {
								$dataApprovers=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
								$sqlApprovers="SELECT gibbonPerson.gibbonPersonID, gibbonFinanceExpenseLog.gibbonFinanceExpenseLogID FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
								$resultApprovers=$connection2->prepare($sqlApprovers);
								$resultApprovers->execute($dataApprovers);
							}
							catch(PDOException $e) { 
								return FALSE ;
							}
							if ($resultApprovers->rowCount()<1) {
								return FALSE ;
							}
							else {
								while ($rowApprovers=$resultApprovers->fetch()) {
									if ($rowApprovers["gibbonFinanceExpenseLogID"]=="") {
										setNotification($connection2, $guid, $rowApprovers["gibbonPersonID"], $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_approve.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=" . $row["gibbonFinanceBudgetID"]) ;
									}
								}
								return TRUE ;
							}
						}
						else if ($expenseApprovalType=="Chain Of All") { //Chain of all	
							//Get notifiers in sequence
							try {
								$dataApprovers=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
								$sqlApprovers="SELECT gibbonPerson.gibbonPersonID AS g1, gibbonFinanceExpenseLog.gibbonPersonID AS g2 FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonFinanceExpenseApprover.gibbonPersonID AND gibbonFinanceExpenseLog.action='Approval - Partial - School' AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName" ; 
								$resultApprovers=$connection2->prepare($sqlApprovers);
								$resultApprovers->execute($dataApprovers);
							}
							catch(PDOException $e) { 
								return FALSE ;
							}
							if ($resultApprovers->rowCount()<1) {
								return FALSE ;
							}
							else {
								$approvers=$resultApprovers->fetchAll() ;
								$gibbonPersonIDNext=NULL ;
								foreach ($approvers AS $approver) {
									if ($approver["g1"]!=$approver["g2"]) {
										if (is_null($gibbonPersonIDNext)) {
											$gibbonPersonIDNext=$approver["g1"] ;
										}
									}
								}
								
								if (is_null($gibbonPersonIDNext)) {
									return FALSE ;
								}
								else {
									setNotification($connection2, $guid, $gibbonPersonIDNext, $notificationText, "Finance", "/index.php?q=/modules/Finance/expenses_manage_approve.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=" . $row["gibbonFinanceBudgetID"]) ;
									return TRUE ;
								}
							}
						}
						else {
							return FALSE ;
						}
					}
				}
			}
		}
	}
}

//Returns log associated with a particular expense
function getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2) {
	try {
		$data=array("gibbonFinanceExpenseID"=>$gibbonFinanceExpenseID); 
		$sql="SELECT gibbonFinanceExpenseLog.*, surname, preferredName FROM gibbonFinanceExpense JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonFinanceExpenseID=gibbonFinanceExpense.gibbonFinanceExpenseID) JOIN gibbonPerson ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID ORDER BY timestamp" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print _("Person") ;
				print "</th>" ;
				print "<th>" ;
					print _("Date") ;
				print "</th>" ;
				print "<th>" ;
					print _("Event") ;
				print "</th>" ;
				print "<th>" ;
					print _("Actions") ;
				print "</th>" ;
			print "</tr>" ;
			
			$rowNum="odd" ;
			$count=0;
			while ($row=$result->fetch()) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Staff", false, true) ;
					print "</td>" ;
					print "<td>" ;
						print dateConvertBack($guid, substr($row["timestamp"],0 , 10)) ;
					print "</td>" ;
					print "<td>" ;
						print $row["action"] ;
					print "</td>" ;
					print "<td>" ;
						print "<script type='text/javascript'>" ;	
							print "$(document).ready(function(){" ;
								print "\$(\".comment-$count\").hide();" ;
								print "\$(\".show_hide-$count\").fadeIn(1000);" ;
								print "\$(\".show_hide-$count\").click(function(){" ;
								print "\$(\".comment-$count\").fadeToggle(1000);" ;
								print "});" ;
							print "});" ;
						print "</script>" ;
						if ($row["comment"]!="") {
							print "<a title='" . _('View Description') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
						}
					print "</td>" ;
				print "</tr>" ;
				if ($row["comment"]!="") {
					print "<tr class='comment-$count' id='comment-$count'>" ;
						print "<td colspan=4>" ;
							if ($row["comment"]!="") {
								print nl2brr($row["comment"]) . "<br/><br/>" ;
							}
						print "</td>" ;
					print "</tr>" ;
				}
			}
		print "</table>" ;
	}
}


//Returns all budgets a person is linked to, as well as their access rights to that budget
function getBudgetsByPerson($connection2, $gibbonPersonID, $gibbonFinanceBudgetID="") {
	$return=FALSE ;
	
	try {
		$data=array("gibbonPersonID"=>$gibbonPersonID);
		if ($gibbonFinanceBudgetID=="") {
			$sql="SELECT * FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY name" ;
		}
		else {
			$data["gibbonFinanceBudgetID"]=$gibbonFinanceBudgetID ;
			$sql="SELECT * FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID ORDER BY name" ;
		}
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { print $e->getMessage() ;}
	
	$count=0 ;
	if ($result->rowCount()>0) {
		$return=array() ;
		while ($row=$result->fetch()) {
			$return[$count][0]=$row["gibbonFinanceBudgetID"] ;
			$return[$count][1]=$row["name"] ;
			$return[$count][2]=$row["access"] ;
			$count++ ;
		}
	}
	
	return $return ;
}


//Take a budget cycle, and return the previous one, or false if none
function getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2) {
	$output=FALSE ;
	
	try {
		$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID); 
		$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { }
	if ($result->rowcount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonFinanceBudgetCycle WHERE sequenceNumber<:sequenceNumber ORDER BY sequenceNumber DESC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonFinanceBudgetCycleID"] ;	
		}
	}
	
	return $output ;
}


//Take a budget cycle, and return the previous one, or false if none
function getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2) {		
	$output=FALSE ;
	
	try {
		$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID); 
		$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	if ($result->rowcount()==1) {
		$row=$result->fetch() ;
		try {
			$dataPrevious=array("sequenceNumber"=>$row["sequenceNumber"]); 
			$sqlPrevious="SELECT * FROM gibbonFinanceBudgetCycle WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC" ;
			$resultPrevious=$connection2->prepare($sqlPrevious);
			$resultPrevious->execute($dataPrevious); 
		}
		catch(PDOException $e) { }
		if ($resultPrevious->rowCount()>=1) {
			$rowPrevious=$resultPrevious->fetch() ;
			$output=$rowPrevious["gibbonFinanceBudgetCycleID"] ;	
		}
	}
	
	return $output ;
}


//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
//Mode can be add, edit
function makeFeeBlock($guid, $connection2, $i, $mode="add", $feeType, $gibbonFinanceFeeID, $name="", $description="", $gibbonFinanceFeeCategoryID="", $fee="", $category="", $outerBlock=TRUE) {	
	if ($outerBlock) {
		print "<div id='blockOuter$i' class='blockOuter'>" ;
	}
	?>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#blockInner<?php print $i ?>").css("display","none");
				$("#block<?php print $i ?>").css("height","72px")
				
				//Block contents control
				$('#show<?php print $i ?>').unbind('click').click(function() {
					if ($("#blockInner<?php print $i ?>").is(":visible")) {
						$("#blockInner<?php print $i ?>").css("display","none");
						$("#block<?php print $i ?>").css("height","72px")
						$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)"); 
					} else {
						$("#blockInner<?php print $i ?>").slideDown("fast", $("#blockInner<?php print $i ?>").css("display","table-row")); 
						$("#block<?php print $i ?>").css("height","auto")
						$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\'"?>)"); 
					}
				});
				
				<?php if ($mode=="add" AND $feeType=="Ad Hoc") { ?>
					var nameClick<?php print $i ?>=false ;
					$('#name<?php print $i ?>').focus(function() {
						if (nameClick<?php print $i ?>==false) {
							$('#name<?php print $i ?>').css("color", "#000") ;
							$('#name<?php print $i ?>').val("") ;
							nameClick<?php print $i ?>=true ;
						}
					});
					
					var feeClick<?php print $i ?>=false ;
					$('#fee<?php print $i ?>').focus(function() {
						if (feeClick<?php print $i ?>==false) {
							$('#fee<?php print $i ?>').css("color", "#000") ;
							$('#fee<?php print $i ?>').val("") ;
							feeClick<?php print $i ?>=true ;
						}
					});
				<?php } ?>
				
				$('#delete<?php print $i ?>').unbind('click').click(function() {
					if (confirm("Are you sure you want to delete this record?")) {
						$('#blockOuter<?php print $i ?>').fadeOut(600, function(){ $('#block<?php print $i ?>').remove(); });
						fee<?php print $i ?>.destroy() ;
					}
				});
			});
		</script>
		<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="block<?php print $i ?>" style='padding: 0px'>
			<table class='blank' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 70%'>
						<input name='order[]' type='hidden' value='<?php print $i ?>'>
						<input <?php if ($feeType=="Standard") { print "readonly" ; }?> maxlength=100 id='name<?php print $i ?>' name='name<?php print $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="add" AND $feeType=="Ad Hoc") { print "color: #999;" ;} ?> margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php if ($mode=="add" AND $feeType=="Ad Hoc") { print _("Fee Name") . " $i" ;} else { print htmlPrep($name) ;} ?>'><br/>
						<?php
						if ($mode=="add" AND $feeType=="Ad Hoc") {
							?>
							<select name="gibbonFinanceFeeCategoryID<?php print $i ?>" id="gibbonFinanceFeeCategoryID<?php print $i ?>" style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px'>
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonFinanceFeeCategoryID"] . "'>" . $rowSelect["name"] . "</option>" ;
								}
								print "<option selected value='1'>" . _('Other') . "</option>" ;
								?>				
							</select>
							<?php 
						}
						else {
							?>
							<input <?php if ($feeType=="Standard") { print "readonly" ; }?> maxlength=100 id='category<?php print $i ?>' name='category<?php print $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php print htmlPrep($category) ?>'>
							<input type='hidden' id='gibbonFinanceFeeCategoryID<?php print $i ?>' name='gibbonFinanceFeeCategoryID<?php print $i ?>' value='<?php print htmlPrep($gibbonFinanceFeeCategoryID) ?>'>
							<?php
						}
						?>
						<input <?php if ($feeType=="Standard") { print "readonly" ; }?> maxlength=13 id='fee<?php print $i ?>' name='fee<?php print $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="add" AND $feeType=="Ad Hoc") { print "color: #999;" ;} ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 95px' value='<?php if ($mode=="add" AND $feeType=="Ad Hoc") { print _("Value") ; if ($_SESSION[$guid]["currency"]!="") { print " (" . $_SESSION[$guid]["currency"] . ")" ;}} else { print htmlPrep($fee) ;} ?>'>
						<script type="text/javascript">
							var fee<?php print $i ?>=new LiveValidation('fee<?php print $i ?>');
							fee<?php print $i ?>.add(Validate.Presence);
							fee<?php print $i ?>.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
						</script>
					</td>
					<td style='text-align: right; width: 30%'>
						<div style='margin-bottom: 5px'>
							<?php
							print "<img id='delete$i' title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/> " ;
							print "<div id='show$i'  title='" . _('Show/Hide') . "' style='margin-top: -1px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\")'></div></br>" ;
							?>
						</div>
						<?php
						if ($mode=="plannerEdit") {
							print "</br>" ;
						}
						?>
						<input type='hidden' name='feeType<?php print $i ?>' value='<?php print $feeType ?>'>
						<input type='hidden' name='gibbonFinanceFeeID<?php print $i ?>' value='<?php print $gibbonFinanceFeeID ?>'>
					</td>
				</tr>
				<tr id="blockInner<?php print $i ?>">
					<td colspan=2 style='vertical-align: top'>
						<?php 
						print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>Description</div>" ;
						if ($gibbonFinanceFeeID==NULL) {
							print "<textarea style='width: 100%;' name='description" . $i . "'>" . htmlPrep($description) . "</textarea>" ;
						}
						else {
							print "<div style='width: 100%;'>" . htmlPrep($description) . "</div>" ;
							print "<input type='hidden' name='description" . $i . "' value='" . htmlPrep($description) . "'>" ;
						}
						?>
					</td>
				</tr>
			</table>
		</div>
	<?php
	if ($outerBlock) {
		print "</div>" ;
	}
}

function invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $currency="", $email=FALSE, $preview=FALSE) {
	$return="" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonSchoolYearID2"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
		$sql="SELECT gibbonPerson.gibbonPersonID, studentID, surname, preferredName, gibbonFinanceInvoice.*, companyContact, companyName, companyAddress, gibbonRollGroup.name AS rollgroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$return=FALSE ;
	}
	
	if ($result->rowCount()==1) {
		//Let's go!
		$row=$result->fetch() ;
		
		if ($email==TRUE) {
			$return.="<div style='width: 100%; text-align: right'>" ;
				$return.="<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "'><img height='100px' width='400px' class='School Logo' alt='Logo' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $_SESSION[$guid]["organisationLogo"] ."'/></a>" ;
			$return.="</div>" ;
		}
		
		//Invoice Text
		$invoiceText=getSettingByScope( $connection2, "Finance", "invoiceText" ) ;
		if ($invoiceText!="") {
			$return.="<p>" ;
				$return.=$invoiceText ;
			$return.="</p>" ;
		}
		
		$style="" ;
		$style2="" ;
		$style3="" ;
		$style4="" ;
		if ($email==TRUE) {
			$style="border-top: 1px solid #333; " ;
			$style2="border-bottom: 1px solid #333; " ;
			$style3="background-color: #f0f0f0; " ;
			$style4="background-color: #f6f6f6; " ;
		}
		//Invoice Details
		$return.="<table cellspacing='0' style='width: 100%'>" ;
			$return.="<tr>" ;
				$return.="<td style='padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style3' colspan=3>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Invoice To') . " (" .$row["invoiceTo"] . ")</span><br/>" ;
					if ($row["invoiceTo"]=="Company") {
						$invoiceTo="" ;
						if ($row["companyContact"]!="") {
							$invoiceTo.=$row["companyContact"] . ", " ;
						}
						if ($row["companyName"]!="") {
							$invoiceTo.=$row["companyName"] . ", " ;
						}
						if ($row["companyAddress"]!="") {
							$invoiceTo.=$row["companyAddress"] . ", " ;
						}
						$return.=substr($invoiceTo,0,-2) ;
					}
					else {
						try {
							$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
							$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
							$resultParents=$connection2->prepare($sqlParents);
							$resultParents->execute($dataParents);
						}
						catch(PDOException $e) { 
							$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultParents->rowCount()<1) {
							$return.="<div class='warning'>" . _('There are no family members available to send this receipt to.') . "</div>" ; 
						}
						else {
							$return.="<ul style='margin-top: 3px; margin-bottom: 3px'>" ;
							while ($rowParents=$resultParents->fetch()) {
								$return.="<li>" ;
								$invoiceTo="" ;
								$invoiceTo.="<b>" . formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) . "</b>, " ;
								if ($rowParents["address1"]!="") {
									$invoiceTo.=$rowParents["address1"] . ", " ;
									if ($rowParents["address1District"]!="") {
										$invoiceTo.=$rowParents["address1District"] . ", " ;
									}
									if ($rowParents["address1Country"]!="") {
										$invoiceTo.=$rowParents["address1Country"] . ", " ;
									}
								}
								else {
									$invoiceTo.=$rowParents["homeAddress"] . ", " ;
									if ($rowParents["homeAddressDistrict"]!="") {
										$invoiceTo.=$rowParents["homeAddressDistrict"] . ", " ;
									}
									if ($rowParents["homeAddressCountry"]!="") {
										$invoiceTo.=$rowParents["homeAddressCountry"] . ", " ;
									}
								}
								$return.=substr($invoiceTo,0,-2) ;
								$return.="</li>" ;
							}
							$return.="</ul>" ;
						}
					}
				$return.="</td>" ;
			$return.="</tr>" ;
			$return.="<tr>" ;
				$return.="<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style4'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Fees For') . "</span><br/>" ;
					$return.=formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) . "<br/><span style='font-style: italic; font-size: 85%'>" . _('Roll Group') . " " . $row["rollgroup"] . "</span><br/>" ;
				$return.="</td>" ;
					$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Status') . "</span><br/>" ;
					$return.=$row["status"] ;
				$return.="</td>" ;
					$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Schedule') . "</span><br/>" ;
					if ($row["billingScheduleType"]=="Ad Hoc") {
						$return.=_("Ad Hoc") ;
					}
					else {
						try {
							$dataSched=array("gibbonFinanceBillingScheduleID"=>$row["gibbonFinanceBillingScheduleID"]); 
							$sqlSched="SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID" ; 
							$resultSched=$connection2->prepare($sqlSched);
							$resultSched->execute($dataSched);
						}
						catch(PDOException $e) { 
							$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSched->rowCount()==1) {
							$rowSched=$resultSched->fetch() ;
							$return.=$rowSched["name"] ;
						}
					}
				$return.="</td>" ;
			$return.="</tr>" ;
			$return.="<tr>" ;
				$return.="<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style2 $style3'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Invoice Issue Date') . "</span><br/>" ;
					$return.=dateConvertBack($guid, $row["invoiceIssueDate"]) ;
				$return.="</td>" ;
				$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Due Date') . "</span><br/>" ;
					$return.=dateConvertBack($guid, $row["invoiceDueDate"]) ;
				$return.="</td>" ;
				$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Invoice Number') . "</span><br/>" ;
					$invoiceNumber=getSettingByScope( $connection2, "Finance", "invoiceNumber" ) ;
					if ($invoiceNumber=="Person ID + Invoice ID") {
						$return.=ltrim($row["gibbonPersonID"],"0") . "-" . ltrim($gibbonFinanceInvoiceID, "0") ;
					}
					else if ($invoiceNumber=="Student ID + Invoice ID") {
						$return.=ltrim($row["studentID"],"0") . "-" . ltrim($gibbonFinanceInvoiceID, "0") ;
					}
					else {
						$return.=ltrim($gibbonFinanceInvoiceID, "0") ;
					}
				$return.="</td>" ;
			$return.="</tr>" ;
		$return.="</table>" ;

		//Fee table
		$return.="<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>" ;
			$return.=_("Fee Table") ;
		$return.="</h3>" ;
		$feeTotal=0 ;
		try {
			if ($preview==TRUE) {
				//Standard
				$dataFees["gibbonFinanceInvoiceID1"]=$row["gibbonFinanceInvoiceID"]; 
				$sqlFees="(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID1 AND feeType='Standard')" ;
				$sqlFees.=" UNION " ;
				//Ad Hoc
				$dataFees["gibbonFinanceInvoiceID2"]=$row["gibbonFinanceInvoiceID"]; 
				$sqlFees.="(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID2 AND feeType='Ad Hoc')" ;
				$sqlFees.=" ORDER BY sequenceNumber" ;
			}
			else {
				$dataFees["gibbonFinanceInvoiceID"]=$row["gibbonFinanceInvoiceID"]; 
				$sqlFees="SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber" ;
			}
			$resultFees=$connection2->prepare($sqlFees);
			$resultFees->execute($dataFees);
		}
		catch(PDOException $e) { 
			$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultFees->rowCount()<1) {
			$return.="<div class='error'>" ;
				$return.=_("There are no records to display") ;
			$return.="</div>" ;
		}
		else {
			$return.="<table cellspacing='0' style='width: 100%; $style4'>" ;
				$return.="<tr class='head'>" ;
					$return.="<th style='text-align: left; padding-left: 10px'>" ;
						$return.=_("Name") ;
					$return.="</th>" ;
					$return.="<th style='text-align: left'>" ;
						$return.=_("Category") ;
					$return.="</th>" ;
					$return.="<th style='text-align: left'>" ;
						$return.=_("Description") ;
					$return.="</th>" ;
					$return.="<th style='text-align: left'>" ;
						$return.=_("Fee") . "<br/>" ;
						if ($currency!="") {
							$return.="<span style='font-style: italic; font-size: 85%'>" . $currency . "</span>" ;
						}
					$return.="</th>" ;
				$return.="</tr>" ;

				$count=0;
				$rowNum="odd" ;
				while ($rowFees=$resultFees->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;

					$return.="<tr style='height: 25px' class=$rowNum>" ;
						$return.="<td style='padding-left: 10px'>" ;
							$return.=$rowFees["name"] ;
						$return.="</td>" ;
						$return.="<td>" ;
							$return.=$rowFees["category"] ;
						$return.="</td>" ;
						$return.="<td>" ;
							$return.=$rowFees["description"] ;
						$return.="</td>" ;
						$return.="<td>" ;
							if (substr($currency,4)!="") {
								$return.=substr($currency,4) . " " ;
							}
							$return.=number_format($rowFees["fee"], 2, ".", ",") ;
							$feeTotal+=$rowFees["fee"] ;
						$return.="</td>" ;
					$return.="</tr>" ;
				}
				$return.="<tr style='height: 35px' class='current'>" ;
					$return.="<td colspan=3 style='text-align: right; $style2'>" ;
						$return.="<b>" . _("Invoice Total:") . "</b>";
					$return.="</td>" ;
					$return.="<td style='$style2'>" ;
						if (substr($currency,4)!="") {
							$return.=substr($currency,4) . " " ;
						}
						$return.="<b>" . number_format($feeTotal, 2, ".", ",") . "</b>" ;
					$return.="</td>" ;
				$return.="</tr>" ;
			}
		$return.="</table>" ;

		//Online payment
		$currency=getSettingByScope($connection2, "System", "currency") ;
		$enablePayments=getSettingByScope($connection2, "System", "enablePayments") ;
		$paypalAPIUsername=getSettingByScope($connection2, "System", "paypalAPIUsername") ;
		$paypalAPIPassword=getSettingByScope($connection2, "System", "paypalAPIPassword") ;
		$paypalAPISignature=getSettingByScope($connection2, "System", "paypalAPISignature") ;
	
		if ($enablePayments=="Y" AND $paypalAPIUsername!="" AND $paypalAPIPassword!="" AND $paypalAPISignature!="" AND $row["status"]!="Paid" AND $row["status"]!="Cancelled" AND $row["status"]!="Refunded") {
			$financeOnlinePaymentEnabled=getSettingByScope($connection2, "Finance", "financeOnlinePaymentEnabled" ) ; 
			$financeOnlinePaymentThreshold=getSettingByScope($connection2, "Finance", "financeOnlinePaymentThreshold" ) ; 
			if ($financeOnlinePaymentEnabled=="Y") {
				$return.="<h3 style='margin-top: 40px'>" ;
					$return.=_("Online Payment") ;
				$return.="</h3>" ;
				$return.="<p>" ;
					if  ($financeOnlinePaymentThreshold=="" OR $financeOnlinePaymentThreshold>=$feeTotal) {
						$return.=sprintf(_('Payment can be made by credit card, using our secure PayPal payment gateway. When you press Pay Now below, you will be directed to a %1$s page from where you can use PayPal in order to make payment. You can continue with payment through %1$s whether you are logged in or not. During this process we do not see or store your credit card details.'), $_SESSION[$guid]["systemName"]) . " " ;
						$return.="<a style='font-weight: bold' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/invoices_payOnline.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=" . $row["key"] . "'>" . _('Pay Now') . ".</a>" ;
					}
					else {
						$return.="<div class='warning'>" . _("Payment is not permitted for this invoice, as the total amount is greater than the permitted online payment threshold.") . "</div>" ;
					}
				$return.="</p>" ;
			}
		}
		
		//Invoice Notes
		$invoiceNotes=getSettingByScope( $connection2, "Finance", "invoiceNotes" ) ;
		if ($invoiceNotes!="") {
			$return.="<h3 style='margin-top: 40px'>" ;
				$return.=_("Notes") ;
			$return.="</h3>" ;
			$return.="<p>" ;
				$return.=$invoiceNotes ;
			$return.="</p>" ;
		}
		
		return $return ;	
	}
}

function receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $currency="", $email=FALSE) {
	$return="" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonSchoolYearID2"=>$gibbonSchoolYearID, "gibbonFinanceInvoiceID"=>$gibbonFinanceInvoiceID); 
		$sql="SELECT gibbonPerson.gibbonPersonID, studentID, surname, preferredName, gibbonFinanceInvoice.*, companyContact, companyName, companyAddress, gibbonRollGroup.name AS rollgroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		$return=FALSE ;
	}
	
	if ($result->rowCount()==1) {
		//Let's go!
		$row=$result->fetch() ;
		
		if ($email==TRUE) {
			$return.="<div style='width: 100%; text-align: right'>" ;
				$return.="<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "'><img height='100px' width='400px' class='School Logo' alt='Logo' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $_SESSION[$guid]["organisationLogo"] ."'/></a>" ;
			$return.="</div>" ;
		}
		
		//Receipt Text
		$receiptText=getSettingByScope( $connection2, "Finance", "receiptText" ) ;
		if ($receiptText!="") {
			$return.="<p>" ;
				$return.=$receiptText ;
			$return.="</p>" ;
		}

		$style="" ;
		$style2="" ;
		$style3="" ;
		$style4="" ;
		if ($email==TRUE) {
			$style="border-top: 1px solid #333; " ;
			$style2="border-bottom: 1px solid #333; " ;
			$style3="background-color: #f0f0f0; " ;
			$style4="background-color: #f6f6f6; " ;
		}
		//Receipt Details
		$return.="<table cellspacing='0' style='width: 100%'>" ;
			$return.="<tr>" ;
				$return.="<td style='padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style3' colspan=3>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Receipt To') . " (" .$row["invoiceTo"] . ")</span><br/>" ;
					if ($row["invoiceTo"]=="Company") {
						$invoiceTo="" ;
						if ($row["companyContact"]!="") {
							$invoiceTo.=$row["companyContact"] . ", " ;
						}
						if ($row["companyName"]!="") {
							$invoiceTo.=$row["companyName"] . ", " ;
						}
						if ($row["companyAddress"]!="") {
							$invoiceTo.=$row["companyAddress"] . ", " ;
						}
						$return.=substr($invoiceTo,0,-2) ;
					}
					else {
						try {
							$dataParents=array("gibbonFinanceInvoiceeID"=>$row["gibbonFinanceInvoiceeID"]); 
							$sqlParents="SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName" ; 
							$resultParents=$connection2->prepare($sqlParents);
							$resultParents->execute($dataParents);
						}
						catch(PDOException $e) { 
							$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultParents->rowCount()<1) {
							$return.="<div class='warning'>" . _('There are no family members available to send this receipt to.') . "</div>" ; 
						}
						else {
							$return.="<ul style='margin-top: 3px; margin-bottom: 3px'>" ;
							while ($rowParents=$resultParents->fetch()) {
								$return.="<li>" ;
								$invoiceTo="" ;
								$invoiceTo.="<b>" . formatName(htmlPrep($rowParents["title"]), htmlPrep($rowParents["preferredName"]), htmlPrep($rowParents["surname"]), "Parent", false) . "</b>, " ;
								if ($rowParents["address1"]!="") {
									$invoiceTo.=$rowParents["address1"] . ", " ;
									if ($rowParents["address1District"]!="") {
										$invoiceTo.=$rowParents["address1District"] . ", " ;
									}
									if ($rowParents["address1Country"]!="") {
										$invoiceTo.=$rowParents["address1Country"] . ", " ;
									}
								}
								else {
									$invoiceTo.=$rowParents["homeAddress"] . ", " ;
									if ($rowParents["homeAddressDistrict"]!="") {
										$invoiceTo.=$rowParents["homeAddressDistrict"] . ", " ;
									}
									if ($rowParents["homeAddressCountry"]!="") {
										$invoiceTo.=$rowParents["homeAddressCountry"] . ", " ;
									}
								}
								$return.=substr($invoiceTo,0,-2) ;
								$return.="</li>" ;
							}
							$return.="</ul>" ;
						}
					}
				$return.="</td>" ;
			$return.="</tr>" ;
			$return.="<tr>" ;
				$return.="<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style4'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Fees For') . "</span><br/>" ;
					$return.=formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) . "<br/><span style='font-style: italic; font-size: 85%'>" . _('Roll Group') . " " . $row["rollgroup"] . "</span><br/>" ;
				$return.="</td>" ;
				$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Status') . "</span><br/>" ;
					$return.=$row["status"] ;
				$return.="</td>" ;
				$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Schedule') . "</span><br/>" ;
					if ($row["billingScheduleType"]=="Ad Hoc") {
						$return.=_("Ad Hoc") ;
					}
					else {
						try {
							$dataSched=array("gibbonFinanceBillingScheduleID"=>$row["gibbonFinanceBillingScheduleID"]); 
							$sqlSched="SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID" ; 
							$resultSched=$connection2->prepare($sqlSched);
							$resultSched->execute($dataSched);
						}
						catch(PDOException $e) { 
							$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSched->rowCount()==1) {
							$rowSched=$resultSched->fetch() ;
							$return.=$rowSched["name"] ;
						}
					}
				$return.="</td>" ;
			$return.="</tr>" ;
			$return.="<tr>" ;
				$return.="<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style2 $style3'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Due Date') . "</span><br/>" ;
					$return.=dateConvertBack($guid, $row["invoiceDueDate"]) ;
				$return.="</td>" ;
				$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Date Paid') . "</span><br/>" ;
					$return.=dateConvertBack($guid, $row["paidDate"]) ;
				$return.="</td>" ;
				$return.="<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>" ;
					$return.="<span style='font-size: 115%; font-weight: bold'>" . _('Invoice Number') . "</span><br/>" ;
					$invoiceNumber=getSettingByScope( $connection2, "Finance", "invoiceNumber" ) ;
					if ($invoiceNumber=="Person ID + Invoice ID") {
						$return.=ltrim($row["gibbonPersonID"],"0") . "-" . ltrim($gibbonFinanceInvoiceID, "0") ;
					}
					else if ($invoiceNumber=="Student ID + Invoice ID") {
						$return.=ltrim($row["studentID"],"0") . "-" . ltrim($gibbonFinanceInvoiceID, "0") ;
					}
					else {
						$return.=ltrim($gibbonFinanceInvoiceID, "0") ;
					}
				$return.="</td>" ;
			$return.="</tr>" ;
		$return.="</table>" ;

		//Fee table
		$return.="<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>" ;
			$return.=_("Fee Table") ;
		$return.="</h3>" ;
		$feeTotal=0 ;
		try {
			$dataFees["gibbonFinanceInvoiceID"]=$row["gibbonFinanceInvoiceID"]; 
			$sqlFees="SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber" ;
			$resultFees=$connection2->prepare($sqlFees);
			$resultFees->execute($dataFees);
		}
		catch(PDOException $e) { 
			$return.="<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($resultFees->rowCount()<1) {
			$return.="<div class='error'>" ;
				$return.=_("There are no records to display") ;
			$return.="</div>" ;
		}
		else {
			$return.="<table cellspacing='0' style='width: 100%; $style4'>" ;
				$return.="<tr class='head'>" ;
					$return.="<th style='text-align: left; padding-left: 10px'>" ;
						$return.=_("Name") ;
					$return.="</th>" ;
					$return.="<th style='text-align: left'>" ;
						$return.=_("Category") ;
					$return.="</th>" ;
					$return.="<th style='text-align: left'>" ;
						$return.=_("Description") ;
					$return.="</th>" ;
					$return.="<th style='text-align: left'>" ;
						$return.=_("Fee") . "<br/>" ;
						if ($currency!="") {
							$return.="<span style='font-style: italic; font-size: 85%'>" . $currency . "</span>" ;
						}
					$return.="</th>" ;
				$return.="</tr>" ;

				$count=0;
				$rowNum="odd" ;
				while ($rowFees=$resultFees->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;

					$return.="<tr style='height: 25px' class=$rowNum>" ;
						$return.="<td style='padding-left: 10px'>" ;
							$return.=$rowFees["name"] ;
						$return.="</td>" ;
						$return.="<td>" ;
							$return.=$rowFees["category"] ;
						$return.="</td>" ;
						$return.="<td>" ;
							$return.=$rowFees["description"] ;
						$return.="</td>" ;
						$return.="<td>" ;
							if (substr($currency,4)!="") {
								$return.=substr($currency,4) . " " ;
							}
							$return.=number_format($rowFees["fee"], 2, ".", ",") ;
							$feeTotal+=$rowFees["fee"] ;
						$return.="</td>" ;
					$return.="</tr>" ;
				}
				$return.="<tr style='height: 35px'>" ;
					$return.="<td colspan=3 style='text-align: right'>" ;
						$return.="<b>" . _('Invoice Total:') . "</b>";
					$return.="</td>" ;
					$return.="<td>" ;
						if (substr($currency,4)!="") {
							$return.=substr($currency,4) . " " ;
						}
						$return.="<b>" . number_format($feeTotal, 2, ".", ",") . "</b>" ;
					$return.="</td>" ;
				$return.="</tr>" ;
				$return.="<tr style='height: 35px' class='current'>" ;
						$return.="<td colspan=3 style='text-align: right; $style2'>" ;
						$return.="<b>" . _('Amount Paid:') . "</b>";
					$return.="</td>" ;
					$return.="<td style='$style2'>" ;
						if (substr($currency,4)!="") {
							$return.=substr($currency,4) . " " ;
						}
						$return.="<b>" . number_format($row["paidAmount"], 2, ".", ",") . "</b>" ;
					$return.="</td>" ;
				$return.="</tr>" ;
			}
		$return.="</table>" ;

		//Invoice Notes
		$receiptNotes=getSettingByScope( $connection2, "Finance", "receiptNotes" ) ;
		if ($receiptNotes!="") {
			$return.="<h3 style='margin-top: 40px'>" ;
				$return.=_("Notes") ;
			$return.="</h3>" ;
			$return.="<p>" ;
				$return.=$receiptNotes ;
			$return.="</p>" ;
		}
		
		return $return ;	
	}
}

?>
