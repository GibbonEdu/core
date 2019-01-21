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
use Gibbon\Domain\Departments\DepartmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $page->breadcrumbs->add(__('Manage Departments'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __('Department Access');
    echo '</h3>';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_manageProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $setting = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->isRequired()->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    echo '<h3>';
    echo __('Departments');
    echo '</h3>';

    $departmentGateway = $container->get(DepartmentGateway::class);

    // QUERY
    $criteria = $departmentGateway->newQueryCriteria()
        ->sortBy('name')
        ->fromPOST();

    $departments = $departmentGateway->queryDepartments($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('departmentManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/department_manage_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Type'));
    $table->addColumn('nameShort', __('Short Name'));
    $table->addColumn('staff', __('Staff'))
        ->sortable(false)
        ->format(function($row) use ($departmentGateway) {
            $staff = $departmentGateway->selectStaffByDepartment($row['gibbonDepartmentID'])->fetchAll();
            return (!empty($staff)) 
                ? Format::nameList($staff, 'Staff', true, true)
                : '<i>'.__('None').'</i>';
        });
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonDepartmentID')
        ->format(function ($department, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/department_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/department_manage_delete.php');
        });

    echo $table->render($departments);
}
