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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $page->breadcrumbs
        ->add(__('Manage Courses & Classes'), 'course_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Add Course'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Timetable Admin/course_manage_edit.php&gibbonCourseID='.$_GET['editID'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];

    if ($gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
			$schoolYear = $result->fetch(); 
			
			$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/course_manage_addProcess.php');
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
			
			$row = $form->addRow();
				$row->addLabel('schoolYearName', __('School Year'));
				$row->addTextField('schoolYearName')->required()->readonly()->setValue($schoolYear['name']);
			
			$sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
			$row = $form->addRow();
				$row->addLabel('gibbonDepartmentID', __('Learning Area'));
				$row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql)->placeholder();
			
			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
				$row->addTextField('name')->required()->maxLength(60);
			
			$row = $form->addRow();
				$row->addLabel('nameShort', __('Short Name'));
				$row->addTextField('nameShort')->required()->maxLength(12);
			
			$row = $form->addRow();
				$row->addLabel('orderBy', __('Order'))->description(__('May be used to adjust arrangement of courses in reports.'));
				$row->addNumber('orderBy')->maxLength(3);
			
			$row = $form->addRow();
				$column = $row->addColumn('blurb');
				$column->addLabel('description', __('Blurb'));
				$column->addEditor('description', $guid)->setRows(20);
			
			$row = $form->addRow();
				$row->addLabel('map', __('Include In Curriculum Map'));
				$row->addYesNo('map')->required();
			
			$row = $form->addRow();
				$row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Enrolable year groups.'));
				$row->addCheckboxYearGroup('gibbonYearGroupIDList');
			
			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();
			
			echo $form->getOutput();
        }
    }
}
