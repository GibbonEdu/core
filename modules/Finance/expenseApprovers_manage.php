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

use Gibbon\Services\Format;
use Gibbon\Domain\Finance\FinanceGateway;
use Gibbon\Tables\DataTable;

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseApprovers_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Expense Approvers'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
    $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
    echo '<p>';
    if ($expenseApprovalType == 'One Of') {
        if ($budgetLevelExpenseApproval == 'Y') {
            echo __("Expense approval has been set as 'One Of', which means that only one of the people listed below (as well as someone with Full budget access) needs to approve an expense before it can go ahead.");
        } else {
            echo __("Expense approval has been set as 'One Of', which means that only one of the people listed below needs to approve an expense before it can go ahead.");
        }
    } elseif ($expenseApprovalType == 'Two Of') {
        if ($budgetLevelExpenseApproval == 'Y') {
            echo __("Expense approval has been set as 'Two Of', which means that only two of the people listed below (as well as someone with Full budget access) need to approve an expense before it can go ahead.");
        } else {
            echo __("Expense approval has been set as 'Two Of', which means that only two of the people listed below need to approve an expense before it can go ahead.");
        }
    } elseif ($expenseApprovalType == 'Chain Of All') {
        if ($budgetLevelExpenseApproval == 'Y') {
            echo __("Expense approval has been set as 'Chain Of All', which means that all of the people listed below (as well as someone with Full budget access) need to approve an expense, in order from lowest to highest, before it can go ahead.");
        } else {
            echo __("Expense approval has been set as 'Chain Of All', which means that all of the people listed below need to approve an expense, in order from lowest to highest, before it can go ahead.");
        }
    } else {
        echo __('Expense Approval policies have not been set up: this should be done under Admin > School Admin > Finance Settings.');
    }
    echo '</p>';

    $gateway = $container->get(FinanceGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
        ->fromPOST();


    switch ($expenseApprovalType) {
        case "Chain Of All":
            $criteria->sortBy(
                [
                'sequenceNumber',
                'surname',
                'preferredName'
                ]
            );
            break;

        default:
            $criteria->sortBy(
                [
                'surname',
                'preferredName'
                ]
            );
            break;
    }
    $feeApprovers = $gateway->queryExpenseApprovers($criteria);
    $table = DataTable::createPaginated('expenseApprovers', $criteria);
    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Finance/expenseApprovers_manage_add.php');

    $table
        ->addColumn('name', __('Name'))
        ->format(
            function ($expenseApprover) {
                return Format::name($expenseApprover['title'], $expenseApprover['preferredName'], $expenseApprover['surname'],'Staff',true,true);
            }
        );

    if ($expenseApprovalType == 'Chain Of All') {
        $table->addColumn('sequenceNumber', __('Sequence Number'));
    }

    $table
        ->addActionColumn()
        ->addParam('gibbonFinanceExpenseApproverID')
        ->format(
            function ($expenseApprover, $actions) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Finance/expenseApprovers_manage_edit.php');
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Finance/expenseApprovers_manage_delete.php');
            }
        );
    echo $table->render($feeApprovers);
}
