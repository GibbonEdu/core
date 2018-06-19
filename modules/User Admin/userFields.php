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
use Gibbon\Domain\User\UserFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userFields.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Custom Fields').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $userFieldGateway = $container->get(UserFieldGateway::class);
    
    // QUERY
    $criteria = $userFieldGateway->newQueryCriteria()
        ->sortBy('name')
        ->fromArray($_POST);

    $userFields = $userFieldGateway->queryUserFields($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('userFieldManage', $criteria);

    $table->modifyRows(function ($userField, $row) {
        if ($userField['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/userFields_add.php')
        ->displayLabel();

    $table->addMetaData('filterOptions', [
        'active:Y'     => __('Active').': '.__('Yes'),
        'active:N'     => __('Active').': '.__('No'),
        'role:student' => __('Role').': '.__('Student'),
        'role:parent'  => __('Role').': '.__('Parent'),
        'role:staff'   => __('Role').': '.__('Staff'),
        'role:other'   => __('Role').': '.__('Other'),
    ]);

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'));
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
        ->addParam('gibbonPersonFieldID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/User Admin/userFields_edit.php');
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/User Admin/userFields_delete.php');
            
        });
        
    echo $table->render($userFields);
}
