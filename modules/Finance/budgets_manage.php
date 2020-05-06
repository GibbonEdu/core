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
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Budgets'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }
    
    echo '<p>';
    echo __('Budgets are used within purchase requisitions and expense records in order to segregate records into different areas.');
    echo '</p>';

    $gateway = $container->get(FinanceGateway::class);
    $criteria = $gateway
      ->newQueryCriteria(true)
      ->fromPOST();
    $budgets = $gateway->queryFinanceBudget($criteria);
    $table = DataTable::createPaginated('budgets',$criteria);
    $table
      ->addHeaderAction('add',__('Add'))
      ->setURL('/modules/Finance/budgets_manage_add.php');
    $table->addColumn('name',__('Name'));
    $table->addColumn('nameShort',__('Short Name'));
    $table->addColumn('category',__('Category'));
    $table
      ->addColumn('active',__('Active'))
      ->format(function($budget) {
        return $budget['active'] == 'Y' ? 'Yes' : 'No';
      });
    $table
      ->addActionColumn()
      ->addParam('gibbonFinanceBudgetID')
      ->format(function($budget,$actions) {
        $actions
          ->addAction('edit',__('Edit'))
          ->setURL('/modules/Finance/budgets_manage_edit.php&');
        $actions
          ->addAction('delete',__('Delete'));
      });

    $table->modifyRows(function($budget,$row) {
      if($budget['active'] == 'N')
      {
        $row->addClass('error');
      }
      return $row;
    });

    echo $table->render($budgets);
}
