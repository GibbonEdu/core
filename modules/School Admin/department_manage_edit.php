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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/department_manage.php'>".__($guid, 'Manage Departments')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Department').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    if ($gibbonDepartmentID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('departmentManageRecord', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/department_manage_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&address=".$_SESSION[$guid]['address']);

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
                $row->addTextField('type')->readOnly();

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
                    ->setAttachment('logo', $_SESSION[$guid]['absoluteURL'], $values['logo']);

            $form->addRow()->addHeading(__('Current Staff'));

            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = "SELECT preferredName, surname, gibbonDepartmentStaff.* FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";

            $results = $pdo->executeQuery($data, $sql);

            if ($results->rowCount() == 0) {
                $form->addRow()->addAlert(__('There are no records to display.'), 'error');
            } else {
                $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a member of staff, any unsaved changes to this record will be lost!'))->wrap('<i>', '</i>');

                $table = $form->addRow()->addTable()->addClass('colorOddEven');

                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Role'));
                $header->addContent(__('Action'));

                while ($staff = $results->fetch()) {
                    $row = $table->addRow();
                    $row->addContent(formatName('', $staff['preferredName'], $staff['surname'], 'Staff', true, true));
                    $row->addContent($staff['role']);
                    $row->addContent("<a onclick='return confirm(\"".__($guid, 'Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_manage_edit_staff_deleteProcess.php?address='.$_GET['q'].'&gibbonDepartmentStaffID='.$staff['gibbonDepartmentStaffID']."&gibbonDepartmentID=$gibbonDepartmentID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>");
                }
            }

            $form->addRow()->addHeading(__('New Staff'));

            $row = $form->addRow();
                $row->addLabel('staff', 'Staff');
                $row->addSelectStaff('staff')->selectMultiple();

            if ($values['type'] == 'Learning Area') {
                $row = $form->addRow()->setClass('roleLARow');
                    $row->addLabel('role', 'Role');
                    $row->addSelect('role')->fromArray($typesLA);
            } else {
                $row = $form->addRow()->setClass('roleAdmin');
                    $row->addLabel('role', 'Role');
                    $row->addSelect('role')->fromArray($typesAdmin);
            }

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
