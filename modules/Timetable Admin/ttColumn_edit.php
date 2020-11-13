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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Columns'), 'ttColumn.php')
        ->add(__('Edit Column'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $ttColumnGateway = $container->get(TimetableColumnGateway::class);

    //Check if school year specified
    $gibbonTTColumnID = $_GET['gibbonTTColumnID'];
    if ($gibbonTTColumnID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

        $values = $ttColumnGateway->getTTColumnByID($gibbonTTColumnID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/ttColumn_editProcess.php');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonTTColumnID', $values['gibbonTTColumnID']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
                $row->addTextField('name')->maxLength(30)->required();

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->maxLength(12)->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Column Rows');
            echo '</h2>';

            $rows = $ttColumnGateway->selectTTColumnRowsByID($gibbonTTColumnID);

            // DATA TABLE
            $table = DataTable::create('timetableColumnRows');

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Timetable Admin/ttColumn_edit_row_add.php')
                ->addParam('gibbonTTColumnID', $gibbonTTColumnID)
                ->displayLabel();

            $table->addColumn('name', __('Name'));
            $table->addColumn('nameShort', __('Short Name'));
            $table->addColumn('time', __('Time'))->format(Format::using('timeRange', ['timeStart', 'timeEnd']));
            $table->addColumn('type', __('Type'))->translatable();

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonTTColumnID', $gibbonTTColumnID)
                ->addParam('gibbonTTColumnRowID')
                ->format(function ($values, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/ttColumn_edit_row_edit.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Timetable Admin/ttColumn_edit_row_delete.php');
                });

            echo $table->render($rows->toDataSet());
        }
    }
}
