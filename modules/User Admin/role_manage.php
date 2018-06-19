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
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Roles').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $roleGateway = $container->get(RoleGateway::class);
    
    // QUERY
    $criteria = $roleGateway->newQueryCriteria()
        ->sortBy(['type', 'name'])
        ->fromArray($_POST);

    $roles = $roleGateway->queryRoles($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('roleManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/role_manage_add.php')
        ->displayLabel();

    $table->addColumn('category', __('Category'));
    $table->addColumn('name', __('Name'));
    $table->addColumn('nameShort', __('Short Name'));
    $table->addColumn('description', __('Description'));
    $table->addColumn('type', __('Type'));
    $table->addColumn('loginYear', __('Login Years'))
        ->notSortable()
        ->format(function ($row) {
            if ($row['futureYearsLogin'] == 'Y' and $row['pastYearsLogin'] == 'Y') {
                return __('All years');
            } elseif ($row['futureYearsLogin'] == 'N' and $row['pastYearsLogin'] == 'N') {
                return __('Current year only');
            } elseif ($row['futureYearsLogin'] == 'N') {
                return __('Current/past years only');
            } elseif ($row['pastYearsLogin'] == 'N') {
                return __('Current/future years only');
            }
        });

    $table->addActionColumn()
        ->addParam('gibbonRoleID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/User Admin/role_manage_edit.php');

            if ($row['type'] == 'Additional') {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/User Admin/role_manage_delete.php');
            }

            $actions->addAction('duplciate', __('Duplicate'))
                ->setIcon('copy')
                ->setURL('/modules/User Admin/role_manage_duplicate.php');
        });

    echo $table->render($roles);
}
