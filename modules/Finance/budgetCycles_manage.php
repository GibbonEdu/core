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

use Gibbon\Domain\Finance\FinanceGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Budget Cycles'));

    $gateway = $container->get(FinanceGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
                        ->fromPOST();
                        
    $budgetCycles = $gateway->queryFinanceCycles($criteria);
    $table = DataTable::createPaginated('cycles', $criteria);

    $table->modifyRows(function ($item, $row) {
        return $item['status'] == 'Current' ? $row->addClass('success') : $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Finance/budgetCycles_manage_add.php')
        ->displayLabel();

    $table->addColumn('sequenceNumber', __('Sequence'));
    $table->addColumn('name', __('Name'));
    $table->addColumn('dateStart', __('Dates'))
          ->format(function ($cycle) {
            return Format::dateRange($cycle['dateStart'], $cycle['dateEnd']);
          });
    $table->addColumn('status', __('Status'))->translatable();

    $actions = $table->addActionColumn()
        ->addParam('gibbonFinanceBudgetCycleID')
        ->format(function ($cycle, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Finance/budgetCycles_manage_edit.php');
            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Finance/budgetCycles_manage_delete.php');
        });
    echo $table->render($budgetCycles);
}
