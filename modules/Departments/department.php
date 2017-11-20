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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

$makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/department.php') == false and $makeDepartmentsPublic != 'Y') {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    if ($gibbonDepartmentID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID';
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

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/departments.php'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/departments.php'>".__($guid, 'View All')."</a> > </div><div class='trailEnd'>".$row['name'].'</div>';
            echo '</div>';

            //Print overview
            if ($row['blurb'] != '' or $role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                echo '<h2>';
                echo __($guid, 'Overview');
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                }
                echo '</h2>';
                echo '<p>';
                echo $row['blurb'];
                echo '</p>';
            }

            //Print staff
            try {
                $dataStaff = array('gibbonDepartmentID' => $gibbonDepartmentID);
                $sqlStaff = "SELECT gibbonPerson.gibbonPersonID, gibbonDepartmentStaff.role, title, surname, preferredName, image_240, gibbonStaff.jobTitle FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonDepartmentID=:gibbonDepartmentID ORDER BY role, surname, preferredName";
                $resultStaff = $connection2->prepare($sqlStaff);
                $resultStaff->execute($dataStaff);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultStaff->rowCount() > 0) {
                echo '<h2>';
                echo __($guid, 'Staff');
                echo '</h2>';
                echo "<table class='noIntBorder' cellspacing='0' style='width:100%; margin-top: 20px'>";
                $count = 0;
                $columns = 5;

                while ($rowStaff = $resultStaff->fetch()) {
                    if ($count % $columns == 0) {
                        echo '<tr>';
                    }
                    echo "<td style='width:20%; text-align: center; vertical-align: top'>";
                    if ($rowStaff['image_240'] == '' or file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowStaff['image_240']) == false) {
                        echo "<img style='height: 100px; width: 75px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/anonymous_75.jpg'/><br/>";
                    } else {
                        echo "<img style='height: 100px; width: 75px' class='user' src='".$_SESSION[$guid]['absoluteURL'].'/'.$rowStaff['image_240']."'/><br/>";
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                        echo "<div style='padding-top: 5px'><b><a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowStaff['gibbonPersonID']."'>".formatName($rowStaff['title'], $rowStaff['preferredName'], $rowStaff['surname'], 'Staff').'</a></b><br/><i>';
                    } else {
                        echo "<div style='padding-top: 5px'><b>".formatName($rowStaff['title'], $rowStaff['preferredName'], $rowStaff['surname'], 'Staff').'</b><br/><i>';
                    }
                    if ($rowStaff['jobTitle'] != '') {
                        echo $rowStaff['jobTitle'];
                    } else {
                        echo $rowStaff['role'];
                    }
                    echo '</i><br/></div>';
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

            //Print sidebar
            $_SESSION[$guid]['sidebarExtra'] = '';

            //Print subject list
            if ($row['subjectListing'] != '') {
                $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Subject List');
                $_SESSION[$guid]['sidebarExtra'] .= '</h2>';

                $_SESSION[$guid]['sidebarExtra'] .= '<ul>';
                $subjects = explode(',', $row['subjectListing']);
                for ($i = 0;$i < count($subjects);++$i) {
                    $_SESSION[$guid]['sidebarExtra'] .= '<li>'.$subjects[$i].'</li>';
                }
                $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
            }

            //Print current course list
            try {
                $dataCourse = array('gibbonDepartmentID' => $gibbonDepartmentID);
                $sqlCourse = "SELECT * FROM gibbonCourse WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY nameShort, name";
                $resultCourse = $connection2->prepare($sqlCourse);
                $resultCourse->execute($dataCourse);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultCourse->rowCount() > 0) {
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)') {
                    $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                    $_SESSION[$guid]['sidebarExtra'] .= 'Current Courses';
                    $_SESSION[$guid]['sidebarExtra'] .= '</h2>';
                } else {
                    $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                    $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Course List');
                    $_SESSION[$guid]['sidebarExtra'] .= '</h2>';
                }

                $_SESSION[$guid]['sidebarExtra'] .= '<ul>';
                while ($rowCourse = $resultCourse->fetch()) {
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=".$rowCourse['gibbonCourseID']."'>".$rowCourse['nameShort']."</a> <span style='font-size: 85%; font-style: italic'>".$rowCourse['name'].'</span></li>';
                }
                $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
            }

            //Print other courses
            if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') {
                $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Non-Current Courses');
                $_SESSION[$guid]['sidebarExtra'] .= '</h2>';

                $form = Form::create('courseSelect', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
                $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/department_course.php');
                $form->addHiddenValue('gibbonDepartmentID', $gibbonDepartmentID);
                
                $data = array('gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonSchoolYear.name AS year, gibbonCourse.gibbonCourseID as value, gibbonCourse.name AS name 
                        FROM gibbonCourse 
                        JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
                        WHERE gibbonDepartmentID=:gibbonDepartmentID 
                        AND NOT gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID 
                        ORDER BY sequenceNumber, gibbonCourse.nameShort, name";
                $result = $pdo->executeQuery($data, $sql);

                $courses = ($result->rowCount() > 0)? $result->fetchAll() : array();
                $courses = array_reduce($courses, function($carry, $item) {
                    $carry[$item['year']][$item['value']] = $item['name'];
                    return $carry;
                }, array());

                $row = $form->addRow();
                    $row->addSelect('gibbonCourseID')
                        ->fromArray($courses)
                        ->placeholder()
                        ->setClass('fullWidth');
                    $row->addSubmit(__('Go'));
                
                $_SESSION[$guid]['sidebarExtra'] .= $form->getOutput();
            }

            //Print useful reading
            try {
                $dataReading = array('gibbonDepartmentID' => $gibbonDepartmentID);
                $sqlReading = 'SELECT * FROM gibbonDepartmentResource WHERE gibbonDepartmentID=:gibbonDepartmentID ORDER BY name';
                $resultReading = $connection2->prepare($sqlReading);
                $resultReading->execute($dataReading);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultReading->rowCount() > 0 or $role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                $_SESSION[$guid]['sidebarExtra'] .= '<h2>';
                $_SESSION[$guid]['sidebarExtra'] .= __($guid, 'Useful Reading');
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                    $_SESSION[$guid]['sidebarExtra'] .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                }
                $_SESSION[$guid]['sidebarExtra'] .= '</h2>';

                $_SESSION[$guid]['sidebarExtra'] .= '<ul>';
                while ($rowReading = $resultReading->fetch()) {
                    if ($rowReading['type'] == 'Link') {
                        $_SESSION[$guid]['sidebarExtra'] .= "<li><a target='_blank' href='".$rowReading['url']."'>".$rowReading['name'].'</a></li>';
                    } else {
                        $_SESSION[$guid]['sidebarExtra'] .= "<li><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowReading['url']."'>".$rowReading['name'].'</a></li>';
                    }
                }
                $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
            }
        }
    }
}
