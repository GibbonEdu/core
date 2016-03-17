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

@session_start() ;

//Module includes
include "./modules/Finance/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenses_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Expenses') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["approveReturn"])) { $approveReturn=$_GET["approveReturn"] ; } else { $approveReturn="" ; }
		$approveReturnMessage="" ;
		$class="error" ;
		if (!($approveReturn=="")) {
			if ($approveReturn=="success0") {
				$approveReturnMessage=__($guid, "Your request was completed successfully.") ;	
				$class="success" ;
			}
			else if ($approveReturn=="success1") {
				$approveReturnMessage=__($guid, "Your request was completed successfully, but notifications could not be sent out.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $approveReturnMessage;
			print "</div>" ;
		}
	
		print "<p>" ;
			if ($highestAction=="Manage Expenses_all") {
				print __($guid, "This action allows you to manage all expenses for all budgets, regardless of your access rights to individual budgets.") . "<br/>" ;
			}
			else {
				print __($guid, "This action allows you to manage expenses for the budgets in which you have relevant access rights.") . "<br/>" ;
			}
		print "</p>" ;
	
		//Check if have Full, Write or Read access in any budgets
		$budgetsAccess=FALSE ;
		$budgets=getBudgetsByPerson($connection2, $_SESSION[$guid]["gibbonPersonID"]) ;
		$budgetsAll=NULL ;
		if ($highestAction=="Manage Expenses_all") {
			$budgetsAll=getBudgets($connection2) ;
			$budgetsAccess=TRUE ;
		}
		else {
			foreach ($budgets AS $budget) {
				if ($budget[2]=="Full" OR $budget[2]=="Write" OR $budget[2]=="READ") {
					$budgetsAccess=TRUE ;
				}
			}
		}
		
		if ($budgetsAccess==FALSE) {
			print "<div class='error'>" ;
				print __($guid, "You do not have Full or Write access to any budgets.") ;
			print "</div>" ;
		}
		else {
			//Get and check settings
			$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
			$budgetLevelExpenseApproval=getSettingByScope($connection2, "Finance", "budgetLevelExpenseApproval") ;
			if ($expenseApprovalType=="" OR $budgetLevelExpenseApproval=="") {
				print "<div class='error'>" ;
					print __($guid, "An error has occurred with your expense and budget settings.") ;
				print "</div>" ;
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
					print "<div class='error'>" ;
						print __($guid, "An error has occurred with your expense and budget settings.") ;
					print "</div>" ;
				}
				else {
					//Ready to go!
					$gibbonFinanceBudgetCycleID="" ;
					if (isset($_GET["gibbonFinanceBudgetCycleID"])) {
						$gibbonFinanceBudgetCycleID=$_GET["gibbonFinanceBudgetCycleID"] ;
					}
					if ($gibbonFinanceBudgetCycleID=="") {
						try {
							$data=array(); 
							$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE status='Current'" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($result->rowcount()!=1) {
							print "<div class='error'>" ;
								print __($guid, "The Current budget cycle cannot be determined.") ;
							print "</div>" ;
						}
						else {
							$row=$result->fetch() ;
							$gibbonFinanceBudgetCycleID=$row["gibbonFinanceBudgetCycleID"] ;
							$gibbonFinanceBudgetCycleName=$row["name"] ;
						}
					}
					if ($gibbonFinanceBudgetCycleID!="") {
						try {
							$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID); 
							$sql="SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($result->rowcount()!=1) {
							print "<div class='error'>" ;
								print __($guid, "The specified budget cycle cannot be determined.") ;
							print "</div>" ;
						}
						else {
							$row=$result->fetch() ;
							$gibbonFinanceBudgetCycleName=$row["name"] ;
						}
					
						print "<h2>" ;
							print $gibbonFinanceBudgetCycleName ;
						print "</h2>" ;
		
						print "<div class='linkTop'>" ;
							//Print year picker
							$previousCycle=getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2) ;
							if ($previousCycle!=FALSE) {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage.php&gibbonFinanceBudgetCycleID=" . $previousCycle . "'>" . __($guid, 'Previous Cycle') . "</a> " ;
							}
							else {
								print __($guid, "Previous Cycle") . " " ;
							}
							print " | " ;
							$nextCycle=getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2) ;
							if ($nextCycle!=FALSE) {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage.php&gibbonFinanceBudgetCycleID=" . $nextCycle . "'>" . __($guid, 'Next Cycle') . "</a> " ;
							}
							else {
								print __($guid, "Next Cycle") . " " ;
							}
						print "</div>" ;
	
						$status2=NULL ;
						if (isset($_GET["status2"])) {
							$status2=$_GET["status2"] ;
						}
						$gibbonFinanceBudgetID2=NULL ;
						if (isset($_GET["gibbonFinanceBudgetID2"])) {
							$gibbonFinanceBudgetID2=$_GET["gibbonFinanceBudgetID2"] ;
						}
					
						print "<h3>" ;
							print __($guid, "Filters") ;
						print "</h3>" ;
						print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php'>" ;
							print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
								?>
								<tr>
									<td> 
										<b><?php print __($guid, 'Status') ?></b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<?php
										print "<select name='status2' id='status2' style='width:302px'>" ;
											$selected="" ;
											if ($status2=="") {
												$selected="selected" ;
											}
											print "<option $selected value=''>" . __($guid, 'All') . "</option>" ;
											$selected="" ;
											if ($status2=="Requested") {
												$selected="selected" ;
											}
											print "<option $selected value='Requested'>" . __($guid, 'Requested') . "</option>" ;
											$selected="" ;
											if ($status2=="Requested - Approval Required") {
												$selected="selected" ;
											}
											print "<option $selected value='Requested - Approval Required'>" . __($guid, 'Requested - Approval Required') . "</option>" ;
											$selected="" ;
											if ($status2=="Approved") {
												$selected="selected" ;
											}
											print "<option $selected value='Approved'>" . __($guid, 'Approved') . "</option>" ;
											$selected="" ;
											if ($status2=="Rejected") {
												$selected="selected" ;
											}
											print "<option $selected value='Rejected'>" . __($guid, 'Rejected') . "</option>" ;
											$selected="" ;
											if ($status2=="Cancelled") {
												$selected="selected" ;
											}
											print "<option $selected value='Cancelled'>" . __($guid, 'Cancelled') . "</option>" ;
											$selected="" ;
											if ($status2=="Ordered") {
												$selected="selected" ;
											}
											print "<option $selected value='Ordered'>" . __($guid, 'Ordered') . "</option>" ;
											$selected="" ;
											if ($status2=="Paid") {
												$selected="selected" ;
											}
											print "<option $selected value='Paid'>" . __($guid, 'Paid') . "</option>" ;
										print "</select>" ;
										?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print __($guid, 'Budget') ?></b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<?php
										print "<select name='gibbonFinanceBudgetID2' id='gibbonFinanceBudgetID2' style='width:302px'>" ;
											$selected="" ;
											if ($gibbonFinanceBudgetID2=="") {
												$selected="selected" ;
											}
											print "<option $selected value=''>" . __($guid, 'All') . "</option>" ;
											if ($budgetsAll==NULL) {
												$budgetsAll=$budgets ;
											}
											foreach ($budgetsAll AS $budget) {
												$selected="" ;
												if ($gibbonFinanceBudgetID2==$budget[0]) {
													$selected="selected" ;
												}
												print "<option $selected value='" . $budget[0] . "'>" . $budget[1] . "</option>" ;
											}
										print "</select>" ;
										?>
									</td>
								</tr>
								<?php
				
								print "<tr>" ;
									print "<td class='right' colspan=2>" ;
										print "<input type='hidden' name='gibbonFinanceBudgetCycleID' value='$gibbonFinanceBudgetCycleID'>" ;
										print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID'>" . __($guid, 'Clear Filters') . "</a> " ;
										print "<input type='submit' value='" . __($guid, 'Go') . "'>" ;
									print "</td>" ;
								print "</tr>" ;
							print "</table>" ;
						print "</form>" ;
		
						try {
							//Set Up filter wheres
							$data=array("gibbonFinanceBudgetCycleID"=>$gibbonFinanceBudgetCycleID); 
							$whereBudget="" ;
							if ($gibbonFinanceBudgetID2!="") {
								$data["gibbonFinanceBudgetID"]=$gibbonFinanceBudgetID2 ;
								$whereBudget.=" AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
							}
							$approvalRequiredFilter=FALSE ;
							$whereStatus="" ;
							if ($status2!="") {
								if ($status2=="Requested - Approval Required") {
									$data["status"]='Requested' ;
									$approvalRequiredFilter=TRUE ;
								}
								else {
									$data["status"]=$status2 ;
								}
								$whereStatus.=" AND gibbonFinanceExpense.status=:status" ;
							}
							//GET THE DATA ACCORDING TO FILTERS
							if ($highestAction=="Manage Expenses_all") { //Access to everything
								$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID $whereBudget $whereStatus 
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC" ; 
							}
							else { //Access only to own budgets
								$data["gibbonPersonID"]=$_SESSION[$guid]["gibbonPersonID"] ;
								$sql="SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, access
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID $whereBudget $whereStatus 
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC" ; 
							}
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						print "<h3>" ;
						print __($guid, "View") ;
						print "</h3>" ;
		
						$allowExpenseAdd=getSettingByScope($connection2, "Finance", "allowExpenseAdd") ;
						if ($highestAction=="Manage Expenses_all" AND $allowExpenseAdd=="Y") { //Access to everything
							print "<div class='linkTop' style='text-align: right'>" ;
								print "<a style='margin-right: 3px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a><br/>" ;
							print "</div>" ;
						}
						
						
						print "<form onsubmit='return confirm(\"" .__($guid, 'Are you sure you wish to process this action? It cannot be undone.') . "\")' method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_processBulk.php?gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>" ;
							print "<fieldset style='border: none'>" ;
								print "<div class='linkTop' style='text-align: right; margin-bottom: 40px'>" ;
									?>
									<input style='margin-top: 0px; float: right' type='submit' value='<?php print __($guid, 'Go') ?>'>
									<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
										<option value="Select action"><?php print __($guid, 'Select action') ?></option>
										<option value="export"><?php print __($guid, 'Export') ?></option>
									</select>
									<script type="text/javascript">
										var action=new LiveValidation('action');
										action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
									</script>
									<?php
								print "</div>" ;	
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th style='width: 110px'>" ;
											print __($guid, "Title") . "<br/>" ;
											print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Budget') . "</span>" ;
										print "</th>" ;
										print "<th style='width: 110px'>" ;
											print __($guid, "Staff") ;
										print "</th>" ;
										print "<th style='width: 100px'>" ;
											print __($guid, "Status") ;
										print "</th>" ;
										print "<th style='width: 90px'>" ;
											print __($guid, "Cost") . "<br/><span style='font-style: italic; font-size: 75%'>(" . $_SESSION[$guid]["currency"] . ")</span><br/>" ;
										print "</th>" ;
										print "<th style='width: 120px'>" ;
											print __($guid, "Date") ;
										print "</th>" ;
										print "<th style='width: 140px'>" ;
											print __($guid, "Actions") ;
										print "</th>" ;
										print "<th>" ;
											?>
											<script type="text/javascript">
												$(function () {
													$('.checkall').click(function () {
														$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
													});
												});
											</script>
											<?php
											print "<input type='checkbox' class='checkall'>" ;
										print "</th>" ;
									print "</tr>" ;

									$count=0;
									$rowNum="odd" ;
									while ($row=$result->fetch()) {
										$approvalRequired=approvalRequired($guid, $_SESSION[$guid]["gibbonPersonID"], $row["gibbonFinanceExpenseID"], $gibbonFinanceBudgetCycleID, $connection2, FALSE) ;
										if ($approvalRequiredFilter==FALSE OR ($approvalRequiredFilter AND $approvalRequired)) {
											if ($count%2==0) {
												$rowNum="even" ;
											}
											else {
												$rowNum="odd" ;
											}
											$count++ ;
	
											//Color row by status
											if ($row["status"]=="Approved") {
												$rowNum="current" ;	
											}
											if ($row["status"]=="Rejected" OR $row["status"]=="Cancelled") {
												$rowNum="error" ;	
											}
	
											print "<tr class=$rowNum>" ;
												print "<td>" ;
													print "<b>" . $row["title"] . "</b><br/>" ;
													print "<span style='font-size: 85%; font-style: italic'>" . $row["budget"] . "</span>" ;
												print "</td>" ;
												print "<td>" ;
													print formatName("", $row["preferredName"], $row["surname"], "Staff", false, true) ;
												print "</td>" ;
												print "<td>" ;
													print $row["status"] ;
												print "</td>" ;
												print "<td>" ;
													print number_format($row["cost"] , 2, ".", ",") ;
												print "</td>" ;
												print "<td>" ;
													print dateConvertBack($guid, substr($row["timestampCreator"], 0, 10)) ;
												print "</td>" ;
												print "<td>" ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_view.php&gibbonFinanceExpenseID=" . $row["gibbonFinanceExpenseID"] . "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='" . __($guid, 'View') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_print.php&gibbonFinanceExpenseID=" . $row["gibbonFinanceExpenseID"] . "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
													if ($row["status"]=="Requested" OR $row["status"]=="Approved" OR $row["status"]=="Ordered") {
														print "<a style='margin-left: 4px' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_edit.php&gibbonFinanceExpenseID=" . $row["gibbonFinanceExpenseID"] . "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
													}
													if ($row["status"]=="Requested") {
														if ($approvalRequired==TRUE) {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/expenses_manage_approve.php&gibbonFinanceExpenseID=" . $row["gibbonFinanceExpenseID"] . "&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='" . __($guid, 'Approve/Reject') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a> " ;
														}
													}
												print "</td>" ;
												print "<td>" ;
													print "<input style='margin-left: -6px' type='checkbox' name='gibbonFinanceExpenseIDs[]' value='" . $row["gibbonFinanceExpenseID"] . "'>" ;
												print "</td>" ;
											print "</tr>" ;
										}
									}
									if ($count<1) {
										print "<tr>" ;
											print "<td colspan=7>" ;
												print "<div class='error'>" ;
												print __($guid, "There are no records to display.") ;
												print "</div>" ;
											print "</td>" ;
										print "</tr>" ;
									}
									print "<input type=\"hidden\" name=\"address\" value=\"" . $_SESSION[$guid]["address"] . "\">" ;
					
								print "</fieldset>" ;
							print "</table>" ;
						print "</form>" ;
					}
				}
			}
		}
	}
}
?>