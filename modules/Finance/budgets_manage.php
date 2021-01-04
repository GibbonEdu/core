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

use Gibbon\Domain\Finance\FinanceGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Budgets'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }
    
    $gateway = $container->get(FinanceGateway::class);
    $criteria = $gateway
      ->newQueryCriteria(true)
      ->sortBy('gibbonFinanceBudget.active', 'DESC')
      ->fromPOST();
    
    $budgets = $gateway->queryFinanceBudget($criteria);
    $table = DataTable::createPaginated('budgets', $criteria);
    $table->setDescription(__('Budgets are used within purchase requisitions and expense records in order to segregate records into different areas.'));

    $table->modifyRows(function ($item, $row) {
        return $item['active'] == 'N' ? $row->addClass('error') : $row;
    });

    $table->addHeaderAction('add', __('Add'))
      ->setURL('/modules/Finance/budgets_manage_add.php')
      ->displayLabel();
    
    $table->addColumn('name', __('Name'));
    $table->addColumn('nameShort', __('Short Name'));
    $table->addColumn('category', __('Category'));
    $table->addColumn('active', __('Active'))
      ->format(Format::using('yesNo', 'active'));
    
    $table
      ->addActionColumn()
      ->addParam('gibbonFinanceBudgetID')
      ->format(function ($budget, $actions) {
        $actions
          ->addAction('edit', __('Edit'))
          ->setURL('/modules/Finance/budgets_manage_edit.php');
        $actions
          ->addAction('delete', __('Delete'))
          ->setURL('/modules/Finance/budgets_manage_delete.php');
      });

    $table->modifyRows(function ($budget, $row) {
      if($budget['active'] == 'N') {
        $row->addClass('error');
      }
      return $row;
    });

    echo $table->render($budgets);
}
