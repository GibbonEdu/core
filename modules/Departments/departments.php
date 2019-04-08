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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/departments.php') == false and $makeDepartmentsPublic != 'Y') {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $page->breadcrumbs->add(__('View All'));

    $departments = false;

    //LEARNING AREAS
    try {
        $dataLA = array();
        $sqlLA = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $resultLA = $connection2->prepare($sqlLA);
        $resultLA->execute($dataLA);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultLA->rowCount() > 0) {
        $departments = true;
        echo '<h2>';
        echo __('Learning Areas');
        echo '</h2>';
        echo "<table class='blank' cellspacing='0' style='width:100%; margin-top: 20px'>";
        $count = 0;
        $columns = 3;

        while ($rowLA = $resultLA->fetch()) {
            if ($count % $columns == 0) {
                echo '<tr>';
            }
            echo "<td style='width:33%; text-align: center; vertical-align: top'>";
            if ($rowLA['logo'] == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowLA['logo']) == false) {
                echo "<img style='height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg'/><br/>";
            } else {
                echo "<img style='height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/'.$rowLA['logo']."'/><br/>";
            }
            echo "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Departments/department.php&gibbonDepartmentID=".$rowLA['gibbonDepartmentID']."'>".$rowLA['name'].'</a><br/><br/></div>';
            echo '</td>';

            if ($count % $columns == ($columns - 1)) {
                echo '</tr>';
            }
            ++$count;
        }

        for ($i = 0;$i < $columns - ($count % $columns);++$i) {
            echo '<td></td>';
        }

        if ($count % $columns != 0) {
            echo '</tr>';
        }
        echo '</table>';
    }

    //ADMINISTRATION
    try {
        $dataLA = array();
        $sqlLA = "SELECT * FROM gibbonDepartment WHERE type='Administration' ORDER BY name";
        $resultLA = $connection2->prepare($sqlLA);
        $resultLA->execute($dataLA);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($resultLA->rowCount() > 0) {
        $departments = true;
        echo '<h2>';
        echo __('Administration');
        echo '</h2>';
        echo "<table class='blank' cellspacing='0' style='width:100%; margin-top: 20px'>";
        $count = 0;
        $columns = 3;

        while ($rowLA = $resultLA->fetch()) {
            if ($count % $columns == 0) {
                echo '<tr>';
            }
            echo "<td style='width:33%; text-align: center; vertical-align: top'>";
            if ($rowLA['logo'] == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowLA['logo']) == false) {
                echo "<img style='height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_125.jpg'/><br/>";
            } else {
                echo "<img style='height: 125px; width: 125px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/'.$rowLA['logo']."'/><br/>";
            }
            echo "<div style='padding-top: 5px'><b><a href='index.php?q=/modules/Departments/department.php&gibbonDepartmentID=".$rowLA['gibbonDepartmentID']."'>".$rowLA['name'].'</a><br/><br/></div>';
            echo '</td>';

            if ($count % $columns == ($columns - 1)) {
                echo '</tr>';
            }
            ++$count;
        }

        for ($i = 0;$i < $columns - ($count % $columns);++$i) {
            echo '<td></td>';
        }

        if ($count % $columns != 0) {
            echo '</tr>';
        }
        echo '</table>';
    }

    if ($departments == false) {
        echo "<div class='warning'>";
        echo __('There are no records to display.');
        echo '</div>';
    }

    if (isset($_SESSION[$guid]['username'])) {
        //Print sidebar
        $sidebarExtra = '';

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE \'% - Left%\' ORDER BY course, class';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() > 0) {
            $sidebarExtra .= '<div class="column-no-break">';
            $sidebarExtra .= "<h2 class='sidebar'>";
            $sidebarExtra .= __('My Classes');
            $sidebarExtra .= '</h2>';

            $sidebarExtra .= '<ul>';
            while ($row = $result->fetch()) {
                $sidebarExtra .= "<li><a href='index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=".$row['gibbonCourseClassID']."'>".$row['course'].'.'.$row['class'].'</a></li>';
            }
            $sidebarExtra .= '</ul>';
            $sidebarExtra .= '</div>';

            $_SESSION[$guid]['sidebarExtra'] = $sidebarExtra;
        }
    }
}
