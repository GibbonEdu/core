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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\ExpenseGateway;
use Gibbon\Module\Finance\Tables\ExpenseLog;


//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all') == false) {
        $page->addError(__('You do not have access to this action.'));
    } else {
        //Proceed!
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';

        $urlParams = compact('gibbonFinanceBudgetCycleID');

        $page->breadcrumbs
            ->add(__('Manage Expenses'), 'expenses_manage.php',  $urlParams)
            ->add(__('Edit Expense'));

        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'] ?? '';
        $status2 = $_GET['status2'] ?? '';
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'] ?? '';
        $gibbonFinanceBudgetID = '';
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

                            //Set Up filter wheres
                            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
									FROM gibbonFinanceExpense
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);

                        if ($result->rowCount() != 1) {
                            $page->addError(__('The specified record cannot be found.'));
                        } else {
                            //Let's go!
                            $values = $result->fetch();

                            if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                                 $params = [
                                    "gibbonFinanceBudgetCycleID" => $gibbonFinanceBudgetCycleID,
                                    "status2" => $status2,
                                    "gibbonFinanceBudgetID2" =>$gibbonFinanceBudgetID2
                                ];
                                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'expenses_manage.php')->withQueryParams($params));
                            }

                            // Get budget allocation & allocated amounts
                            $budgetAllocation = getBudgetAllocation($pdo, $gibbonFinanceBudgetCycleID, $values['gibbonFinanceBudgetID']);
                            $budgetAllocated = getBudgetAllocated($pdo, $gibbonFinanceBudgetCycleID, $values['gibbonFinanceBudgetID']);
                            $budgetRemaining = (is_numeric($budgetAllocation) && is_numeric($budgetAllocated))? ($budgetAllocation - $budgetAllocated) : __('N/A');

							$form = Form::create('expenseManage', $session->get('absoluteURL').'/modules/'.$session->get('module').'/expenses_manage_editProcess.php');
							$form->setFactory(DatabaseFormFactory::create($pdo));

							$form->addHiddenValue('address', $session->get('address'));
							$form->addHiddenValue('status2', $status2);
							$form->addHiddenValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
							$form->addHiddenValue('gibbonFinanceBudgetID', $gibbonFinanceBudgetID);
							$form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);
							$form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

							$form->addRow()->addHeading('Basic Information', __('Basic Information'));

							$cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
							$row = $form->addRow();
								$row->addLabel('name', __('Budget Cycle'));
								$row->addTextField('name')->setValue($cycleName)->maxLength(20)->required()->readonly();

							$row = $form->addRow();
								$row->addLabel('budgetName', __('Budget'));
								$row->addTextField('budgetName')->setValue($values['budget'])->required()->readonly();

							$row = $form->addRow();
								$row->addLabel('title', __('Title'));
								$row->addTextField('title')->required()->readonly();

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

								$row->addSelect('status')->fromArray($statuses)->required()->placeholder();
							} else {
								$row->addTextField('status')->required()->readonly();
							}

							$row = $form->addRow();
								$col = $row->addColumn();
								$col->addLabel('body', __('Description'));
								$col->addContent($values['body']);

							$row = $form->addRow();
								$row->addLabel('purchaseBy', __('Purchase By'));
								$row->addTextField('purchaseBy')->required()->readonly();

							$row = $form->addRow();
								$col = $row->addColumn();
								$col->addLabel('purchaseDetails', __('Purchase Details'));
								$col->addContent($values['purchaseDetails']);

                            $form->addRow()->addHeading('Budget Tracking', __('Budget Tracking'));

                            $row = $form->addRow();
                                $row->addLabel('cost', __('Total Cost'));
                                $row->addCurrency('cost')->required()->readonly()->setValue(number_format($values['cost'], 2, '.', ','));

							$row = $form->addRow();
								$row->addLabel('countAgainstBudget', __('Count Against Budget'))->description(__('For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.'));
                                $row->addYesNo('countAgainstBudget')->required();

                            $form->toggleVisibilityByClass('budgetInfo')->onSelect('countAgainstBudget')->when('Y');

                            $budgetAllocationLabel = (is_numeric($budgetAllocation))? number_format($budgetAllocation, 2, '.', ',') : $budgetAllocation;
                            $row = $form->addRow()->addClass('budgetInfo');
								$row->addLabel('budgetAllocation', __('Budget For Cycle'))->description(__('Numeric value of the fee.'));
                                $row->addCurrency('budgetAllocation')->required()->readonly()->setValue($budgetAllocationLabel);

                            $budgetAllocatedLabel = (is_numeric($budgetAllocated))? number_format($budgetAllocated, 2, '.', ',') : $budgetAllocated;
							$row = $form->addRow()->addClass('budgetInfo');
								$row->addLabel('budgetForCycle', __('Amount already approved or spent'))->description(__('Numeric value of the fee.'));
                                $row->addCurrency('budgetForCycle')->required()->readonly()->setValue($budgetAllocatedLabel);

                            $budgetRemainingLabel = (is_numeric($budgetRemaining))? number_format($budgetRemaining, 2, '.', ',') : $budgetRemaining;
							$row = $form->addRow()->addClass('budgetInfo');
								$row->addLabel('budgetRemaining', __('Budget Remaining For Cycle'))->description(__('Numeric value of the fee.'));
                                $row->addCurrency('budgetRemaining')
                                    ->required()
                                    ->readonly()
                                    ->setValue($budgetRemainingLabel)
                                    ->addClass( (is_numeric($budgetRemaining) && $budgetRemaining - $values['cost'] > 0)? 'textUnderBudget' : 'textOverBudget' );

                            $form->addRow()->addHeading('Log', __('Log'));

                            $expenseLog = $container->get(ExpenseLog::class)->create($gibbonFinanceExpenseID);
                            $form->addRow()->addContent($expenseLog->getOutput());

							$isPaid = $values['status'] == 'Paid';
							if (!$isPaid) {
								$form->toggleVisibilityByClass('paymentInfo')->onSelect('status')->when('Paid');
							}

							$form->addRow()->addHeading('Payment Information', __('Payment Information'))->addClass('paymentInfo');

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentDate', __('Date Paid'))->description(__('Date of payment, not entry to system.'));
								$row->addDate('paymentDate')->required()->setValue(Format::date($values['paymentDate']))->readonly($isPaid);

							$row = $form->addRow()->addClass('paymentInfo');
								$row->addLabel('paymentAmount', __('Amount Paid'))->description(__('Final amount paid.'));
								$row->addCurrency('paymentAmount')->required()->maxLength(15)->readonly($isPaid);

							$row = $form->addRow()->addClass('paymentInfo');
                                $row->addLabel('gibbonPersonIDPayment', __('Payee'))->description(__('Staff who made, or arranged, the payment.'));
                                if ($isPaid) {
                                    $data = array('gibbonPersonID' => $values['gibbonPersonIDPayment']);
                                    $sql = "SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                                    $result = $pdo->executeQuery($data, $sql);
                                    $payee = $result->rowCount() == 1? $result->fetch() : null;
                                    $payeeName = !empty($payee)? Format::name($payee['title'], $payee['preferredName'], $payee['surname'], 'Staff', true, true) : '';
                                    $row->addTextField('payee')->required()->readonly()->setValue($payeeName);
                                    $form->addHiddenValue('gibbonPersonIDPayment', $values['gibbonPersonIDPayment']);
                                } else {
                                    $row->addSelectStaff('gibbonPersonIDPayment')->required()->placeholder();
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
                                    $row->addTextField('paymentMethod')->required()->readonly()->setValue($values['paymentMethod']);
                                } else {
                                    $row->addSelect('paymentMethod')->fromArray($methods)->placeholder()->required()->readonly($isPaid);
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
