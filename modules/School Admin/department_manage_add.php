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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\CustomFieldHandler;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('Manage Departments'), 'department_manage.php')
        ->add(__('Add Department'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/department_manage_edit.php&gibbonDepartmentID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('departmentManageRecord', $session->get('absoluteURL').'/modules/'.$session->get('module').'/department_manage_addProcess.php?address='.$session->get('address'));

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    $types = array(
        'Learning Area' => __('Learning Area'),
        'Administration' => __('Administration'),
    );

    $typesLA = array(
        'Coordinator'           => __('Coordinator'),
        'Assistant Coordinator' => __('Assistant Coordinator'),
        'Teacher (Curriculum)'  => __('Teacher (Curriculum)'),
        'Teacher'               => __('Teacher'),
        'Other'                 => __('Other'),
    );

    $typesAdmin = array(
        'Director'      => __('Director'),
        'Manager'       => __('Manager'),
        'Administrator' => __('Administrator'),
        'Other'         => __('Other'),
    );

    $row = $form->addRow()->addHeading('Basic Details', __('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->required();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(40)->required();

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'));
        $row->addTextField('nameShort')->maxLength(4)->required();

    $row = $form->addRow();
        $row->addLabel('subjectListing', __('Subject Listing'));
        $row->addTextField('subjectListing')->maxLength(255);

    $row = $form->addRow();
       $column = $row->addColumn()->setClass('');
       $column->addLabel('blurb', __('Blurb'));
       $column->addEditor('blurb', $guid);

    $row = $form->addRow();
        $row->addLabel('file', __('Logo'))->description(__('125x125px jpg/png/gif'));
        $row->addFileUpload('file')
            ->accepts('.jpg,.jpeg,.gif,.png');

    $form->addRow()->addHeading('Staff', __('Staff'));

    $row = $form->addRow();
        $row->addLabel('staff', __('Staff'));
        $row->addSelectStaff('staff')->selectMultiple();

    $form->toggleVisibilityByClass('roleLARow')->onSelect('type')->when('Learning Area');

    $row = $form->addRow()->setClass('roleLARow');
        $row->addLabel('roleLA', __('Role'));
        $row->addSelect('roleLA')->fromArray($typesLA);

    $form->toggleVisibilityByClass('roleAdmin')->onSelect('type')->when('Administration');

    $row = $form->addRow()->setClass('roleAdmin');
        $row->addLabel('roleAdmin', __('Role'));
        $row->addSelect('roleAdmin')->fromArray($typesAdmin);

    // Custom Fields
    $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Department', []);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
