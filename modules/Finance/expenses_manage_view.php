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

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_view.php') == false) {
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
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID']."'>".__($guid, 'Manage Expenses')."</a> > </div><div class='trailEnd'>".__($guid, 'View Expense').'</div>';
        echo '</div>';

        //Check if params are specified
        $gibbonFinanceExpenseID = isset($_GET['gibbonFinanceExpenseID'])? $_GET['gibbonFinanceExpenseID'] : '';
        $gibbonFinanceBudgetCycleID = isset($_GET['gibbonFinanceBudgetCycleID'])? $_GET['gibbonFinanceBudgetCycleID'] : '';
        $status2 = isset($_GET['status2'])? $_GET['status2'] : '';
        $gibbonFinanceBudgetID2 = isset($_GET['gibbonFinanceBudgetID2'])? $_GET['gibbonFinanceBudgetID2'] : '';
        if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            //Check if have Full or Write in any budgets
            $budgets = getBudgetsByPerson($connection2, $_SESSION[$guid]['gibbonPersonID']);
            $budgetsAccess = false;
            if ($highestAction == 'Manage Expenses_all') { //Access to everything {
                $budgetsAccess = true;
            } else {
                //Check if have Full or Write in any budgets
                $budgets = getBudgetsByPerson($connection2, $_SESSION[$guid]['gibbonPersonID']);
                if (is_array($budgets) && count($budgets)>0) {
                    foreach ($budgets as $budget) {
                        if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                            $budgetsAccess = true;
                        }
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
								<table class='smallIntBorder fullWidth' cellspacing='0'>
									<tr class='break'>
										<td colspan=2>
											<h3><?php echo __($guid, 'Basic Information') ?></h3>
										</td>
									</tr>
									<tr>
										<td style='width: 275px'>
											<b><?php echo __($guid, 'Budget Cycle') ?></b><br/>
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
											<b><?php echo __($guid, 'Budget') ?></b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['budget']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Title') ?></b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['title']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Status') ?></b><br/>
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
											<b><?php echo __($guid, 'Total Cost') ?></b><br/>
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
											<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['cost']; ?>" type="text" class="standardWidth">
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
									<tr>
										<td>
											<b><?php echo __($guid, 'Purchase By') ?></b><br/>
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

									<?php
                                    if ($row['status'] == 'Paid') {
                                        ?>
										<tr class='break' id="paidTitle">
											<td colspan=2>
												<h3><?php echo __($guid, 'Payment Information') ?></h3>
											</td>
										</tr>
										<tr id="paymentDateRow">
											<td>
												<b><?php echo __($guid, 'Date Paid') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Date of payment, not entry to system.') ?></span>
											</td>
											<td class="right">
												<input readonly name="paymentDate" id="paymentDate" maxlength=10 value="<?php echo dateConvertBack($guid, $row['paymentDate']) ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr id="paymentAmountRow">
											<td>
												<b><?php echo __($guid, 'Amount Paid') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Final amount paid.') ?>
												<?php
                                                if ($_SESSION[$guid]['currency'] != '') {
                                                    echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                                                }
                                        		?>
												</span>
											</td>
											<td class="right">
												<input readonly name="paymentAmount" id="paymentAmount" maxlength=10 value="<?php echo number_format($row['paymentAmount'], 2, '.', ',') ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr id="payeeRow">
											<td>
												<b><?php echo __($guid, 'Payee') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Staff who made, or arranged, the payment.') ?></span>
											</td>
											<td class="right">
												<?php
                                                try {
                                                    $dataSelect = array('gibbonPersonID' => $row['gibbonPersonIDPayment']);
                                                    $sqlSelect = 'SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                                                    $resultSelect = $connection2->prepare($sqlSelect);
                                                    $resultSelect->execute($dataSelect);
                                                } catch (PDOException $e) {
                                                }
												if ($resultSelect->rowCount() == 1) {
													$rowSelect = $resultSelect->fetch();
													?>
															<input readonly name="payee" id="payee" maxlength=10 value="<?php echo formatName(htmlPrep($rowSelect['title']), ($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true) ?>" type="text" class="standardWidth">
															<?php

												}
												?>
											</td>
										</tr>
										<tr id="paymentMethodRow">
											<td>
												<b><?php echo __($guid, 'Payment Method') ?></b><br/>
											</td>
											<td class="right">
												<input readonly name="paymentMethod" id="paymentMethod" maxlength=10 value="<?php echo $row['paymentMethod'] ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr id="paymentIDRow">
											<td>
												<b><?php echo __($guid, 'Payment ID') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Transaction ID to identify this payment.') ?></span>
											</td>
											<td class="right">
												<input readonly name="paymentID" id="paymentID" maxlength=100 value="<?php echo $row['paymentID'] ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<?php

                                    }
                            		?>
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
