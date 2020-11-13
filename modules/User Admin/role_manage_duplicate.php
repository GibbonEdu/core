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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $page->breadcrumbs
        ->add(__('Manage Roles'),'role_manage.php')
        ->add(__('Duplicate Role')); 

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if courseschool year specified
    $gibbonRoleID = $_GET['gibbonRoleID'];
    if ($gibbonRoleID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonRoleID' => $gibbonRoleID);
            $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $role = $result->fetch();

            $form = Form::create('addRole', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/role_manage_duplicateProcess.php?gibbonRoleID='.$gibbonRoleID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('role', __('Role'));
                $row->addTextField('role')->required()->readonly()->setValue($role['name']);


            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(20);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->required()->maxLength(4);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
