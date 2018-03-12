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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all') == false) {
        echo "<div class='error'>";
        echo __($guid, 'You do not have access to this action.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID']."'>".__($guid, 'Manage Expenses')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Expense').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'];
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
        $status2 = $_GET['status2'];
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
        $gibbonFinanceBudgetID = '';
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
                            $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
									FROM gibbonFinanceExpense
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID";
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
                            $values = $result->fetch();

                            if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Back to Search Results').'</a>';
                                echo '</div>';
							}
							
							$form = Form::create('expenseManage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_editProcess.php');
							$form->setFactory(DatabaseFormFactory::create($pdo));

							$form->addHiddenValue('address', $_SESSION[$guid]['address']);
							$form->addHiddenValue('status2', $status2);
							$form->addHiddenValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
							$form->addHiddenValue('gibbonFinanceBudgetID', $gibbonFinanceBudgetID);
							$form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);

							$form->addRow()->addHeading(__('Basic Information'));
							
							$cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
							$row = $form->addRow();
								$row->addLabel('name', __('Budget Cycle'));
								$row->addTextField('name')->setValue($cycleName)->maxLength(20)->isRequired()->readonly();

							$row = $form->addRow();
								$row->addLabel('budgetName', __('Budget'));
								$row->addTextField('budgetName')->setValue($values['budget'])->isRequired()->readonly();

							$row = $form->addRow();
								$row->addLabel('title', __('Title'));
								$row->addTextField('title')->isRequired()->readonly();

							$row = $form->addRow();
								$row->addLabel('status', __('Status'));
							if ($values['status'] == 'Requested' or $values['status'] == 'Approved' or $values['status'] == 'Ordered') {
								$statuses = array(
									'Ordered' => __('Ordered'),
									'Paid' => __('Paid'),
									'Cancelled' => __('Cancelled'),
								);
								if ($values['status'] == 'Requested') {
									array_unshift($statuses, array(
										'Requested' => __('Requested'),
										'Approved' => __('Approved'),
										'Rejected' => __('Rejected'),
									));
								}
								if ($values['status'] == 'Approved') {
									array_unshift($statuses, array('Approved' => __('Approved')));
								}

								$row->addSelect('status')->fromArray($statuses)->isRequired()->placeholder();
							} else {
								$row->addTextField('status')->isRequired()->readonly();
							}

							$row = $form->addRow();
								$col = $row->addColumn();
								$col->addLabel('body', __('Description'));
								$col->addContent($values['body']);

							$row = $form->addRow();
								$row->addLabel('purchaseBy', __('Purchase By'));
								$row->addTextField('purchaseBy')->isRequired()->readonly();

							$row = $form->addRow();
								$col = $row->addColumn();
								$col->addLabel('purchaseDetails', __('Purchase Details'));
								$col->addContent($values['purchaseDetails']);

							$form->addRow()->addHeading(__('Budget Tracking'));

							$row = $form->addRow();
								$row->addLabel('cost', __('Total Cost'));
								$row->addTextField('costLabel')->isRequired()->readonly()->setValue(number_format($values['cost'], 2, '.', ','));

							$row = $form->addRow();
								$row->addLabel('countAgainstBudget', __('Count Against Budget'))->description(__('For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.'));
								$row->addYesNo('countAgainstBudget')->isRequired();

							$form->addRow()->addHeading(__('Log'));

							$isPaid = $values['status'] == 'Paid';
							if (!$isPaid) {
								$form->toggleVisibilityByClass('paymentInfo')->onSelect('status')->when('Paid');
							}

							$form->addRow()->addHeading(__('Payment Information'))->addClass('paymentInfo');

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentDate', __('Date Paid'))->description(__('Date of payment, not entry to system.'));
								$row->addDate('paymentDate')->isRequired()->setValue(dateConvertBack($guid, $values['paymentDate']))->readonly($isPaid);

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentAmount', __('Amount Paid'))->description(__('Final amount paid.'));
								$row->addCurrency('paymentAmount')->isRequired()->maxLength(15)->readonly($isPaid);

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('gibbonPersonIDPayment', __('Payee'))->description(__('Staff who made, or arranged, the payment.'));
								$row->addSelectStaff('gibbonPersonIDPayment')->isRequired()->placeholder()->readonly($isPaid);

							$methods = array(
								'Bank Transfer' => __('Bank Transfer'),
								'Cash' => __('Cash'),
								'Cheque' => __('Cheque'),
								'Credit Card' => __('Credit Card'),
								'Other' => __('Other')
							);
							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentMethod', __('Payment Method'));
								$row->addSelect('paymentMethod')->fromArray($methods)->placeholder()->isRequired()->readonly($isPaid);

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentID', __('Payment ID'))->description(__('Transaction ID to identify this payment.'));
								$row->addTextField('paymentID')->maxLength(100)->readonly($isPaid);


							$row = $form->addRow();
								$row->addFooter();
								$row->addSubmit();

							$form->loadAllValuesFrom($values);

							echo $form->getOutput();

                            ?>
							<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_editProcess.php' ?>">
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
										</td>
									</tr>
									<tr>
										<td style='width: 275px'>
											<b><?php echo __($guid, 'Budget') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=20 value="<?php echo $values['budget']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Title') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="name" id="name" maxlength=60 value="<?php echo $values['title']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Status') ?> *</b><br/><?php echo $values['status'] ?>
										</td>
										<td class="right">
											<?php
                                            if ($values['status'] == 'Requested' or $values['status'] == 'Approved' or $values['status'] == 'Ordered') {
                                                echo "<select name='status' id='status' class='status' style='width:302px'>";
                                                    echo "<option  value='Please select...'>".__($guid, 'Please select...').'</option>';
                                                    if ($values['status'] == 'Requested') {
                                                        $selected = '';
                                                        if ($values['status'] == 'Requested') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Requested'>".__($guid, 'Requested').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Approved') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Approved'>".__($guid, 'Approved').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Rejected') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Rejected'>".__($guid, 'Rejected').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Ordered') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Ordered'>".__($guid, 'Ordered').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Paid') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Paid'>".__($guid, 'Paid').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Cancelled') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Cancelled'>".__($guid, 'Cancelled').'</option>';
                                                    } elseif ($values['status'] == 'Approved') {
                                                        $selected = '';
                                                        if ($values['status'] == 'Approved') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Approved'>".__($guid, 'Approved').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Ordered') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Ordered'>".__($guid, 'Ordered').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Paid') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Paid'>".__($guid, 'Paid').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Cancelled') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Cancelled'>".__($guid, 'Cancelled').'</option>';
                                                    } elseif ($values['status'] == 'Ordered') {
                                                        $selected = '';
                                                        if ($values['status'] == 'Ordered') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Ordered'>".__($guid, 'Ordered').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Paid') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Paid'>".__($guid, 'Paid').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Cancelled') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Cancelled'>".__($guid, 'Cancelled').'</option>';
                                                    }
                                                echo '</select>';
                                            } else {
                                                ?>
												<input readonly name="status" id="status" maxlength=60 value="<?php echo $values['status'];?>" type="text" class="standardWidth">
												<?php
                                            }
                            				?>
											<script type="text/javascript">
												var statusVar=new LiveValidation('status');
												statusVar.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
											</script>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<b><?php echo __($guid, 'Description') ?></b>
											<?php
                                                echo '<p>';
												echo $values['body'];
												echo '</p>'
                                            ?>
										</td>
									</tr>
									<tr>
										<td>
											<b><?php echo __($guid, 'Purchase By') ?> *</b><br/>
										</td>
										<td class="right">
											<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php echo $values['purchaseBy']; ?>" type="text" class="standardWidth">
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<b><?php echo __($guid, 'Purchase Details') ?></b>
											<?php
                                                echo '<p>';
												echo $values['purchaseDetails'];
												echo '</p>'
                                            ?>
										</td>
									</tr>

									<?php
                                    if ($values['status'] == 'Requested' or $values['status'] == 'Approved' or $values['status'] == 'Ordered' or $values['status'] == 'Paid') {
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
												<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format($values['cost'], 2, '.', ','); ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr>
											<td>
												<b><?php echo __($guid, 'Count Against Budget') ?> *</b><br/>
												<span class="emphasis small">
													<?php echo __($guid, 'For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.'); ?>
												</span>
											</td>
											<td class="right">
												<select name="countAgainstBudget" id="countAgainstBudget" class="standardWidth">
													<?php
                                                    $selected = '';
													if ($values['countAgainstBudget'] == 'Y') {
														$selected = 'selected';
													}
													echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>';
													$selected = '';
													if ($values['countAgainstBudget'] == 'N') {
														$selected = 'selected';
													}
													echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
													?>
												</select>
											</td>
										</tr>

										<?php
                                        if ($values['countAgainstBudget'] == 'Y') {
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
														$dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $values['gibbonFinanceBudgetID']);
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
                                                        $dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $values['gibbonFinanceBudgetID']);
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
        												<input readonly name="name" id="name" maxlength=60 value="<?php echo number_format($budgetAllocated, 2, '.', ',');?>" type="text" class="standardWidth">
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
                                                        if (($budgetAllocation - $budgetAllocated) - $values['cost'] > 0) {
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
                                            echo getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2);
                            				?>
										</td>
									</tr>

									<?php
                                    if ($values['status'] == 'Approved' or $values['status'] == 'Ordered') {
                                        ?>
										<script type="text/javascript">
											$(document).ready(function(){
												$("#paidTitle").css("display","none");
												$("#paymentDateRow").css("display","none");
												$("#paymentAmountRow").css("display","none");
												$("#payeeRow").css("display","none");
												$("#paymentMethodRow").css("display","none");
												$("#paymentIDRow").css("display","none");
												$("#reimbursementRow").css("display","none");
												$("#reimbursementCommentRow").css("display","none");
												paymentDate.disable() ;
												paymentAmount.disable() ;
												gibbonPersonIDPayment.disable() ;
												paymentMethod.disable() ;
												$("#status").change(function(){
													if ($('#status').val()=="Paid" ) {
														$("#paidTitle").slideDown("fast", $("#paidTitle").css("display","table-row"));
														$("#paymentDateRow").slideDown("fast", $("#paymentDateRow").css("display","table-row"));
														$("#paymentAmountRow").slideDown("fast", $("#paymentAmountRow").css("display","table-row"));
														$("#payeeRow").slideDown("fast", $("#payeeRow").css("display","table-row"));
														$("#paymentMethodRow").slideDown("fast", $("#paymentMethodRow").css("display","table-row"));
														$("#paymentIDRow").slideDown("fast", $("#paymentIDRow").css("display","table-row"));
														$("#reimbursementRow").slideDown("fast", $("#reimbursementRow").css("display","table-row"));
														$("#reimbursementCommentRow").slideDown("fast", $("#reimbursementCommentRow").css("display","table-row"));
														paymentDate.enable() ;
														paymentAmount.enable() ;
														gibbonPersonIDPayment.enable() ;
														paymentMethod.enable() ;
													} else {
														$("#paidTitle").css("display","none");
														$("#paymentDateRow").css("display","none");
														$("#paymentAmountRow").css("display","none");
														$("#payeeRow").css("display","none");
														$("#paymentMethodRow").css("display","none");
														$("#paymentIDRow").css("display","none");
														$("#reimbursementRow").css("display","none");
														$("#reimbursementCommentRow").css("display","none");
														paymentDate.disable() ;
														paymentAmount.disable() ;
														gibbonPersonIDPayment.disable() ;
														paymentMethod.disable() ;
													}
												 });
											});
										</script>
										<tr class='break' id="paidTitle">
											<td colspan=2>
												<h3><?php echo __($guid, 'Payment Information') ?></h3>
											</td>
										</tr>
										<tr id="paymentDateRow">
											<td>
												<b><?php echo __($guid, 'Date Paid') ?> *</b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Date of payment, not entry to system.') ?></span>
											</td>
											<td class="right">
												<input name="paymentDate" id="paymentDate" maxlength=10 value="" type="text" class="standardWidth">
												<script type="text/javascript">
													var paymentDate=new LiveValidation('paymentDate');
													paymentDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
														echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
													} else {
														echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
													}
													?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
														echo 'dd/mm/yyyy';
													} else {
														echo $_SESSION[$guid]['i18n']['dateFormat'];
													}
                                       		 		?>." } );
													paymentDate.add(Validate.Presence);
												</script>
												 <script type="text/javascript">
													$(function() {
														$( "#paymentDate" ).datepicker();
													});
												</script>
											</td>
										</tr>
										<tr id="paymentAmountRow">
											<td>
												<b><?php echo __($guid, 'Amount Paid') ?> *</b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Final amount paid.') ?>
												<?php
                                                if ($_SESSION[$guid]['currency'] != '') {
                                                    echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                                                }
                                        		?>
												</span>
											</td>
											<td class="right">
												<input name="paymentAmount" id="paymentAmount" maxlength=15 value="" type="text" class="standardWidth">
												<script type="text/javascript">
													var paymentAmount=new LiveValidation('paymentAmount');
													paymentAmount.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
													paymentAmount.add(Validate.Presence);
												</script>
											</td>
										</tr>
										<tr id="payeeRow">
											<td>
												<b><?php echo __($guid, 'Payee') ?> *</b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Staff who made, or arranged, the payment.') ?></span>
											</td>
											<td class="right">
												<select name="gibbonPersonIDPayment" id="gibbonPersonIDPayment" class="standardWidth">
													<?php
                                                    echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
													try {
														$dataSelect = array();
														$sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
														$resultSelect = $connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													} catch (PDOException $e) {
													}
													while ($rowSelect = $resultSelect->fetch()) {
														echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName(htmlPrep($rowSelect['title']), ($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true).'</option>';
													}
													?>
												</select>
												<script type="text/javascript">
													var gibbonPersonIDPayment=new LiveValidation('gibbonPersonIDPayment');
													gibbonPersonIDPayment.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr id="paymentMethodRow">
											<td>
												<b><?php echo __($guid, 'Payment Method') ?> *</b><br/>
											</td>
											<td class="right">
												<?php
												echo "<select name='paymentMethod' id='paymentMethod' style='width:302px'>";
												echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
												echo "<option value='Bank Transfer'>Bank Transfer</option>";
												echo "<option value='Cash'>Cash</option>";
												echo "<option value='Cheque'>Cheque</option>";
												echo "<option value='Credit Card'>Credit Card</option>";
												echo "<option value='Other'>Other</option>";
												echo '</select>';
												?>
												<script type="text/javascript">
													var paymentMethod=new LiveValidation('paymentMethod');
													paymentMethod.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
												</script>
											</td>
										</tr>
										<tr id="paymentIDRow">
											<td>
												<b><?php echo __($guid, 'Payment ID') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Transaction ID to identify this payment.') ?></span>
											</td>
											<td class="right">
												<input name="paymentID" id="paymentID" maxlength=100 value="" type="text" class="standardWidth">
											</td>
										</tr>
										<?php

                                    } elseif ($values['status'] == 'Paid') {
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
												<input readonly name="paymentDate" id="paymentDate" maxlength=10 value="<?php echo dateConvertBack($guid, $values['paymentDate']) ?>" type="text" class="standardWidth">
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
												<input readonly name="paymentAmount" id="paymentAmount" maxlength=10 value="<?php echo number_format($values['paymentAmount'], 2, '.', ',') ?>" type="text" class="standardWidth">
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
                                                    $dataSelect = array('gibbonPersonID' => $values['gibbonPersonIDPayment']);
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
												<input readonly name="paymentMethod" id="paymentMethod" maxlength=10 value="<?php echo $values['paymentMethod'] ?>" type="text" class="standardWidth">
											</td>
										</tr>
										<tr id="paymentIDRow">
											<td>
												<b><?php echo __($guid, 'Payment ID') ?></b><br/>
												<span class="emphasis small"><?php echo __($guid, 'Transaction ID to identify this payment.') ?></span>
											</td>
											<td class="right">
												<?php
                                                if ($values['paymentReimbursementReceipt'] != '') {
                                                    if (is_file('./'.$values['paymentReimbursementReceipt'])) {
                                                        echo "<a target='_blank' href=\"./".$values['paymentReimbursementReceipt'].'">'.__($guid, 'Payment Receipt').'</a><br/>';
                                                    }
                                                }
												if ($values['paymentID'] == '' and $values['status'] == 'Paid' and $values['purchaseBy'] == 'Self' and $values['paymentReimbursementStatus'] == 'Requested') {
													?>
													<input name="paymentID" id="paymentID" maxlength=100 value="" type="text" class="standardWidth">
													<?php
												} else {
													?>
													<input readonly name="paymentID" id="paymentID" maxlength=100 value="<?php echo $values['paymentID'] ?>" type="text" class="standardWidth">
													<?php
												}
												?>
											</td>
										</tr>
										<?php
                                        if ($values['status'] == 'Paid' and $values['purchaseBy'] == 'Self' and $values['paymentReimbursementStatus'] != '') {
                                            ?>
											<tr id="reimbursementRow">
												<td>
													<b><?php echo __($guid, 'Reimbursement Status') ?></b><br/>
												</td>
												<td class="right">
													<?php
                                                    if ($values['paymentReimbursementStatus'] == 'Requested') {
                                                        echo "<select name='paymentReimbursementStatus' id='paymentReimbursementStatus' style='width:302px'>";
                                                        $selected = '';
                                                        if ($values['status'] == 'Requested') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Requested'>".__($guid, 'Requested').'</option>';
                                                        $selected = '';
                                                        if ($values['status'] == 'Complete') {
                                                            $selected = 'selected';
                                                        }
                                                        echo "<option $selected value='Complete'>".__($guid, 'Complete').'</option>';
                                                        echo '</select>';
                                                    } else {
                                                        ?>
														<input readonly name="paymentReimbursementStatus" id="paymentReimbursementStatus" maxlength=60 value="<?php echo $values['paymentReimbursementStatus'];?>" type="text" class="standardWidth">
														<?php

                                                    }
                                            		?>
												</td>
											</tr>
											<?php
                                            if ($values['paymentReimbursementStatus'] == 'Requested') {
                                                ?>
												<tr id="reimbursementCommentRow">
													<td colspan=2>
														<b><?php echo __($guid, 'Reimbursement Comment') ?></b><br/>
														<textarea name="reimbursementComment" id="reimbursementComment" rows=4 style="width: 100%"></textarea>
													</td>
												</tr>
												<?php

                                            }
                                        }
                                    }
                            		?>

									<tr>
										<td>
											<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
										</td>
										<td class="right">
											<input name="gibbonFinanceExpenseID" id="gibbonFinanceExpenseID" value="<?php echo $gibbonFinanceExpenseID ?>" type="hidden">
											<input name="gibbonFinanceBudgetID" id="gibbonFinanceBudgetID" value="<?php echo $gibbonFinanceBudgetID ?>" type="hidden">
											<input name="status2" id="status2" value="<?php echo $status2 ?>" type="hidden">
											<input name="gibbonFinanceBudgetID2" id="gibbonFinanceBudgetID2" value="<?php echo $gibbonFinanceBudgetID2 ?>" type="hidden">
											<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
											<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
										</td>
									</tr>
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
