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
use Gibbon\Domain\System\CustomFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/customFields.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Custom Fields'));

    $customFieldGateway = $container->get(CustomFieldGateway::class);
    
    // QUERY
    $criteria = $customFieldGateway->newQueryCriteria(true)
        ->sortBy(['context', 'name'])
        ->fromPOST();

    $userFields = $customFieldGateway->queryCustomFields($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('userFieldManage', $criteria);

    $table->modifyRows(function ($userField, $row) {
        if ($userField['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/System Admin/customFields_add.php')
        ->displayLabel();

    $table->addMetaData('filterOptions', [
        'active:Y'     => __('Active').': '.__('Yes'),
        'active:N'     => __('Active').': '.__('No'),
        'role:student' => __('Role').': '.__('Student'),
        'role:parent'  => __('Role').': '.__('Parent'),
        'role:staff'   => __('Role').': '.__('Staff'),
        'role:other'   => __('Role').': '.__('Other'),
    ]);

    $customFieldTypes = array(
        'varchar' => __('Short Text'),
        'text'    => __('Long Text'),
        'date'    => __('Date'),
        'url'     => __('Link'),
        'select'  => __('Dropdown')
    );

    $table->addColumn('context', __('Context'))->translatable();
    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'))->format(function ($row) use ($customFieldTypes) {
        return isset($customFieldTypes[$row['type']])? $customFieldTypes[$row['type']] : '';
    });
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));
    $table->addColumn('roles', __('Role Categories'))
        ->notSortable()
        ->format(function ($row) {
            $output = '';
            if ($row['activePersonStudent']) $output .= __('Student').'<br/>';
            if ($row['activePersonParent']) $output .= __('Parent').'<br/>';
            if ($row['activePersonStaff']) $output .= __('Staff').'<br/>';
            if ($row['activePersonOther']) $output .= __('Other').'<br/>';
            return $output;
        });

    $table->addActionColumn()
        ->addParam('gibbonCustomFieldID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/System Admin/customFields_edit.php');
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/System Admin/customFields_delete.php');
            
        });
        
    echo $table->render($userFields);
}
