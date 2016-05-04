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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_approve.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID']."'>".__($guid, 'Manage Expenses')."</a> > </div><div class='trailEnd'>".__($guid, 'Approve/Reject Expense').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'];
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
        $status2 = $_GET['status2'];
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
        if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            $budgetsAccess = false;
            if ($highestAction == 'Manage Expenses_all') { //Access to everything {
                $budgetsAccess = true;
            } else {
                //Check if have Full or Write in any budgets
                $budgets = getBudgetsByPerson($connection2, $_SESSION[$guid]['gibbonPersonID']);
                foreach ($budgets as $budget) {
                    if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                        $budgetsAccess = true;
                    }
                }
            }

            if ($budgetsAccess == false) {
                echo "<div class='error'>";
                echo __($guid, 'You do not have Full or Write access to any budgets.');
                echo '</div>';
            } else {
                //Get and check settings
                $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
                $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
                $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
                if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
                    echo "<div class='error'>";
                    echo __($guid, 'An error has occurred with your expense and budget settings.');
                    echo '</div>';
                } else {
                    //Check if there are approvers
                    try {
                        $data = array();
                        $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }

                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'An error has occurred with your expense and budget settings.');
                        echo '</div>';
                    } else {
                        //Ready to go! Just check record exists and we have access, and load it ready to use...
                        try {
                            //Set Up filter wheres
                            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            //GET THE DATA ACCORDING TO FILTERS
                            if ($highestAction == 'Manage Expenses_all') { //Access to everything
                                $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
                            } else { //Access only to own budgets
                                $data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                                $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, access
									FROM gibbonFinanceExpense 
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
									JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID 
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
                            }
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'The specified record cannot be found.');
                            echo '</div>';
                        } else {
                            //Let's go!
                            $row = $result->fetch();

                            if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Back to Search Results').'</a>';
                                echo '</div>';
                            }
                            ?>
							<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_approveProcess.php' ?>">
								<table class='smallIntBorder fullWidth' cellspacing='0'>	
									<tr class='break'>
										<td colspan=2> 
											<h3><?php echo __($guid, 'Basic Information') ?></h3>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'> 
											<b><?php echo __($guid, 'Budget Cycle') ?> *</b><br/>
										</td>
										<td class="right">
											<?php
                                            $yearName = '';
                            try {
                                $dataYear = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                                $sqlYear = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                                $resultYear = $connection2->prepare($sqlYear);
                                $resultYear->execute($dataYear);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultYear->rowCount() == 1) {
                                $rowYear = $resultYear->fetch();
                                $yearName = $rowYear['name'];
                            }
                            ?>
											<input readonly name="name" id="name" maxlength=20 value="<?php echo $yearName ?>" type="text" class="standardWidth">
											<input name="gibbonFinanceBudgetCycleID" id="gibbonFinanceBudgetCycleID" maxlength=20 value="<?php echo $gibbonFinanceBudgetCycleID ?>" type="hidden" class="standardWidth">
											<script type="text/javascript">
												var gibbonFinanceBudgetCycleID=new LiveValidation('gibbonFinanceBudgetCycleID');
												gibbonFinanceBudgetCycleID.add(Validate.Presence);
											</script>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'> 
											<b><?php echo __($guid, 'Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<?php
                                            if ($highestAction == 'Manage Expenses_all' and $row['statusApprovalBudgetCleared'] == 'Y') { //Can change budgets only if budget level approval is passed (e.g. you are a school approver.
                                                try {
                                                    $dataBudget = array();
                                                    $sqlBudget = "SELECT * FROM gibbonFinanceBudget WHERE active='Y' ORDER BY name";
                                                    $resultBudget = $connection2->prepare($sqlBudget);
                                                    $resultBudget->execute($dataBudget);
                                                } catch (PDOException $e) {
                                                }

                                                echo "<select name='gibbonFinanceBudgetID' id='gibbonFinanceBudgetID' style='width:302px'>";
                                                $selected = '';
                                                if ($gibbonFinanceBudgetID == '') {
                                                    $selected = 'selected';
                                                }
                                                echo "<option $selected value='Please select...'>".__($guid, 'Please select...').'</option>';
                                                while ($rowBudget = $resultBudget->fetch()) {
                                                    $selected = '';
                                                    if ($row['gibbonFinanceBudgetID'] == $rowBudget['gibbonFinanceBudgetID']) {
                                                        $selected = 'selected';
                                                    }
                                                    echo "<option $selected value='".$rowBudget['gibbonFinanceBudgetID']."'>".$rowBudget['name'].'</option>';
                                                }
                                                echo '</select>';
                                                ?>
												<script type="text/javascript">
													var gibbonFinanceBudgetID=new LiveValidation('gibbonFinanceBudgetID');
													gibbonFinanceBudgetID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
												</script>
												<?php

                                            } else { //Cannot change budget
                                                ?>
												<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['budget'];
                                                ?>" type="text" class="standardWidth">
												<input type='hidden' name='gibbonFinanceBudgetID' value='<?php echo $row['gibbonFinanceBudgetID'] ?>'/>
												<?php

                                            }
                            ?>
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Title') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['title']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Status') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['status']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php echo __($guid, 'Description') ?></b>
											<?php 
                                                echo '<p>';
                            echo $row['body'];
                            echo '</p>'
                                            ?>
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Purchase By') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php echo $row['purchaseBy']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<b><?php echo __($guid, 'Purchase Details') ?></b>
											<?php 
                                                echo '<p>';
                            echo $row['purchaseDetails'];
                            echo '</p>'
                                            ?>
										</td>
									</tr>
							
									
									<tr class='break'>
										<td colspan=2> 
											<h3><?php echo __($guid, 'Budget Tracking') ?></h3>
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Total Cost') ?> *</b><br/>
											<span style="font-size: 90%">
												<i>
												<?php
                                                if ($_SESSION[$guid]['currency'] != '') {
                                                    echo sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]['currency']);
                                                } else {
                                                    echo __($guid, 'Numeric value of the fee.');
                                                }
                            ?>
												</i>
											</span>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format($row['cost'], 2, '.', ','); ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Count Against Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="countAgainstBudget" id="countAgainstBudget" maxlength=60 value="<?php echo ynExpander($guid, $row['countAgainstBudget']); ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<?php 
                                    if ($row['countAgainstBudget'] == 'Y') {
                                        ?>
										<tr>
											<td> 
												<b><?php echo __($guid, 'Budget For Cycle') ?> *</b><br/>
												<span style="font-size: 90%">
													<i>
													<?php
                                                    if ($_SESSION[$guid]['currency'] != '') {
                                                        echo sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]['currency']);
                                                    } else {
                                                        echo __($guid, 'Numeric value of the fee.');
                                                    }
                                        			?>
													</i>
												</span>
											</td>
											<td class="right">
												<?php
                                                $budgetAllocation = null;
                                        $budgetAllocationFail = false;
                                        try {
                                            $dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $row['gibbonFinanceBudgetID']);
                                            $sqlCheck = 'SELECT * FROM gibbonFinanceBudgetCycleAllocation WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                                            $resultCheck = $connection2->prepare($sqlCheck);
                                            $resultCheck->execute($dataCheck);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                            $budgetAllocationFail = true;
                                        }
                                        if ($resultCheck->rowCount() != 1) {
                                            echo '<input readonly name="name" id="name" maxlength=60 value="'.__($guid, 'NA').'" type="text" style="width: 300px">';
                                            $budgetAllocationFail = true;
                                        } else {
                                            $rowCheck = $resultCheck->fetch();
                                            $budgetAllocation = $rowCheck['value'];
                                            ?>
													<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format($budgetAllocation, 2, '.', ',');
                                            ?>" type="text" class="standardWidth">
													<?php

                                        }
                                        ?>
											</td>
										</tr>
										<tr>
											<td> 
												<b><?php echo __($guid, 'Amount already approved or spent') ?> *</b><br/>
												<span style="font-size: 90%">
													<i>
													<?php
                                                    if ($_SESSION[$guid]['currency'] != '') {
                                                        echo sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]['currency']);
                                                    } else {
                                                        echo __($guid, 'Numeric value of the fee.');
                                                    }
                                        			?>
													</i>
												</span>
											</td>
											<td class="right">
												<?php
                                                $budgetAllocated = 0;
                                        $budgetAllocatedFail = false;
                                        try {
                                            $dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $row['gibbonFinanceBudgetID']);
                                            $sqlCheck = "(SELECT cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Approved', 'Order'))
													UNION
													(SELECT paymentAmount AS cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Paid'))
													";
                                            $resultCheck = $connection2->prepare($sqlCheck);
                                            $resultCheck->execute($dataCheck);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                            $budgetAllocatedFail = true;
                                        }
                                        if ($budgetAllocatedFail == false) {
                                            while ($rowCheck = $resultCheck->fetch()) {
                                                $budgetAllocated = $budgetAllocated + $rowCheck['cost'];
                                            }
                                            ?>
													<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format($budgetAllocated, 2, '.', ',');
                                            ?>" type="text" class="standardWidth">
													<?php

                                        }

                                        ?>
											</td>
										</tr>
										<?php
                                        if ($budgetAllocationFail == false and $budgetAllocatedFail == false) {
                                            ?>
											<tr>
											<td> 
												<b><?php echo __($guid, 'Budget Remaining For Cycle') ?> *</b><br/>
												<span style="font-size: 90%">
													<i>
													<?php
                                                    if ($_SESSION[$guid]['currency'] != '') {
                                                        echo sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]['currency']);
                                                    } else {
                                                        echo __($guid, 'Numeric value of the fee.');
                                                    }
                                            ?>
													</i>
												</span>
											</td>
											<td class="right">
												<?php
                                                $color = 'red';
                                            if (($budgetAllocation - $budgetAllocated) - $row['cost'] > 0) {
                                                $color = 'green';
                                            }
                                            ?>
												<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format(($budgetAllocation - $budgetAllocated), 2, '.', ',');
                                            ?>" type="text" style="width: 300px; font-weight: bold; color: <?php echo $color ?>">
											</td>
										</tr>
										<?php

                                        }
                                    }
                            ?>
									
									<tr class='break'>
										<td colspan=2> 
											<h3><?php echo __($guid, 'Log') ?></h3>
										</td>
									</tr>
									<tr>
										<td colspan=2> 
											<?php
                                            echo getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2);
                            ?>
										</td>
									</tr>
									
									
									<tr class='break'>
										<td colspan=2> 
											<h3><?php echo __($guid, 'Action') ?></h3>
										</td>
									</tr>
									<?php
                                    $approvalRequired = approvalRequired($guid, $_SESSION[$guid]['gibbonPersonID'], $row['gibbonFinanceExpenseID'], $gibbonFinanceBudgetCycleID, $connection2);
                            if ($approvalRequired != true) { //Approval not required
                                        ?>
										<tr>
											<td colspan=2> 
												<div class='error'><?php echo __($guid, 'Your approval is not currently required: it is possible somone beat you to it, or you have already approved it.') ?></div>
											</td>
										</tr>
										<?php

                            } else {
                                ?>
										<tr>
											<td style='width: 275px'> 
												<b><?php echo __($guid, 'Approval') ?> *</b><br/>
											</td>
											<td class="right">
												<?php
                                                echo "<select name='approval' id='approval' style='width:302px'>";
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                                echo "<option value='Approval - Partial'>".__($guid, 'Approve').'</option>';
                                echo "<option value='Rejection'>".__($guid, 'Reject').'</option>';
                                echo "<option value='Comment'>".__($guid, 'Comment').'</option>';
                                echo '</select>';
                                ?>
												<script type="text/javascript">
													var approval=new LiveValidation('approval');
													approval.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr>
											<td colspan=2> 
												<b><?php echo __($guid, 'Comment') ?></b><br/>
												<textarea name="comment" id="comment" rows=8 style="width: 100%"></textarea>
											</td>
										</tr>
										<tr>
											<td>
												<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');?></span>
											</td>
											<td class="right">
												<input name="gibbonFinanceExpenseID" id="gibbonFinanceExpenseID" value="<?php echo $gibbonFinanceExpenseID ?>" type="hidden">
												<input name="gibbonFinanceBudgetCycleID" id="gibbonFinanceBudgetCycleID" value="<?php echo $gibbonFinanceBudgetCycleID ?>" type="hidden">
												<input name="status2" id="status2" value="<?php echo $status2 ?>" type="hidden">
												<input name="gibbonFinanceBudgetID2" id="gibbonFinanceBudgetID2" value="<?php echo $gibbonFinanceBudgetID2 ?>" type="hidden">
												<input name="status" id="status" value="<?php echo $status ?>" type="hidden">
												<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
												<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
											</td>
										</tr>
										<?php

                            }
                            ?>
								</table>
							</form>
							<?php

                        }
                    }
                }
            }
        }
    }
}
?>