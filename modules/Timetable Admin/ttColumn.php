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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Timetable\TimetableColumnGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Columns').'</div>';
    echo '</div>';
    echo '<p>';
    echo __($guid, 'In Gibbon a column is a holder for the structure of a day. A number of columns can be defined, and these can be tied to particular timetable days in the timetable interface.');
    echo '</p>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $ttColumnGateway = $container->get(TimetableColumnGateway::class);
    $columns = $ttColumnGateway->selectTTColumns();

    // DATA TABLE
    $table = DataTable::create('timetableColumns');

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Timetable Admin/ttColumn_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('nameShort', __('Short Name'));
    $table->addColumn('rowCount', __('Rows'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonTTColumnID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Timetable Admin/ttColumn_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Timetable Admin/ttColumn_delete.php');
        });

    echo $table->render($columns->toDataSet());
}
