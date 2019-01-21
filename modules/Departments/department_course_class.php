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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    $gibbonCourseID = null;
    if (isset($_GET['gibbonCourseID'])) {
        $gibbonCourseID = $_GET['gibbonCourseID'];
    }
    $gibbonDepartmentID = null;
    if (isset($_GET['gibbonDepartmentID'])) {
        $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    }
    if ($gibbonCourseClassID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $proceed = false;
        if ($gibbonDepartmentID != '') {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.gibbonSchoolYearID,gibbonDepartment.name AS department, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year, gibbonCourseClass.attendance FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The specified record does not exist.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                $proceed = true;
            }
        } else {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.gibbonSchoolYearID, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year, gibbonCourseClass.attendance FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The specified record does not exist.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                $proceed = true;
            }
        }

        if ($proceed == true) {
            //Get role within learning area
            $role = null;
            if ($gibbonDepartmentID != '' and isset($_SESSION[$guid]['username'])) {
                $role = getRole($_SESSION[$guid]['gibbonPersonID'], $gibbonDepartmentID, $connection2);
            }

            $extra = '';
            if (($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') and $row['gibbonSchoolYearID'] != $_SESSION[$guid]['gibbonSchoolYearID']) {
                $extra = ' '.$row['year'];
            }
            if ($gibbonDepartmentID != '') {
                
                $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonCourseID' => $gibbonCourseID];
                $page->breadcrumbs
                    ->add(__('View All'), 'departments.php')
                    ->add($row['department'], 'department.php', $urlParams)
                    ->add($row['courseLong'].$extra, 'department_course.php', $urlParams)
                    ->add(Format::courseClassName($row['course'], $row['class']));
            } else {
                $page->breadcrumbs
                    ->add(__('View All'), 'departments.php')
                    ->add(Format::courseClassName($row['course'], $row['class']));
            }            
            
            echo '<h2>';
            echo Format::courseClassName($row['course'], $row['class']);
            echo '<br/><small><em>'.__('Course').': '.$row['courseLong'].'</em></small>';
            echo '</h2>';

            //CHECK & STORE WHAT TO DISPLAY
            $menu = array();
            $menuCount = 0;

            // Attendance
            if ($row['attendance'] == 'Y' && isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
                $menu[$menuCount][0]="Attendance" ;
                $menu[$menuCount][1]="<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-bottom: 10px' title='" . __('Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance_large.png'/><br/><b>" . __('Attendance') . "</b></a>" ;
                $menuCount++ ;
            }
            //Planner
            if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                $menu[$menuCount][0] = 'Planner';
                $menu[$menuCount][1] = "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php&gibbonCourseClassID=$gibbonCourseClassID&viewBy=class'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='".__('Planner')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner_large.png'/><br/><b>".__('Planner').'</b></a>';
                ++$menuCount;
            }
            //Markbook
            if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                $menu[$menuCount][0] = 'Markbook';
                $menu[$menuCount][1] = "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='".__('Markbook')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook_large.png'/><br/><b>".__('Markbook').'</b></a>';
                ++$menuCount;
            }
            //Homework
            if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_deadlines.php')) {
                $menu[$menuCount][0] = 'Homework';
                $menu[$menuCount][1] = "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter=$gibbonCourseClassID'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='".__('Markbook')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/homework_large.png'/><br/><b>".__('Homework').'</b></a>';
                ++$menuCount;
            }
            //Internal Assessment
            if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write.php')) {
                $menu[$menuCount][0] = 'Internal Assessment';
                $menu[$menuCount][1] = "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-bottom: 10px'  style='margin-left: 5px' title='".__('Internal Assessment')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/internalAssessment_large.png'/><br/><b>".__('Internal Assessment').'</b></a>';
                ++$menuCount;
            }

            if ($menuCount < 1) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo "<table class='smallIntBorder' cellspacing='0' style='width:100%'>";
                $count = 0;
                $columns = 3;

                foreach ($menu as $menuEntry) {
                    if ($count % $columns == 0) {
                        echo '<tr>';
                    }
                    echo "<td style='padding-top: 15px!important; padding-bottom: 15px!important; width:30%; text-align: center; vertical-align: top'>";
                    echo $menuEntry[1];
                    echo '</td>';

                    if ($count % $columns == ($columns - 1)) {
                        echo '</tr>';
                    }
                    ++$count;
                }

                if ($count % $columns != 0) {
                    for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                        echo '<td></td>';
                    }
                    echo '</tr>';
                }

                echo '</table>';

                //PARTICIPANTS
                echo '<a name=\'participants\'></a>';
                echo '<h3 class=\'bigTop\'>';
                    echo __('Participants');
                echo '</h3>';
                if (getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2) == 'View Student Profile_full') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/department_course_classExport.php?gibbonCourseClassID=$gibbonCourseClassID&address=".$_GET['q']."'>".__('Export')." <img title='".__('Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                    echo '</div>';
                }

                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __('The specified record does not exist.');
                    echo '</div>';
                } else {
                    printClassGroupTable($guid, $gibbonCourseClassID, 4, $connection2);
                }
            }

            //Print sidebar
            if (isset($_SESSION[$guid]['username'])) {
                $_SESSION[$guid]['sidebarExtra'] = '';

                //Print related class list
                try {
                    $dataCourse = array('gibbonCourseID' => $row['gibbonCourseID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlCourse = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY class';
                    $resultCourse = $connection2->prepare($sqlCourse);
                    $resultCourse->execute($dataCourse);
                } catch (PDOException $e) {
                    $_SESSION[$guid]['sidebarExtra'] .= "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultCourse->rowCount() > 0) {
                    $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                    $_SESSION[$guid]['sidebarExtra'] .= __('Related Classes');
                    $_SESSION[$guid]['sidebarExtra'] .= '</h2>';

                    $_SESSION[$guid]['sidebarExtra'] .= '<ul>';
                    while ($rowCourse = $resultCourse->fetch()) {
                        $_SESSION[$guid]['sidebarExtra'] .= "<li><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=".$row['gibbonCourseID'].'&gibbonCourseClassID='.$rowCourse['gibbonCourseClassID']."'>".$rowCourse['course'].'.'.$rowCourse['class'].'</a></li>';
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
                }

                //Print list of all classes
                $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                $_SESSION[$guid]['sidebarExtra'] .= __('Current Classes');
                $_SESSION[$guid]['sidebarExtra'] .= '</h2>';

                $form = Form::create('classSelect', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
                $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/department_course_class.php');
                
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name 
                        FROM gibbonCourse 
                        JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
                        WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                        ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";

                $row = $form->addRow();
                    $row->addSelect('gibbonCourseClassID')
                        ->fromQuery($pdo, $sql, $data)
                        ->selected($gibbonCourseClassID)
                        ->placeholder()
                        ->setClass('fullWidth');
                    $row->addSubmit(__('Go'));
                
                $_SESSION[$guid]['sidebarExtra'] .= $form->getOutput();
            }
        }
    }
}
