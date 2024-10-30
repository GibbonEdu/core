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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if courseschool year specified
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';

    if ($gibbonDepartmentID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonSchoolYear.name AS year, gibbonDepartment.name AS department, gibbonCourse.name AS course, description, gibbonCourse.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $values = $result->fetch();

            //Get role within learning area
            $role = getRole($session->get('gibbonPersonID'), $gibbonDepartmentID, $connection2);

            $extra = '';
            if (($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') and $values['gibbonSchoolYearID'] != $session->get('gibbonSchoolYearID')) {
                $extra = ' '.$values['year'];
            }

            $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonCourseID' => $gibbonCourseID];

            $page->breadcrumbs
                ->add($values['department'], 'department.php', $urlParams)
                ->add($values['course'].$extra, 'department_course.php', $urlParams)
                ->add(__('Edit Course'));

            if ($role != 'Coordinator' and $role != 'Assistant Coordinator' and $role != 'Teacher (Curriculum)') {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {

                $form = Form::create('courseEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/department_course_editProcess.php?gibbonDepartmentID='.$gibbonDepartmentID.'&gibbonCourseID='.$gibbonCourseID);

                $form->addHiddenValue('address', $session->get('address'));

                $form->addRow()->addHeading('Overview', __('Overview'));
                $form->addRow()->addEditor('description', $guid)->setRows(20)->setValue($values['description']);

                $row = $form->addRow();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
