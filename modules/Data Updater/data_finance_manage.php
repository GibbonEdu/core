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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\DataUpdater\FinanceUpdateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Finance Data Updates').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gateway = $container->get(FinanceUpdateGateway::class);

    // QUERY
    $criteria = $gateway->newQueryCriteria()
        ->sortBy('status')
        ->sortBy('timestamp', 'DESC')
        ->fromArray($_POST);

    $dataUpdates = $gateway->queryDataUpdates($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    // DATA TABLE
    $table = DataTable::createPaginated('financeUpdateManage', $criteria);

    $table->modifyRows(function ($update, $row) {
        if ($update['status'] != 'Pending') $row->addClass('current');
        return $row;
    });

    // COLUMNS
    $table->addColumn('target', __('Target User'))
        ->sortable(['target.surname', 'target.preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student']));
    $table->addColumn('updater', __('Requesting User'))
        ->sortable(['updater.surname', 'updater.preferredName'])
        ->format(Format::using('name', ['updaterTitle', 'updaterPreferredName', 'updaterSurname', 'Parent']));
    $table->addColumn('timestamp', __('Date & Time'))->format(Format::using('dateTime', 'timestamp'));
    $table->addColumn('status', __('Status'))->width('12%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonFinanceInvoiceeUpdateID')
        ->format(function ($update, $actions) {
            if ($update['status'] == 'Pending') {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Data Updater/data_finance_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Data Updater/data_finance_manage_delete.php');
            }
        });

    echo $table->render($dataUpdates);
}
