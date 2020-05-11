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

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Budget Cycles'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    $gateway = $container->get(FinanceGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
                        ->fromPOST();
    $budgetCycles = $gateway->queryFinanceCycles($criteria);
    $table = DataTable::createPaginated('cycles', $criteria);
    $table->addHeaderAction('add', __('Add'))
      ->setURL('/modules/Finance/budgetCycles_manage_add.php');
    $table->addColumn('sequenceNumber', __('Sequence'));
    $table->addColumn('name', __('Name'));
    $table->addColumn('dates', __('Dates'))
          ->format(function ($cycle) {
            return Format::dateRange($cycle['dateStart'], $cycle['dateEnd']);
          });
    $table->addColumn('status', __('Status'));
    $actions = $table->addActionColumn()
        ->addParam('gibbonFinanceBudgetCycleID')
                     ->format(function ($cycle, $actions) {
                        $actions->addAction('edit', __('Edit'))
                               ->setURL('/modules/Finance/budgetCycles_manage_edit.php');
                        $actions->addAction('delete', __('Delete'))
                          ->setURL('/modules/Finance/budgetCycles_manage_delete.php');
                     });
    echo $table->render($budgetCycles);
    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonFinanceBudgetCycle ORDER BY sequenceNumber';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
}
