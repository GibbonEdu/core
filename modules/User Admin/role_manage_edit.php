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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/role_manage.php'>".__($guid, 'Manage Roles')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Role').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonRoleID = $_GET['gibbonRoleID'];
    if ($gibbonRoleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonRoleID' => $gibbonRoleID);
            $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $role = $result->fetch();
            $isReadOnly = ($role['type'] == 'Core');

            $form = Form::create('addRole', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/role_manage_editProcess.php?gibbonRoleID='.$gibbonRoleID);

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
            if ($isReadOnly) {
                $row->addTextField('category')->isRequired()->readonly()->setValue($role['category']);
            } else {
                $row->addSelect('category')->fromArray($categories)->isRequired()->placeholder()->selected($role['category']);
            }

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired()->maxLength(20)->readonly($isReadOnly)->setValue($role['name']);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->isRequired()->maxLength(4)->readonly($isReadOnly)->setValue($role['nameShort']);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextField('description')->isRequired()->maxLength(60)->readonly($isReadOnly)->setValue($role['description']);

            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addTextField('type')->isRequired()->readonly()->setValue($role['type']);

            $row = $form->addRow();
                $row->addLabel('canLoginRole', __('Can Login?'))->description(__('Are users with this primary role able to login?'));
                if ($role['name'] == 'Administrator') {
                    $row->addTextField('canLoginRole')->isRequired()->readonly()->setValue(__('Yes'));
                } else {
                    $row->addYesNo('canLoginRole')->isRequired()->selected($role['canLoginRole']);
                    $form->toggleVisibilityByClass('loginOptions')->onSelect('canLoginRole')->when('Y');
                }

            $row = $form->addRow()->addClass('loginOptions');
                $row->addLabel('pastYearsLogin', __('Login To Past Years'));
                $row->addYesNo('pastYearsLogin')->isRequired()->selected($role['pastYearsLogin']);

            $row = $form->addRow()->addClass('loginOptions');
                $row->addLabel('futureYearsLogin', __('Login To Future Years'));
                $row->addYesNo('futureYearsLogin')->isRequired()->selected($role['futureYearsLogin']);

            $row = $form->addRow();
                $row->addLabel('restriction', __('Restriction'))->description('Determines who can grant or remove this role in Manage Users.');
            if ($role['name'] == 'Administrator') {
                $row->addTextField('restriction')->isRequired()->readonly()->setValue('Admin Only');
            } else {
                $row->addSelect('restriction')->fromArray($restrictions)->isRequired()->selected($role['restriction']);
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
