<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Finance\Tables\ExpenseLog;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Finance/expenses_manage_print.php', $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'] ?? '';
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';
        if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            $budgetsAccess = false;
            if ($highestAction == 'Manage Expenses_all') { //Access to everything {
                $budgetsAccess = true;
            } else {
                //Check if have Full or Write in any budgets
                $budgets = getBudgetsByPerson($connection2, $session->get('gibbonPersonID'));
                if (is_array($budgets) && count($budgets)>0) {
                    foreach ($budgets as $budget) {
                        if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                            $budgetsAccess = true;
                        }
                    }
                }
            }

            if ($budgetsAccess == false) {
                $page->addError(__('You do not have Full or Write access to any budgets.'));
            } else {
                //Get and check settings
                $settingGateway = $container->get(SettingGateway::class);
                $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
                $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');
                $expenseRequestTemplate = $settingGateway->getSettingByScope('Finance', 'expenseRequestTemplate');
                if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
                    $page->addError(__('An error has occurred with your expense and budget settings.'));
                } else {
                    //Check if there are approvers
                    try {
                        $data = array();
                        $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                    }

                    if ($result->rowCount() < 1) {
                        $page->addError(__('An error has occurred with your expense and budget settings.'));
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
                                $data['gibbonPersonID'] = $session->get('gibbonPersonID');
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
                        }

                        if ($result->rowCount() != 1) {
                            $page->addError(__('The specified record cannot be found.'));
                        } else {
                            //Let's go!
                            $row = $result->fetch();

                            echo "<div class='linkTop'>";
                            echo "<a href='javascript:window.print()'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                            echo '</div>';
                            ?>
							<table class='smallIntBorder fullWidth' cellspacing='0'>
								<tr class='break'>
									<td colspan=2>
										<h3><?php echo __('Basic Information') ?></h3>
									</td>
								</tr>
								<tr>
									<td style='width: 275px'>
										<b><?php echo __('Budget Cycle') ?> *</b><br/>
									</td>
									<td class="right">
										<?php
                                        $yearName = '';

											$dataYear = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
											$sqlYear = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
											$resultYear = $connection2->prepare($sqlYear);
											$resultYear->execute($dataYear);
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
										<b><?php echo __('Budget') ?> *</b><br/>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['budget']; ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __('Title') ?> *</b><br/>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['title']; ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __('Status') ?> *</b><br/>
									</td>
									<td class="right">
										<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['status']; ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td colspan=2>
										<b><?php echo __('Description') ?></b>
										<?php
                                            echo '<p>';
											echo $row['body'];
											echo '</p>'
                                        ?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __('Purchase By') ?> *</b><br/>
									</td>
									<td class="right">
										<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php echo $row['purchaseBy']; ?>" type="text" class="standardWidth">
									</td>
								</tr>
								<tr>
									<td colspan=2>
										<b><?php echo __('Purchase Details') ?></b>
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
											<h3><?php echo __('Budget Tracking') ?></h3>
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __('Total Cost') ?> *</b><br/>
											<span style="font-size: 90%">
												<i>
												<?php
                                                if ($session->get('currency') != '') {
                                                    echo sprintf(__('Numeric value of the fee in %1$s.'), $session->get('currency'));
                                                } else {
                                                    echo __('Numeric value of the fee.');
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
											<b><?php echo __('Count Against Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="countAgainstBudget" id="countAgainstBudget" maxlength=60 value="<?php echo Format::yesNo($row['countAgainstBudget']); ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<?php
                                    if ($row['countAgainstBudget'] == 'Y') {
                                        ?>
										<tr>
											<td>
												<b><?php echo __('Budget For Cycle') ?> *</b><br/>
												<span style="font-size: 90%">
													<i>
													<?php
                                                    if ($session->get('currency') != '') {
                                                        echo sprintf(__('Numeric value of the fee in %1$s.'), $session->get('currency'));
                                                    } else {
                                                        echo __('Numeric value of the fee.');
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
													$budgetAllocationFail = true;
												}
												if ($resultCheck->rowCount() != 1) {
													echo '<input readonly name="name" id="name" maxlength=60 value="'.__('NA').'" type="text" style="width: 300px">';
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
												<b><?php echo __('Amount already approved or spent') ?> *</b><br/>
												<span style="font-size: 90%">
													<i>
													<?php
                                                    if ($session->get('currency') != '') {
                                                        echo sprintf(__('Numeric value of the fee in %1$s.'), $session->get('currency'));
                                                    } else {
                                                        echo __('Numeric value of the fee.');
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
												<b><?php echo __('Budget Remaining For Cycle') ?> *</b><br/>
												<span style="font-size: 90%">
													<i>
													<?php
                                                    if ($session->get('currency') != '') {
                                                        echo sprintf(__('Numeric value of the fee in %1$s.'), $session->get('currency'));
                                                    } else {
                                                        echo __('Numeric value of the fee.');
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
									<h3><?php echo __('Log') ?></h3>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<?php
									$expenseLog = $container->get(ExpenseLog::class)->create($gibbonFinanceExpenseID, true);
                                    echo $expenseLog->getOutput();
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
