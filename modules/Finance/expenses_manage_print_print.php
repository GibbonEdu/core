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

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Finance/expenses_manage_print.php', $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'];
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
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

                            echo "<div class='linkTop'>";
                            echo "<a href='javascript:window.print()'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                            echo '</div>';
                            ?>
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
										<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['budget'];
                            ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Title') ?> *</b><br/>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['title'];
                            ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'Status') ?> *</b><br/>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['status'];
                            ?>" type="text" class="standardWidth">
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
										<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php echo $row['purchaseBy'];
                            ?>" type="text" class="standardWidth">
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
								
								
								<?php
                                if ($row['status'] == 'Requested' or $row['status'] == 'Approved' or $row['status'] == 'Ordered' or $row['status'] == 'Paid') {
                                    ?>
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
											<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format($row['cost'], 2, '.', ',');
                                    ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Count Against Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="countAgainstBudget" id="countAgainstBudget" maxlength=60 value="<?php echo ynExpander($guid, $row['countAgainstBudget']);
                                    ?>" type="text" class="standardWidth">
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
                                        echo getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2, true);
                            ?>
									</td>
								</tr>
							</table>
							<?php

                        }
                    }
                }
            }
        }
    }
}
?>