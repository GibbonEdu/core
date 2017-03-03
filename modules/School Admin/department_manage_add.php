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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/department_manage.php'>".__($guid, 'Manage Departments')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Learning Area').'</div>';
    echo '</div>';

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

    $row = $form->addRow();
        $row->addLabel('type', 'Type');
        $row->addSelect('type')->fromString('Learning Area, Administration')->isRequired();

    $row = $form->addRow();
        $row->addLabel('name', 'Name');
        $row->addTextField('name')->maxLength(40)->isRequired();

    $row = $form->addRow();
        $row->addLabel('nameShort', 'Short Name');
        $row->addTextField('nameShort')->maxLength(4)->isRequired();

    $row = $form->addRow();
        $row->addLabel('subjectListing', 'Subject Listing');
        $row->addTextField('subjectListing')->maxLength(255);

    $row = $form->addRow();
       $column = $row->addColumn()->setClass('');
       $column->addLabel('blurb', 'Blurb');
       $column->addEditor('blurb', $guid);

    $row = $form->addRow();
        $row->addLabel('file', 'Logo')->description('125x125px jpg/png/gif');
        $row->addFileUpload('file')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->append('<br/><br/>'.getMaxUpload($guid))
            ->addClass('right');

    $row = $form->addRow();
        $row->addLabel('staff', 'Staff')->description('Use Control, Command and/or Shift to select multiple.');
        $row->addSelectStaff('staff')->selectMultiple();

    $form->toggleVisibilityByClass('roleLARow')->onSelect('type')->when('Learning Area');

    $row = $form->addRow()->setClass('roleLARow');
        $row->addLabel('roleLA', 'Role');
        $row->addSelect('roleLA')->fromString('Coordinator, Assistant Coordinator, Teacher (Curriculum), Teacher, Other');

    $form->toggleVisibilityByClass('roleAdmin')->onSelect('type')->when('Administration');

    $row = $form->addRow()->setClass('roleAdmin');
        $row->addLabel('roleAdmin', 'Role');
        $row->addSelect('roleAdmin')->fromString('Director, Manager, Administrator, Other');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
