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
use Gibbon\Domain\Timetable\ClassFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/classFields.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Custom Fields'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $classFieldGateway = $container->get(ClassFieldGateway::class);
    
    // QUERY
    $criteria = $classFieldGateway->newQueryCriteria(true)
        ->sortBy('name')
        ->fromPOST();

    $classFields = $classFieldGateway->queryClassFields($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('classFieldManage', $criteria);

    $table->modifyRows(function ($classField, $row) {
        if ($classField['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Timetable Admin/classFields_add.php')
        ->displayLabel();

    $table->addMetaData('filterOptions', [
        'active:Y'     => __('Active').': '.__('Yes'),
        'active:N'     => __('Active').': '.__('No'),
    ]);

    $customFieldTypes = array(
        'varchar' => __('Short Text'),
        'text'    => __('Long Text'),
        'date'    => __('Date'),
        'url'     => __('Link'),
        'select'  => __('Dropdown')
    );

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'))->format(function ($row) use ($customFieldTypes) {
        return isset($customFieldTypes[$row['type']])? $customFieldTypes[$row['type']] : '';
    });
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    $table->addActionColumn()
        ->addParam('gibbonClassFieldID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Timetable Admin/classFields_edit.php');
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Timetable Admin/classFields_delete.php');
            
        });
        
    echo $table->render($classFields);
}
