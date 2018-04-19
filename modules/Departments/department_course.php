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
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course.php') == false and $makeDepartmentsPublic != 'Y') {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    $gibbonCourseID = $_GET['gibbonCourseID'];
    if ($gibbonDepartmentID == '' or $gibbonCourseID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT gibbonDepartment.name AS department, gibbonCourse.name, gibbonCourse.description, gibbonSchoolYear.name AS year, gibbonCourse.gibbonSchoolYearID FROM gibbonDepartment JOIN gibbonCourse ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            //Get role within learning area
            $role = null;
            if (isset($_SESSION[$guid]['username'])) {
                $role = getRole($_SESSION[$guid]['gibbonPersonID'], $gibbonDepartmentID, $connection2);
            }

            $extra = '';
            if (($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') and $row['gibbonSchoolYearID'] != $_SESSION[$guid]['gibbonSchoolYearID']) {
                $extra = ' '.$row['year'];
            }
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/departments.php'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/departments.php'>".__($guid, 'View All')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/department.php&gibbonDepartmentID='.$_GET['gibbonDepartmentID']."'>".$row['department']."</a> > </div><div class='trailEnd'>".$row['name']."$extra</div>";
            echo '</div>';

            //Print overview
            if ($row['description'] != '' or $role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)') {
                echo '<h2>';
                echo __($guid, 'Overview');
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/department_course_edit.php&gibbonCourseID=$gibbonCourseID&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                }
                echo '</h2>';
                echo '<p>';
                echo $row['description'];
                echo '</p>';
            }

            //Print Units
            echo '<h2>';
            echo __($guid, 'Units');
            echo '</h2>';

            try {
                $dataUnit = array('gibbonCourseID' => $gibbonCourseID);
                $sqlUnit = 'SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnit.gibbonCourseID=:gibbonCourseID AND active=\'Y\' ORDER BY ordering, name';
                $resultUnit = $connection2->prepare($sqlUnit);
                $resultUnit->execute($dataUnit);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            while ($rowUnit = $resultUnit->fetch()) {
                echo '<h4>';
                echo $rowUnit['name'];
                echo '</h4>';
                echo '<p>';
                echo $rowUnit['description'];
                if ($rowUnit['attachment'] != '') {
                    echo "<br/><br/><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowUnit['attachment']."'>".__($guid, 'Download Unit Outline').'</a></li>';
                }
                echo '</p>';
            }

            try {
                $dataHooks = array();
                $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit'";
                $resultHooks = $connection2->prepare($sqlHooks);
                $resultHooks->execute($dataHooks);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            while ($rowHooks = $resultHooks->fetch()) {
                $hookOptions = unserialize($rowHooks['options']);
                if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                    try {
                        $dataHookUnits = array('gibbonCourseID' => $gibbonCourseID);
                        $sqlHookUnits = 'SELECT DISTINCT '.$hookOptions['unitTable'].'.'.$hookOptions['unitNameField'].', '.$hookOptions['unitTable'].'.'.$hookOptions['unitDescriptionField'].' FROM '.$hookOptions['unitTable'].' JOIN '.$hookOptions['classLinkTable'].' ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitIDField'].'='.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkJoinFieldUnit'].') WHERE '.$hookOptions['classLinkTable'].'.'.$hookOptions['unitCourseIDField'].'=:gibbonCourseID ORDER BY '.$hookOptions['classLinkTable'].'.'.$hookOptions['classLinkIDField'];
                        $resultHookUnits = $connection2->prepare($sqlHookUnits);
                        $resultHookUnits->execute($dataHookUnits);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    while ($rowHookUnits = $resultHookUnits->fetch()) {
                        echo '<h4>';
                        echo $rowHookUnits[$hookOptions['unitNameField']];
                        if ($rowHooks['name'] != '') {
                            echo "<br/><span style='font-size: 75%; font-style: italic; font-weight: normal'>".$rowHooks['name'].' Unit</span>';
                        }
                        echo '</h4>';
                        echo '<p>';
                        echo $rowHookUnits[$hookOptions['unitDescriptionField']];
                        echo '</p>';
                    }
                }
            }

            //Print sidebar
            $_SESSION[$guid]['sidebarExtra'] = '';

            if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php')) {
                //Print class list
                try {
                    $dataCourse = array('gibbonCourseID' => $gibbonCourseID);
                    $sqlCourse = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID ORDER BY class';
                    $resultCourse = $connection2->prepare($sqlCourse);
                    $resultCourse->execute($dataCourse);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultCourse->rowCount() > 0) {
                    $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'<h2>';
                    $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].__($guid, 'Class List');
                    $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'</h2>';

                    $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'<ul>';
                    while ($rowCourse = $resultCourse->fetch()) {
                        $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra']."<li><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=".$rowCourse['gibbonCourseClassID']."'>".$rowCourse['course'].'.'.$rowCourse['class'].'</a></li>';
                    }
                    $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'</ul>';
                }
            }
        }
    }
}
