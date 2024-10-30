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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Finance\Tables\ExpenseLog;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_approve.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';

        $urlParams = compact('gibbonFinanceBudgetCycleID');

        $page->breadcrumbs
            ->add(__('My Expense Requests'), 'expenseRequest_manage.php',  $urlParams)
            ->add(__('Approve/Reject Expense'));

        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'] ?? '';
        $status = '';
        $status2 = $_GET['status2'] ?? '';
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'] ?? '';
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

                            $form = Form::create('expenseManage', $session->get('absoluteURL').'/modules/'.$session->get('module').'/expenses_manage_approveProcess.php');

							$form->addHiddenValue('address', $session->get('address'));
							$form->addHiddenValue('status2', $status2);
							$form->addHiddenValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
							$form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);
							$form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

							$form->addRow()->addHeading('Basic Information', __('Basic Information'));

							$cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
							$row = $form->addRow();
								$row->addLabel('name', __('Budget Cycle'));
								$row->addTextField('name')->setValue($cycleName)->maxLength(20)->required()->readonly();

                            //Can change budgets only if budget level approval is passed (e.g. you are a school approver.
                            if ($highestAction == 'Manage Expenses_all' and $values['statusApprovalBudgetCleared'] == 'Y')
                            {
                                $sql = "SELECT gibbonFinanceBudgetID as value, name FROM gibbonFinanceBudget WHERE active='Y' ORDER BY name";
                                $row = $form->addRow();
                                    $row->addLabel('gibbonFinanceBudgetID', __('Budget'));
                                    $row->addSelect('gibbonFinanceBudgetID')->fromQuery($pdo, $sql)->required()->placeholder()->selected($values['gibbonFinanceBudgetID']);
                            } else {
                                $form->addHiddenValue('gibbonFinanceBudgetID', $values['gibbonFinanceBudgetID']);

                                $row = $form->addRow();
								    $row->addLabel('budgetName', __('Budget'));
								    $row->addTextField('budgetName')->setValue($values['budget'])->required()->readonly();
                            }

							$row = $form->addRow();
								$row->addLabel('title', __('Title'));
								$row->addTextField('title')->required()->readonly();

							$row = $form->addRow();
								$row->addLabel('status', __('Status'));
								$row->addTextField('status')->required()->readonly();

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
                                $row->addLabel('costLabel', __('Total Cost'));
                                $row->addTextField('costLabel')->required()->readonly()->setValue(number_format($values['cost'], 2, '.', ','));

							$row = $form->addRow();
								$row->addLabel('countAgainstBudgetLabel', __('Count Against Budget'));
                                $row->addTextField('countAgainstBudgetLabel')->setValue(Format::yesNo($values['countAgainstBudget']))->required()->readonly();

                            if ($values['countAgainstBudget'] == 'Y') {
                                $budgetAllocationLabel = (is_numeric($budgetAllocation))? number_format($budgetAllocation, 2, '.', ',') : $budgetAllocation;
                                $row = $form->addRow();
                                    $row->addLabel('budgetAllocation', __('Budget For Cycle'))->description(__('Numeric value of the fee.'));
                                    $row->addTextField('budgetAllocation')->required()->readonly()->setValue($budgetAllocationLabel);

                                $budgetAllocatedLabel = (is_numeric($budgetAllocated))? number_format($budgetAllocated, 2, '.', ',') : $budgetAllocated;
                                $row = $form->addRow();
                                    $row->addLabel('budgetForCycle', __('Amount already approved or spent'))->description(__('Numeric value of the fee.'));
                                    $row->addTextField('budgetForCycle')->required()->readonly()->setValue($budgetAllocatedLabel);

                                $budgetRemainingLabel = (is_numeric($budgetRemaining))? number_format($budgetRemaining, 2, '.', ',') : $budgetRemaining;
                                $row = $form->addRow();
                                    $row->addLabel('budgetRemaining', __('Budget Remaining For Cycle'))->description(__('Numeric value of the fee.'));
                                    $row->addTextField('budgetRemaining')
                                        ->required()
                                        ->readonly()
                                        ->setValue($budgetRemainingLabel)
                                        ->addClass( (is_numeric($budgetRemaining) && $budgetRemaining - $values['cost'] > 0)? 'textUnderBudget' : 'textOverBudget' );
                            }

                            $form->addRow()->addHeading('Log', __('Log'));

                            $expenseLog = $container->get(ExpenseLog::class)->create($gibbonFinanceExpenseID);
                            $form->addRow()->addContent($expenseLog->getOutput());

                            $form->addRow()->addHeading('Action', __('Action'));

                            $approvalRequired = approvalRequired($guid, $session->get('gibbonPersonID'), $values['gibbonFinanceExpenseID'], $gibbonFinanceBudgetCycleID, $connection2);
                            if ($approvalRequired != true) {
                                $form->addRow()->addAlert(__('Your approval is not currently required: it is possible someone beat you to it, or you have already approved it.'), 'error');
                            } else {
                                $approvalStatuses = array(
                                    'Approval - Partial' => __('Approve'),
                                    'Rejection' => __('Reject'),
                                    'Comment' => __('Comment'),
                                );
                                $row = $form->addRow();
                                    $row->addLabel('approval', __('Approval'));
                                    $row->addSelect('approval')->fromArray($approvalStatuses)->required()->placeholder();

                                $col = $form->addRow()->addColumn();
                                    $col->addLabel('comment', __('Comment'));
                                    $col->addTextArea('comment')->setRows(8)->setClass('fullWidth');
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
