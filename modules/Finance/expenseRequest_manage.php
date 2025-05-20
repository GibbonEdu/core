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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\ExpenseGateway;
use Gibbon\Domain\Finance\FinanceBudgetCycleGateway;
use Gibbon\Domain\Finance\FinanceExpenseApproverGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('My Expense Requests'));

    // Check if have Full or Write in any budgets
    $budgets = getBudgetsByPerson($connection2, $session->get('gibbonPersonID'));
    $budgetsAccess = false;

    if (is_array($budgets) && count($budgets) > 0) {
        foreach ($budgets as $budget) {
            if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                $budgetsAccess = true;
            }
        }
    }

    if ($budgetsAccess == false) {
        $page->addError(__('You do not have Full or Write access to any budgets.'));
    } else {
        // Get and check settings
        $financeBudgetCycleGateway = $container->get(FinanceBudgetCycleGateway::class);
        $settingGateway = $container->get(SettingGateway::class);
        $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');
        $expenseRequestTemplate = $settingGateway->getSettingByScope('Finance', 'expenseRequestTemplate');
        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            $page->addError(__('An error has occurred with your expense and budget settings.'));
        } else {
                // Check if there are approvers
                $approvers = $container->get(FinanceExpenseApproverGateway::class)->selectExpenseApprovers();

            if ($approvers->rowCount() < 1) {
                $page->addError(__('An error has occurred with your expense and budget settings.'));
            } else {

                // Ready to go!
                $gibbonFinanceBudgetCycleID = '';
                if (isset($_GET['gibbonFinanceBudgetCycleID'])) {
                    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';
                }
                if ($gibbonFinanceBudgetCycleID == '')  {
                    
                    $result = $financeBudgetCycleGateway->selectBy(['status' => 'Current']);

                    if (empty($result)) {
                        $page->addError(__('The Current budget cycle cannot be determined.'));
                    } else {
                        $row = $result->fetch();
                        $gibbonFinanceBudgetCycleID = $row['gibbonFinanceBudgetCycleID'];
                        $gibbonFinanceBudgetCycleName = $row['name'];
                    }
                }
                if ($gibbonFinanceBudgetCycleID != '') {

                    $budgetCycle = $financeBudgetCycleGateway->getByID($gibbonFinanceBudgetCycleID);

                    if (empty($budgetCycle)) {
                        $page->addError(__('The specified budget cycle cannot be determined.'));
                    } else {
                        $row = $budgetCycle;
                        $gibbonFinanceBudgetCycleName = $row['name'];
                    }
                    echo '<h2>';
                    echo $gibbonFinanceBudgetCycleName;
                    echo '</h2>';
                    echo "<div class='linkTop'>";

                    // Print year picker
                    $previousCycle = getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                    if ($previousCycle != false) {
                        echo "<a href='" . $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=' . $previousCycle . "'>" . __('Previous Cycle') . '</a> ';
                    } else {
                        echo __('Previous Cycle') . ' ';
                    }
                    echo ' | ';
                    $nextCycle = getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                    if ($nextCycle != false) {
                        echo "<a href='" . $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . '/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=' . $nextCycle . "'>" . __('Next Cycle') . '</a> ';
                    } else {
                        echo __('Next Cycle') . ' ';
                    }
                    echo '</div>';

                    $status2 = null;
                    if (isset($_GET['status2'])) {
                        $status2 = $_GET['status2'] ?? '';
                    }
                    $gibbonFinanceBudgetID2 = null;
                    if (isset($_GET['gibbonFinanceBudgetID2'])) {
                        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'] ?? '';
                    }

                    $form = Form::create('action', $session->get('absoluteURL') . '/index.php', 'get');

                    $form->setTitle(__('Filters'));
                    $form->setClass('noIntBorder w-full');

                    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);
                    $form->addHiddenValue('q', "/modules/" . $session->get('module') . "/expenseRequest_manage.php");

                    $statuses = [
                        '' => __('All'),
                        'Requested' => __('Requested'),
                        'Approved' => __('Approved'),
                        'Rejected' => __('Rejected'),
                        'Cancelled' => __('Cancelled'),
                        'Ordered' => __('Ordered'),
                        'Paid' => __('Paid'),
                    ];

                    $row = $form->addRow();
                    $row->addLabel('status2', __('Status'));
                    $row->addSelect('status2')->fromArray($statuses)->selected($status2);

                    $budgetsProcessed = array('' => __('All'));
                    foreach ($budgets as $budget) {
                        $budgetsProcessed[$budget[0]] = $budget[1];
                    }
                    $row = $form->addRow();
                    $row->addLabel('gibbonFinanceBudgetID2', __('Budget'));
                    $row->addSelect('gibbonFinanceBudgetID2')->fromArray($budgetsProcessed)->selected($gibbonFinanceBudgetID2);

                    $row = $form->addRow();
                    $row->addFooter();
                    $row->addSearchSubmit($session);

                    echo $form->getOutput();

                    // QUERY
                    $expenseGateway = $container->get(ExpenseGateway::class);
                    $criteria = $expenseGateway->newQueryCriteria(true)
                        ->sortBy(['defaultSortOrder', 'timestampCreator'])
                        ->filterBy('budget', $gibbonFinanceBudgetID2)
                        ->filterBy('status', $status2)
                        ->filterBy('creator', $session->get('gibbonPersonID'))
                        ->fromPOST();

                    // Fetch expenses using the gateway method
                    $myExpenses = $expenseGateway->queryExpensesByBudgetCycleID($criteria, $gibbonFinanceBudgetCycleID);

                    // DATA TABLE
                    $form = Form::createBlank('manageExpenseRequests', '');
                    $form->addHiddenValue('address', $session->get('address'));

                    $table = $form->addRow()->addDataTable('expenses', $criteria)->withData($myExpenses);

                    $table->setTitle(__('View'));
                    $table->setDescription(__("This page allows you to create and manage expense requests, which will be submitted for approval to the relevant individuals. You will be notified when a request has been approved."));

                    $newExpenseParameters = ['gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'status2' => $status2, 'gibbonFinanceBudgetID2' => $gibbonFinanceBudgetID2];
                    $table->addHeaderAction('add', __('Add'))
                        ->setURL('/modules/Finance/expenseRequest_manage_add.php')
                        ->addParams($newExpenseParameters)
                        ->displayLabel();

                    if (empty($myExpenses)) {
                        echo $page->getBlankSlate();
                    } else {

                        // ADD COLUMNS
                        $table->modifyRows(function ($expense, $row) {
                            if ($expense['status'] == 'Rejected' or $expense['status'] == 'Cancelled')
                                $row->addClass('error');
                            else if ($expense['status'] == 'Approved')
                                $row->addClass('current');
                            return $row;
                        });

                        $table->addColumn('title', __('Title'))
                            ->format(function ($expense) {
                                $output = '<b>' . $expense['title'] . '</b><br/>';
                                return $output;
                            });

                        $table->addColumn('budget', __('Budget'))
                            ->format(function ($expense) {
                                $output = $expense['budget'];
                                return $output;
                            });

                        $table->addColumn('status', __('Status'))
                            ->description(__('Reimbursement'))
                            ->format(function ($expense) {
                                $output = $expense['status'] . '<br/>';
                                if ($expense['paymentReimbursementStatus'] != '') {
                                    $output .= "<span style='font-style: italic; font-size: 75%'>" . __($expense['paymentReimbursementStatus']) . '</span><br/>';
                                }
                                return $output;
                            });

                        $table->addColumn('cost', __('Cost'))
                            ->description(__($session->get('currency')))
                            ->format(function ($expense) {
                                $output = Format::currency($expense['cost']);
                                return $output;
                            });

                        $table->addColumn('timestampCreator', __('Date'))
                            ->format(function ($expense) {
                                $output = Format::date(substr($expense['timestampCreator'], 0, 10));
                                return $output;
                            });

                        // ACTIONS
                        $table->addActionColumn()
                            ->addParams(['gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'status2' => $status2, 'gibbonFinanceBudgetID2' => $gibbonFinanceBudgetID2])
                            ->format(function ($expense, $actions) {
                                $actions->addAction('view', __('View'))
                                    ->setURL('/modules/Finance/expenseRequest_manage_view.php')
                                    ->addParam('gibbonFinanceExpenseID', $expense['gibbonFinanceExpenseID']);

                                if ($expense['status'] == 'Approved' and $expense['purchaseBy'] == 'Self') {
                                    $actions->addAction('reimburse', __('Request Reimbursement'))
                                        ->setURL('/modules/Finance/expenseRequest_manage_reimburse.php')
                                        ->addParam('gibbonFinanceExpenseID', $expense['gibbonFinanceExpenseID'])
                                        ->setIcon('page_right');
                                }
                            });

                        echo $form->getOutput();
                    }
                }
            }
        }
    }
}