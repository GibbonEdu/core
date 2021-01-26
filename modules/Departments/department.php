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
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$makeDepartmentsPublic = getSettingByScope($connection2, 'Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/department.php') == false and $makeDepartmentsPublic != 'Y') {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'];
    if ($gibbonDepartmentID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            //Get role within learning area
            $role = null;
            if (isset($_SESSION[$guid]['username'])) {
                $role = getRole($_SESSION[$guid]['gibbonPersonID'], $gibbonDepartmentID, $connection2);
            }

            $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID];
            
            $page->breadcrumbs
                ->add(__('View All'), 'departments.php')
                ->add($row['name'], 'departments.php', $urlParams);

            //Print overview
            if ($row['blurb'] != '' or $role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                echo '<h2>';
                echo __('Overview');
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                }
                echo '</h2>';
                echo '<p>';
                echo $row['blurb'];
                echo '</p>';
            }

            //Print staff
            $dataStaff = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sqlStaff = "SELECT gibbonPerson.gibbonPersonID, gibbonDepartmentStaff.role, title, surname, preferredName, image_240, gibbonStaff.jobTitle FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonDepartmentID=:gibbonDepartmentID ORDER BY role, surname, preferredName";
           
            $staff = $pdo->select($sqlStaff, $dataStaff)->toDataSet();

            // Data Table
            $gridRenderer = new GridView($container->get('twig'));
            $table = $container->get(DataTable::class)->setRenderer($gridRenderer);
            $table->setTitle(__('Staff'));
            $table->addMetaData('gridClass', 'rounded-sm bg-blue-100 border py-2');
            $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/5 my-2 text-center');

            $canViewProfile = isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php');
            $table->addColumn('image_240')
                ->format(function ($person) use ($canViewProfile) {
                    $userPhoto = Format::userPhoto($person['image_240'], 'sm', '');
                    $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];

                    return $canViewProfile
                        ? Format::link($url, $userPhoto)
                        : $userPhoto;
                });
            
            $table->addColumn('name')
                ->setClass('text-xs font-bold mt-1')
                ->format(function ($person) use ($canViewProfile) {
                    $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff');
                    $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                    return $canViewProfile
                        ? Format::link($url, $name)
                        : $name;
                });

            $table->addColumn('jobTitle')
                ->setClass('text-xs text-gray-600 italic leading-snug')
                ->format(function ($person) {
                    return !empty($person['jobTitle']) ? $person['jobTitle'] : __($person['role']);
                });

            echo $table->render($staff);


            //Print sidebar
            $sidebarExtra = '';

            //Print subject list
            if ($row['subjectListing'] != '') {
                $sidebarExtra .= '<div class="column-no-break">';
                $sidebarExtra .= '<h4>';
                $sidebarExtra .= __('Subject List');
                $sidebarExtra .= '</h4>';

                $sidebarExtra .= '<ul>';
                $subjects = explode(',', $row['subjectListing']);
                for ($i = 0;$i < count($subjects);++$i) {
                    $sidebarExtra .= '<li>'.$subjects[$i].'</li>';
                }
                $sidebarExtra .= '</ul>';
                $sidebarExtra .= '</div>';
            }

            //Print current course list
            
                $dataCourse = array('gibbonDepartmentID' => $gibbonDepartmentID);
                $sqlCourse = "SELECT gibbonCourse.* FROM gibbonCourse 
                    JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) 
                    WHERE gibbonDepartmentID=:gibbonDepartmentID 
                    AND gibbonYearGroupIDList <> '' 
                    AND gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') 
                    GROUP BY gibbonCourse.gibbonCourseID 
                    ORDER BY nameShort, name";
                $resultCourse = $connection2->prepare($sqlCourse);
                $resultCourse->execute($dataCourse);

            if ($resultCourse->rowCount() > 0) {
                $sidebarExtra .= '<div class="column-no-break">';
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)') {
                    $sidebarExtra .= '<h4>';
                    $sidebarExtra .= __('Current Courses');
                    $sidebarExtra .= '</h4>';
                } else {
                    $sidebarExtra .= '<h4>';
                    $sidebarExtra .= __('Course List');
                    $sidebarExtra .= '</h4>';
                }

                $sidebarExtra .= '<ul>';
                while ($rowCourse = $resultCourse->fetch()) {
                    $sidebarExtra .= "<li><a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=".$rowCourse['gibbonCourseID']."'>".$rowCourse['nameShort']."</a> <span style='font-size: 85%; font-style: italic'>".$rowCourse['name'].'</span></li>';
                }
                $sidebarExtra .= '</ul>';
                $sidebarExtra .= '</div>';
            }

            //Print other courses
            if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') {
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

                if (!empty($courses)) {
                    $form = Form::create('courseSelect', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
                    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/department_course.php');
                    $form->addHiddenValue('gibbonDepartmentID', $gibbonDepartmentID);

                    $row = $form->addRow()->addClass('items-center');
                        $row->addSelect('gibbonCourseID')
                            ->fromArray($courses)
                            ->placeholder()
                            ->setClass('w-32 float-none');
                    $row->addSubmit(__('Go'));

                    $sidebarExtra .= '<div class="column-no-break">';
                    $sidebarExtra .= '<h4>';
                    $sidebarExtra .= __('Non-Current Courses');
                    $sidebarExtra .= '</h4>';
                    
                    $sidebarExtra .= $form->getOutput();
                    $sidebarExtra .= '</div>';
                }
            }

            //Print useful reading
            
                $dataReading = array('gibbonDepartmentID' => $gibbonDepartmentID);
                $sqlReading = 'SELECT * FROM gibbonDepartmentResource WHERE gibbonDepartmentID=:gibbonDepartmentID ORDER BY name';
                $resultReading = $connection2->prepare($sqlReading);
                $resultReading->execute($dataReading);

            if ($resultReading->rowCount() > 0 or $role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                $sidebarExtra .= '<div class="column-no-break">';
                $sidebarExtra .= '<h4>';
                $sidebarExtra .= __('Useful Reading');
                if ($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Director' or $role == 'Manager') {
                    $sidebarExtra .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/department_edit.php&gibbonDepartmentID=$gibbonDepartmentID'><img style='margin-left: 5px' title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                }
                $sidebarExtra .= '</h4>';

                $sidebarExtra .= '<ul>';
                while ($rowReading = $resultReading->fetch()) {
                    if ($rowReading['type'] == 'Link') {
                        $sidebarExtra .= "<li><a target='_blank' href='".$rowReading['url']."'>".$rowReading['name'].'</a></li>';
                    } else {
                        $sidebarExtra .= "<li><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowReading['url']."'>".$rowReading['name'].'</a></li>';
                    }
                }
                $sidebarExtra .= '</ul>';
                $sidebarExtra .= '</div>';
            }

            $_SESSION[$guid]['sidebarExtra'] = $sidebarExtra;
        }
    }
}
