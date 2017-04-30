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

@session_start();

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/role_manage.php'>".__($guid, 'Manage Roles')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Role').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/role_manage_edit.php&gibbonRoleID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('addRole', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/role_manage_addProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $categories = array(
        'Staff'   => __('Staff'),
        'Student' => __('Student'),
        'Parent'  => __('Parent'),
        'Other'   => __('Other'),
    );

    $restrictions = array(
        'None'       => __('None'),
        'Same Role'  => __('Users with the same role'),
        'Admin Only' => __('Administrators only'),
    );

    $row = $form->addRow();
        $row->addLabel('category', __('Category'));
        $row->addSelect('category')->fromArray($categories)->isRequired()->placeholder();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->isRequired()->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'));
        $row->addTextField('nameShort')->isRequired()->maxLength(4);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->isRequired()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addTextField('type')->isRequired()->readonly()->setValue('Additional');

    $row = $form->addRow();
        $row->addLabel('pastYearsLogin', __('Login To Past Years'));
        $row->addYesNo('pastYearsLogin')->isRequired();

    $row = $form->addRow();
        $row->addLabel('futureYearsLogin', __('Login To Future Years'));
        $row->addYesNo('futureYearsLogin')->isRequired();

    $row = $form->addRow();
        $row->addLabel('restriction', __('Restriction'))->description('Determines who can grant or remove this role in Manage Users.');
        $row->addSelect('restriction')->fromArray($restrictions)->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
