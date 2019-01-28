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
use Gibbon\Domain\Finance\FinanceExpenseGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;

include '../../config.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $expenseIDList = $gibbon->session->get('financeExpenseExportIDs');
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];

    if (empty($expenseIDList) || empty($gibbonFinanceBudgetCycleID)) {
        echo "<div class='error'>";
        echo __('List of invoices or budget cycle have not been specified, and so this export cannot be completed.');
        echo '</div>';
    } else {
        
        $expenseGateway = $container->get(FinanceExpenseGateway::class);
        
        $criteria = $expenseGateway->newQueryCriteria()
            ->sortBy(['FIND_IN_SET(gibbonFinanceExpense.status, \'Requested,Approved,Rejected,Cancelled,Ordered,Paid\'), timestampCreator, surname, preferredName'])
            ->pageSize(0)
        ;

        $expenses = $expenseGateway->queryByExpenseIDList($expenseIDList, $criteria);

        $table = ReportTable::createPaginated('expenseExportOn'.date('Y-m-d'), $criteria)->setViewMode('export',$gibbon->session);
        $table->setTitle('Expenses');
        $table->addMetaData('Subject','Expense Export');
        $table->setDescription('Expense Export');

        $table->addColumn('gibbonFinanceExpenseID', __('Expense Number'));
        $table->addColumn('budget', __('Budget'));
        $table->addColumn('budgetCycle', __('Budget Cycle'));
        $table->addColumn('title', __('Title'));
        $table->addColumn('status', __('Status'));
        $table->addColumn('cost', __('Cost')." (".$gibbon->session->get('currency').')');
        $table->addColumn('staff', __('Staff'));
        $table->addColumn('timestampCreator', __('Timestamp'));

        $expenses->transform(function(&$row){
            $row['staff'] = Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true);
            $row['gibbonFinanceExpenseID'] = intval($row['gibbonFinanceExpenseID']);
            return ;
        });

        $gibbon->session->remove('financeExpenseExportIDs');
        $table->render($expenses);
    }
}
