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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\CustomFieldHandler;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$makeDepartmentsPublic = $container->get(SettingGateway::class)->getSettingByScope('Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course.php') == false and $makeDepartmentsPublic != 'Y') {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    if ($gibbonDepartmentID == '' or $gibbonCourseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonDepartment.name AS department, gibbonCourse.name, gibbonCourse.description, gibbonSchoolYear.name AS year, gibbonCourse.gibbonSchoolYearID, gibbonCourse.fields FROM gibbonDepartment JOIN gibbonCourse ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            $row = $result->fetch();

            //Get role within learning area
            $role = null;
            if ($session->has('username')) {
                $role = getRole($session->get('gibbonPersonID'), $gibbonDepartmentID, $connection2);
            }

            $extra = '';
            if (($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') and $row['gibbonSchoolYearID'] != $session->get('gibbonSchoolYearID')) {
                $extra = ' '.$row['year'];
            }

            $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID];

            $page->breadcrumbs
                ->add(__('Departments'), $session->has('username') ? 'departments.php' : '/modules/Departments/departments.php')
                ->add($row['department'], $session->has('username') ? 'department.php' : '/modules/Departments/department.php', $urlParams)
                ->add($row['name'].$extra);

            //Print overview
            if ($row['description'] != '' or $role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)') {
                echo '<h2>';
                echo __('Overview');
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)') {
                    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/department_course_edit.php&gibbonCourseID=$gibbonCourseID&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                }
                echo '</h2>';
                echo '<p>';
                echo $row['description'];
                echo '</p>';
            }

            // Custom fields
            $table = DataTable::createDetails('fields');
            $container->get(CustomFieldHandler::class)->addCustomFieldsToTable($table, 'Course', [], $row['fields']);
            echo $table->render([$row]);

            //Print Units
            echo '<h2>';
            echo __('Units');
            echo '</h2>';


                $dataUnit = array('gibbonCourseID' => $gibbonCourseID);
                $sqlUnit = 'SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnit.gibbonCourseID=:gibbonCourseID AND active=\'Y\' ORDER BY ordering, name';
                $resultUnit = $connection2->prepare($sqlUnit);
                $resultUnit->execute($dataUnit);

            while ($rowUnit = $resultUnit->fetch()) {
                echo '<h4>';
                echo $rowUnit['name'];
                echo '</h4>';
                echo '<p>';
                echo $rowUnit['description'];
                if ($rowUnit['attachment'] != '') {
                    echo "<br/><br/><a href='".$session->get('absoluteURL').'/'.$rowUnit['attachment']."'>".__('Download Unit Outline').'</a></li>';
                }
                echo '</p>';
            }

            //Print sidebar
            $sidebarExtra = '';

            if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php')) {
                //Print class list

                    $dataCourse = array('gibbonCourseID' => $gibbonCourseID);
                    $sqlCourse = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID ORDER BY class';
                    $resultCourse = $connection2->prepare($sqlCourse);
                    $resultCourse->execute($dataCourse);

                if ($resultCourse->rowCount() > 0) {
                    $sidebarExtra .= '<div class="column-no-break">';
                    $sidebarExtra .= '<h2>';
                    $sidebarExtra .= __('Class List');
                    $sidebarExtra .= '</h2>';

                    $sidebarExtra .= '<ul>';
                    while ($rowCourse = $resultCourse->fetch()) {
                        $sidebarExtra .= "<li><a href='".$session->get('absoluteURL')."/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=".$rowCourse['gibbonCourseClassID']."'>".$rowCourse['course'].'.'.$rowCourse['class'].'</a></li>';
                    }
                    $sidebarExtra .= '</ul>';
                    $sidebarExtra .= '</div>';

                    $session->set('sidebarExtra', $sidebarExtra);
                }
            }
        }
    }
}
