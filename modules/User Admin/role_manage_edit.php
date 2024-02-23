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

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Roles'),'role_manage.php')
        ->add(__('Edit Role'));     

    //Check if gibbonRoleID specified
    $gibbonRoleID = $_GET['gibbonRoleID'] ?? '';
    if ($gibbonRoleID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonRoleID' => $gibbonRoleID);
            $sql = 'SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $role = $result->fetch();
            $isReadOnly = ($role['type'] == 'Core');

            $form = Form::create('addRole', $session->get('absoluteURL').'/modules/'.$session->get('module').'/role_manage_editProcess.php?gibbonRoleID='.$gibbonRoleID);

            $form->addHiddenValue('address', $session->get('address'));

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
                $row->addTextField('category')->required()->readonly()->setValue($role['category']);
            } else {
                $row->addSelect('category')->fromArray($categories)->required()->placeholder()->selected($role['category']);
            }

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(20)->readonly($isReadOnly)->setValue($role['name']);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'));
                $row->addTextField('nameShort')->required()->maxLength(4)->readonly($isReadOnly)->setValue($role['nameShort']);

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextField('description')->required()->maxLength(60)->readonly($isReadOnly)->setValue(__($role['description']));

            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addTextField('type')->required()->readonly()->setValue($role['type']);

            $row = $form->addRow();
                $row->addLabel('canLoginRole', __('Can Login?'))->description(__('Are users with this primary role able to login?'));
                if ($role['name'] == 'Administrator') {
                    $row->addTextField('canLoginRole')->required()->readonly()->setValue(__('Yes'));
                } else {
                    $row->addYesNo('canLoginRole')->required()->selected($role['canLoginRole']);
                    $form->toggleVisibilityByClass('loginOptions')->onSelect('canLoginRole')->when('Y');
                }

            $row = $form->addRow()->addClass('loginOptions');
                $row->addLabel('pastYearsLogin', __('Login To Past Years'));
                $row->addYesNo('pastYearsLogin')->required()->selected($role['pastYearsLogin']);

            $row = $form->addRow()->addClass('loginOptions');
                $row->addLabel('futureYearsLogin', __('Login To Future Years'));
                $row->addYesNo('futureYearsLogin')->required()->selected($role['futureYearsLogin']);

            $row = $form->addRow();
                $row->addLabel('restriction', __('Restriction'))->description(__('Determines who can grant or remove this role in Manage Users.'));
            if ($role['name'] == 'Administrator') {
                $row->addTextField('restrictionText')->required()->readonly()->setValue(__('Administrators only'));
                $form->addHiddenValue('restriction', 'Admin Only');
            } else {
                $row->addSelect('restriction')->fromArray($restrictions)->required()->selected($role['restriction']);
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
