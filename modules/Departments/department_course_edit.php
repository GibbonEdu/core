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

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if courseschool year specified
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];

    if ($gibbonDepartmentID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonSchoolYear.name AS year, gibbonDepartment.name AS department, gibbonCourse.name AS course, description, gibbonCourse.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $values = $result->fetch();

            //Get role within learning area
            $role = getRole($_SESSION[$guid]['gibbonPersonID'], $gibbonDepartmentID, $connection2);

            $extra = '';
            if (($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') and $values['gibbonSchoolYearID'] != $_SESSION[$guid]['gibbonSchoolYearID']) {
                $extra = ' '.$values['year'];
            }
            
            $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonCourseID' => $gibbonCourseID];
            
            $page->breadcrumbs
                ->add(__('View All'), 'departments.php')
                ->add($values['department'], 'department.php', $urlParams)
                ->add($values['course'].$extra, 'department_course.php', $urlParams)
                ->add(__('Edit Course'));

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            if ($role != 'Coordinator' and $role != 'Assistant Coordinator' and $role != 'Teacher (Curriculum)') {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {

                $form = Form::create('courseEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/department_course_editProcess.php?gibbonDepartmentID='.$gibbonDepartmentID.'&gibbonCourseID='.$gibbonCourseID);
                
                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                
                $form->addRow()->addHeading(__('Overview'));
                $form->addRow()->addEditor('description', $guid)->setRows(20)->setValue($values['description']);
            
                $row = $form->addRow();
                    $row->addSubmit();
                
                echo $form->getOutput();
            }
        }
    }
}
