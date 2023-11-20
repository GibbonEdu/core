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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Timetable\TimetableColumnGateway;

$page->breadcrumbs->add(__('Manage Columns'));
    echo '<p>';
    echo __('In Gibbon a column is a holder for the structure of a day. A number of columns can be defined, and these can be tied to particular timetable days in the timetable interface.');
    echo '</p>';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $timetableColumnGateway = $container->get(TimetableColumnGateway::class);

    $criteria = $timetableColumnGateway->newQueryCriteria(true)
        ->sortBy(['name'])
        ->fromPOST();

    $columns = $timetableColumnGateway->queryTTColumns($criteria);

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttColumnProcessBulk.php');
    $form->addHiddenValue('address', $session->get('address'));

    // BULK ACTIONS
    $bulkActions = array(
        'Duplicate' => __('Duplicate')
    );
    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('columnsManage', $criteria)->withData($columns);

    $table->addMetaData('bulkActions', $col);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Timetable Admin/ttColumn_add.php')
        ->displayLabel();

    // COLUMNS
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

    $table->addCheckboxColumn('gibbonTTColumnIDList', 'gibbonTTColumnID');

    echo $form->getOutput();
}
