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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_class_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $page->breadcrumbs
        ->add(__('Manage Courses & Classes'), 'course_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Course & Classes'), 'course_manage_edit.php', ['gibbonCourseID' => $gibbonCourseID, 'gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Add Class'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Timetable Admin/course_manage_class_edit.php&gibbonCourseClassID='.$_GET['editID'].'&gibbonCourseID='.$gibbonCourseID.'&gibbonSchoolYearID='.$gibbonSchoolYearID;
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if ($gibbonSchoolYearID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
			$values = $result->fetch(); 

			$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/course_manage_class_addProcess.php');

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
			$form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
			
			$row = $form->addRow();
				$row->addLabel('schoolYearName', __('School Year'));
				$row->addTextField('schoolYearName')->required()->readonly()->setValue($values['yearName']);
			
			$row = $form->addRow();
				$row->addLabel('courseName', __('Course'));
				$row->addTextField('courseName')->required()->readonly()->setValue($values['courseName']);

			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Must be unique for this course.'));
				$row->addTextField('name')->required()->maxLength(30);
			
			$row = $form->addRow();
				$row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique for this course.'));
				$row->addTextField('nameShort')->required()->maxLength(8);

			$row = $form->addRow();
				$row->addLabel('reportable', __('Reportable?'))->description(__('Should this class show in reports?'));
				$row->addYesNo('reportable');

			if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
				$row = $form->addRow();
				$row->addLabel('attendance', __('Track Attendance?'))->description(__('Should this class allow attendance to be taken?'));
				$row->addYesNo('attendance');
			}

			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();
		
			echo $form->getOutput();
        }
    }
}
