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
        $gibbonFinanceExpenseID = isset($_GET['gibbonFinanceExpenseID'])? $_GET['gibbonFinanceExpenseID'] : '';
        $gibbonFinanceBudgetCycleID = isset($_GET['gibbonFinanceBudgetCycleID'])? $_GET['gibbonFinanceBudgetCycleID'] : '';
        $status2 = isset($_GET['status2'])? $_GET['status2'] : '';
        $gibbonFinanceBudgetID2 = isset($_GET['gibbonFinanceBudgetID2'])? $_GET['gibbonFinanceBudgetID2'] : '';
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
                            
                            // Budget allocation
                            $dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $values['gibbonFinanceBudgetID']);
                            $sqlCheck = "SELECT value FROM gibbonFinanceBudgetCycleAllocation WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID";
                            $resultCheck = $pdo->executeQuery($dataCheck, $sqlCheck);
                            $budgetAllocationFail = $resultCheck->rowCount() != 1;
                            $budgetAllocation = !$budgetAllocationFail? $resultCheck->fetchColumn(0) : __('N/A');

                            // Budget already allocated
                            $dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $values['gibbonFinanceBudgetID']);
                            $sqlCheck = "(SELECT cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Approved', 'Order'))
                                UNION
                                (SELECT paymentAmount AS cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Paid'))";
                            
                            $resultCheck = $pdo->executeQuery($dataCheck, $sqlCheck);
                            $budgetAllocatedFail = $resultCheck->rowCount() == 0;
                            $budgetAllocated = 0;
                            if (!$budgetAllocatedFail) {
                                $budgetAllocated = array_reduce($resultCheck->fetchAll(), function($sum, $item) {
                                    $sum += $item['cost'];
                                    return $sum;
                                }, 0);
                            }

                            // Budget remaining
                            $budgetRemaining = (!$budgetAllocatedFail && !$budgetAllocationFail)? ($budgetAllocation - $budgetAllocated) : __('N/A');
                            
                            
							$form = Form::create('expenseManage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_editProcess.php');
							$form->setFactory(DatabaseFormFactory::create($pdo));

							$form->addHiddenValue('address', $_SESSION[$guid]['address']);
							$form->addHiddenValue('status2', $status2);
							$form->addHiddenValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
							$form->addHiddenValue('gibbonFinanceBudgetID', $gibbonFinanceBudgetID);
							$form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);
							$form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

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
									$statuses = array(
										'Requested' => __('Requested'),
										'Approved' => __('Approved'),
										'Rejected' => __('Rejected'),
									) + $statuses;
								}
								if ($values['status'] == 'Approved') {
									$statuses = array('Approved' => __('Approved')) + $statuses;
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
                                $row->addTextField('cost')->isRequired()->readonly()->setValue(number_format($values['cost'], 2, '.', ','));

							$row = $form->addRow();
								$row->addLabel('countAgainstBudget', __('Count Against Budget'))->description(__('For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.'));
                                $row->addYesNo('countAgainstBudget')->isRequired();
                                
                            $form->toggleVisibilityByClass('budgetInfo')->onSelect('countAgainstBudget')->when('Y');

                            $budgetAllocationLabel = (is_numeric($budgetAllocation))? number_format($budgetAllocation, 2, '.', ',') : $budgetAllocation;
                            $row = $form->addRow()->addClass('budgetInfo');
								$row->addLabel('budgetAllocation', __('Budget For Cycle'))->description(__('Numeric value of the fee.'));
                                $row->addTextField('budgetAllocation')->isRequired()->readonly()->setValue($budgetAllocationLabel);
                              
                            $budgetAllocatedLabel = (is_numeric($budgetAllocated))? number_format($budgetAllocated, 2, '.', ',') : $budgetAllocated;
							$row = $form->addRow()->addClass('budgetInfo');
								$row->addLabel('budgetForCycle', __('Amount already approved or spent'))->description(__('Numeric value of the fee.'));
                                $row->addTextField('budgetForCycle')->isRequired()->readonly()->setValue($budgetAllocatedLabel);
                                
                            $budgetRemainingLabel = (is_numeric($budgetRemaining))? number_format($budgetRemaining, 2, '.', ',') : $budgetRemaining;
							$row = $form->addRow()->addClass('budgetInfo');
								$row->addLabel('budgetRemaining', __('Budget Remaining For Cycle'))->description(__('Numeric value of the fee.'));
								$row->addTextField('budgetRemaining')->isRequired()->readonly()->setValue($budgetRemainingLabel);

                            $form->addRow()->addHeading(__('Log'));
                            
                            $form->addRow()->addContent(getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2));

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
                                if ($isPaid) {
                                    $data = array('gibbonPersonID' => $values['gibbonPersonIDPayment']);
                                    $sql = "SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                                    $result = $pdo->executeQuery($data, $sql);
                                    $payee = $result->rowCount() == 1? $result->fetch() : null;
                                    $payeeName = !empty($payee)? formatName($payee['title'], $payee['preferredName'], $payee['surname'], 'Staff', true, true) : '';
                                    $row->addTextField('payee')->isRequired()->readonly()->setValue($payeeName);
                                    $form->addHiddenValue('gibbonPersonIDPayment', $values['gibbonPersonIDPayment']);
                                } else {
                                    $row->addSelectStaff('gibbonPersonIDPayment')->isRequired()->placeholder();
                                }
								

							$methods = array(
								'Bank Transfer' => __('Bank Transfer'),
								'Cash' => __('Cash'),
								'Cheque' => __('Cheque'),
								'Credit Card' => __('Credit Card'),
								'Other' => __('Other')
							);
							$row = $form->addRow()->addClass('paymentInfo');
                                $row->addLabel('paymentMethod', __('Payment Method'));
                                if ($isPaid) {
                                    $row->addTextField('paymentMethod')->isRequired()->readonly()->setValue($values['paymentMethod']);
                                } else {
                                    $row->addSelect('paymentMethod')->fromArray($methods)->placeholder()->isRequired()->readonly($isPaid);
                                }

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentID', __('Payment ID'))->description(__('Transaction ID to identify this payment.'));
                                $paymentID = $row->addTextField('paymentID')->maxLength(100)->readonly($isPaid && $values['paymentReimbursementStatus'] != 'Requested');

                            if ($values['paymentReimbursementReceipt'] != '') {
                                $paymentID->prepend("<a target='_blank' class='floatRight' href=\"./".$values['paymentReimbursementReceipt'].'">'.__('Payment Receipt').'</a><br/>');
                            }
                                
                            if ($values['status'] == 'Paid' and $values['purchaseBy'] == 'Self' and $values['paymentReimbursementStatus'] != '') {

                                $row = $form->addRow()->addClass('paymentInfo');
                                $row->addLabel('paymentReimbursementStatus', __('Reimbursement Status'));

                                if ($values['paymentReimbursementStatus'] == 'Complete') {
                                    $row->addTextField('paymentReimbursementStatus')->readonly()->setValue($values['paymentReimbursementStatus']);
                                } else {
                                    $statuses = array('Requested' => __('Requested'),'Complete' => __('Complete'));
                                    $row->addSelect('paymentReimbursementStatus')->fromArray($statuses)->selected($values['paymentReimbursementStatus']);

                                    $col = $form->addRow()->addColumn();
                                        $col->addLabel('reimbursementComment', __('Reimbursement Comment'));
                                        $col->addTextArea('reimbursementComment')->setRows(4)->setClass('fullWidth');
                                }
                            }

							$row = $form->addRow();
								$row->addFooter();
								$row->addSubmit();

							$form->loadAllValuesFrom($values);

							echo $form->getOutput();
                        }
                    }
                }
            }
        }
    }
}
