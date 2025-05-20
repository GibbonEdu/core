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

use Gibbon\Domain\Finance\ExpenseGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\FinanceBudgetCycleGateway;
use Gibbon\Domain\Finance\FinanceExpenseApproverGateway;
use Gibbon\Http\Url;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    } 

    //Proceed!
    $page->breadcrumbs->add(__('Manage Expenses'));

    $page->return->addReturns(['success0' => __('Your request was completed successfully.'), 'success1' => __('Your request was completed successfully, but notifications could not be sent out.')]);

    echo '<p>';
    if ($highestAction == 'Manage Expenses_all') {
        echo __('This action allows you to manage all expenses for all budgets, regardless of your access rights to individual budgets.').'<br/>';
    } else {
        echo __('This action allows you to manage expenses for the budgets in which you have relevant access rights.').'<br/>';
    }
    echo '</p>';

    //Check if have Full, Write or Read access in any budgets
    $budgetsAccess = false;
    $budgetsActionAccess = false;
    $budgets = getBudgetsByPerson($connection2, $session->get('gibbonPersonID'));
    $budgetsAll = null;
    if ($highestAction == 'Manage Expenses_all') {
        $budgetsAll = getBudgets($connection2);
        $budgetsAccess = true;
        $budgetsActionAccess = true;
    } else {
        if (is_array($budgets) && count($budgets)>0) {
            foreach ($budgets as $budget) {
                if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                    $budgetsActionAccess = true;
                    $budgetsAccess = true;
                }
                if ($budget[2] == 'Read') {
                    $budgetsAccess = true;
                }
            }
        }
    }

    if ($budgetsAccess == false) {
        $page->addError(__('You do not have Full or Write access to any budgets.'));
        return;
    } 

    //Get and check settings
    $settingGateway = $container->get(SettingGateway::class);
    $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
    $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');
    if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
        $page->addError(__('An error has occurred with your expense and budget settings.'));
        return;
    } 

    // Check if there are approvers
    $result = $container->get(FinanceExpenseApproverGateway::class)->selectExpenseApprovers();
    if ($result->rowCount() < 1) {
        $page->addError(__('An error has occurred with your expense and budget settings.'));
        return;
    } 

    //Ready to go!
    $gibbonFinanceBudgetCycleID = '';
    if (isset($_GET['gibbonFinanceBudgetCycleID'])) {
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';
    }

    if ($gibbonFinanceBudgetCycleID == '') {
            $data = [];
            $sql = "SELECT * FROM gibbonFinanceBudgetCycle WHERE status='Current'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __('The Current budget cycle cannot be determined.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonFinanceBudgetCycleID = $row['gibbonFinanceBudgetCycleID'];
            $gibbonFinanceBudgetCycleName = $row['name'];
        }
    }

    if (empty($gibbonFinanceBudgetCycleID)) {
        return;
    }
    $result = $container->get(FinanceBudgetCycleGateway::class)->getByID($gibbonFinanceBudgetCycleID);

    if (empty($result)) {
        echo "<div class='error'>";
        echo __('The specified budget cycle cannot be determined.');
        echo '</div>';
    } else {
        $row = $result;
        $gibbonFinanceBudgetCycleName = $row['name'];
    }

    echo '<h2>';
    echo $gibbonFinanceBudgetCycleName;
    echo '</h2>';

    echo "<div class='linkTop'>";
    //Print year picker
    $previousCycle = getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
    if ($previousCycle != false) {
        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/expenses_manage.php&gibbonFinanceBudgetCycleID='.$previousCycle."'>".__('Previous Cycle').'</a> ';
    } else {
        echo __('Previous Cycle').' ';
    }
    echo ' | ';
    $nextCycle = getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
    if ($nextCycle != false) {
        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/expenses_manage.php&gibbonFinanceBudgetCycleID='.$nextCycle."'>".__('Next Cycle').'</a> ';
    } else {
        echo __('Next Cycle').' ';
    }
    echo '</div>';


    $status2 = $_GET['status2'] ?? '';
    $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'] ?? '';
    
    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filters'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/Finance/expenses_manage.php');
    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

    $statuses = [
        '' => __('All'),
        'Requested' => __('Requested'),
        'Requested - Approval Required' => __('Requested - Approval Required'),
        'Approved' => __('Approved'),
        'Rejected' => __('Rejected'),
        'Cancelled' => __('Cancelled'),
        'Ordered' => __('Ordered'),
        'Paid' => __('Paid'),
    ];
    $row = $form->addRow();
        $row->addLabel('status2', __('Status'));
        $row->addSelect('status2')
            ->fromArray($statuses)
            ->selected($status2);

    $budgetsList = array_reduce($budgetsAll != null? $budgetsAll : $budgets, function($group, $item) {
        $group[$item[0]] = $item[1];
        return $group;
    }, array());
    $row = $form->addRow();
        $row->addLabel('gibbonFinanceBudgetID2', __('Budget'));
        $row->addSelect('gibbonFinanceBudgetID2')
            ->fromArray(array('' => __('All')))
            ->fromArray($budgetsList)
            ->selected($gibbonFinanceBudgetID2);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'), array('gibbonFinanceBudgetCycleID'));

    echo $form->getOutput();

    $expenseGateway = $container->get(ExpenseGateway::class);

    $criteria = $expenseGateway->newQueryCriteria()
        ->sortBy(['defaultSortOrder', 'timestampCreator'])
        ->filterBy('budget', $gibbonFinanceBudgetID2)
        ->filterBy('status', $status2)
        ->fromPOST();

    if ($highestAction == 'Manage Expenses_all') {
        $expenses = $expenseGateway->queryExpensesByBudgetCycleID($criteria, $gibbonFinanceBudgetCycleID);
    } else {
        $expenses = $expenseGateway->queryExpensesByBudgetCycleID($criteria, $gibbonFinanceBudgetCycleID, $session->get('gibbonPersonID'));
    }

    $expenses->transform(function (&$expense) use ($guid, $session, $gibbonFinanceBudgetCycleID, $connection2) {
        $expense['approvalRequired'] = approvalRequired($guid, $session->get('gibbonPersonID'), $expense['gibbonFinanceExpenseID'], $gibbonFinanceBudgetCycleID, $connection2, false);
    });

    if ($status2 == 'Requested - Approval Required') {
        $status2 = 'Requested';
        $expenses->filter(function ($expense) {
            return $expense['approvalRequired'];
        });
    }

    $urlParams = [
        'status2'                    => $status2,
        'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID,
        'gibbonFinanceBudgetID2'     => $gibbonFinanceBudgetID2,
    ];

    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL') . '/modules/Finance/expenses_manage_processBulk.php?'.http_build_query($urlParams));
    $form->setTitle(__('View'));

    $form->addHiddenValue('address', $session->get('address'));

    $allowExpenseAdd = $settingGateway->getSettingByScope('Finance', 'allowExpenseAdd');
    if ($highestAction == 'Manage Expenses_all' and $allowExpenseAdd == 'Y') { //Access to everything
        $form->addHeaderAction('add', __('Add Expense'))
            ->setURL('/modules/Finance/expenses_manage_add.php')
            ->addParams($urlParams)
            ->displayLabel();
    }

    // DATA TABLE
    $table = $form->addRow()->addDataTable('expenses', $criteria)->withData($expenses);

    $table->modifyRows(function ($expense, $row) {
        if ($expense['status'] == 'Approved') $row->addClass('success');
        if ($expense['status'] == 'Rejected') $row->addClass('error');
        if ($expense['status'] == 'Cancelled') $row->addClass('dull');
        return $row;
    });

    if ($budgetsActionAccess) {
        $bulkActions = ['export' => __('Export')];
        $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

        $table->addMetaData('bulkActions', $col);
    }

    $table->addColumn('title', __('Title'))
        ->description(__('Budget'))
        ->formatDetails(function ($values) {
            return Format::small($values['budget'] ?? '');
        });
    $table->addColumn('staff', __('Staff'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonIDCreator'], '', $values['preferredName'], $values['surname'], 'Staff', false, true);
        });
    $table->addColumn('status', __('Status'))
        ->description(__('Reimbursement'))
        ->formatDetails(function ($values) {
            return Format::small($values['paymentReimbursementStatus'] ?? '');
        });
    $table->addColumn('cost', __('Cost'))->description($session->get('currency'))
        ->format(Format::using('currency', 'cost'));
    $table->addColumn('timestampCreator', __('Date'))
        ->format(Format::using('date', 'timestampCreator'));

    if ($budgetsActionAccess) {
    $table->addActionColumn()
        ->addParam('gibbonFinanceExpenseID')
        ->addParam('search', $criteria->getSearchText(true))
        ->addParams($urlParams)
        ->format(function ($expense, $actions) use ($highestAction, $urlParams) {

            if ($highestAction == 'Manage Expenses_all' && $expense['status'] == 'Requested' && $expense['approvalRequired'] == true) {
                $actions->addAction('approve', __('Approve/Reject'))
                        ->setURL('/modules/Finance/expenses_manage_approve.php')
                        ->setIcon('iconTick');
            }
            
            $actions->addAction('view', __('View'))
                    ->setURL('/modules/Finance/expenses_manage_view.php');

            $actions->addAction('print', __('Print'))
                    ->setURL(Url::fromHandlerModuleRoute('report.php', 'Finance', 'expenses_manage_print_print')->withQueryParams($urlParams + ['gibbonFinanceExpenseID' => $expense['gibbonFinanceExpenseID']]))
                    ->directLink();

            if ($highestAction == 'Manage Expenses_all') {
                if ($expense['status'] == 'Requested' || $expense['status'] == 'Approved' || $expense['status'] == 'Ordered' || ($expense['status'] == 'Paid' && $expense['paymentReimbursementStatus'] == 'Requested')) {
                    $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Finance/expenses_manage_edit.php');
                }

                
            }
        });
    
        $table->addCheckboxColumn('gibbonFinanceExpenseID');
    }

    echo $form->getOutput();
}
