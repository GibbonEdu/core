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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $page->breadcrumbs
        ->add(__('Manage Departments'), 'department_manage.php')
        ->add(__('Add Department'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/department_manage_edit.php&gibbonDepartmentID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('departmentManageRecord', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_manage_addProcess.php?address='.$_SESSION[$guid]['address']);

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

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

    $row = $form->addRow();
        $row->addLabel('type', 'Type');
        $row->addSelect('type')->fromArray($types)->isRequired();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(40)->isRequired();

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'));
        $row->addTextField('nameShort')->maxLength(4)->isRequired();

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

    $row = $form->addRow();
        $row->addLabel('staff', __('Staff'));
        $row->addSelectStaff('staff')->selectMultiple();

    $form->toggleVisibilityByClass('roleLARow')->onSelect('type')->when('Learning Area');

    $row = $form->addRow()->setClass('roleLARow');
        $row->addLabel('roleLA', 'Role');
        $row->addSelect('roleLA')->fromArray($typesLA);

    $form->toggleVisibilityByClass('roleAdmin')->onSelect('type')->when('Administration');

    $row = $form->addRow()->setClass('roleAdmin');
        $row->addLabel('roleAdmin', 'Role');
        $row->addSelect('roleAdmin')->fromArray($typesAdmin);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
