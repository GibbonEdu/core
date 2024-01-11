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
use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Tables\Prefab\ClassGroupTable;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);

$makeDepartmentsPublic = $settingGateway->getSettingByScope('Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
    $currentDate = $_GET['currentDate'] ?? Format::date(date('Y-m-d'));

    if (empty($gibbonCourseClassID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        if (!empty($gibbonDepartmentID)) {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourse.gibbonSchoolYearID,gibbonDepartment.name AS department, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.name AS classLong, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year, gibbonCourseClass.attendance, gibbonCourseClass.fields
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    JOIN gibbonDepartment ON (gibbonDepartment.gibbonDepartmentID=gibbonCourse.gibbonDepartmentID)
                    WHERE gibbonCourseClassID=:gibbonCourseClassID";
        } else {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = "SELECT gibbonCourse.gibbonSchoolYearID, gibbonCourse.name AS courseLong, gibbonCourse.nameShort AS course, gibbonCourseClass.name AS classLong, gibbonCourseClass.nameShort AS class, gibbonCourse.gibbonCourseID, gibbonSchoolYear.name AS year, gibbonCourseClass.attendance, gibbonCourseClass.fields
                    FROM gibbonCourse
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    WHERE gibbonCourseClassID=:gibbonCourseClassID";
        }

        $row = $pdo->selectOne($sql, $data);

        if (empty($row)) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            //Get role within learning area
            $role = null;
            if ($gibbonDepartmentID != '' and ($session->get('username'))) {
                $role = getRole($session->get('gibbonPersonID'), $gibbonDepartmentID, $connection2);
            }

            $extra = '';
            if (($role == 'Coordinator' or $role == 'Assistant Coordinator' or $role == 'Teacher (Curriculum)' or $role == 'Teacher') and $row['gibbonSchoolYearID'] != $session->get('gibbonSchoolYearID')) {
                $extra = ' '.$row['year'];
            }
            if ($gibbonDepartmentID != '') {
                $urlParams = ['gibbonDepartmentID' => $gibbonDepartmentID, 'gibbonCourseID' => $gibbonCourseID];
                $page->breadcrumbs
                    ->add($row['department'], 'department.php', $urlParams)
                    ->add($row['courseLong'].$extra, 'department_course.php', $urlParams)
                    ->add(Format::courseClassName($row['course'], $row['class']));
            } else {
                $page->breadcrumbs
                    ->add(__('Departments'), 'departments.php')
                    ->add(Format::courseClassName($row['course'], $row['class']));
            }

            // CHECK & STORE WHAT TO DISPLAY
            $menuItems = [];

            // Attendance
            if ($row['attendance'] == 'Y' && isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {
                $menuItems[] = [
                    'name' => __('Attendance'),
                    'url'  => './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$gibbonCourseClassID.'&currentDate='.$currentDate,
                    'icon' => 'attendance_large.png',
                ];
            }
            // Planner
            if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
                $menuItems[] = [
                    'name' => __('Planner'),
                    'url'  => './index.php?q=/modules/Planner/planner.php&gibbonCourseClassID='.$gibbonCourseClassID.'&viewBy=class',
                    'icon' => 'planner_large.png',
                ];
            }
            // Markbook
            if (getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2) == 'View Markbook_allClassesAllData') {
                $menuItems[] = [
                    'name' => __('Markbook'),
                    'url'  => './index.php?q=/modules/Markbook/markbook_view.php&gibbonCourseClassID='.$gibbonCourseClassID,
                    'icon' => 'markbook_large.png',
                ];
            }
            // Homework
            if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_deadlines.php')) {
                $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
                $menuItems[] = [
                    'name' => __($homeworkNamePlural),
                    'url'  => './index.php?q=/modules/Planner/planner_deadlines.php&gibbonCourseClassIDFilter='.$gibbonCourseClassID,
                    'icon' => 'homework_large.png',
                ];
            }
            // Internal Assessment
            if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write.php')) {
                $menuItems[] = [
                    'name' => __('Internal Assessment'),
                    'url'  => './index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID='.$gibbonCourseClassID,
                    'icon' => 'internalAssessment_large.png',
                ];
            }

            // Menu Items Table
            if (!empty($menuItems)) {
                $gridRenderer = new GridView($container->get('twig'));
                $table = $container->get(DataTable::class)->setRenderer($gridRenderer);
                $table->setTitle($row['courseLong']." - ".$row['classLong']);
                $table->setDescription(Format::courseClassName($row['course'], $row['class']));

                $table->addMetaData('gridClass', 'rounded-sm bg-gray-100 border py-2');
                $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/3 p-4 text-center');
                $table->addMetaData('hidePagination', true);

                $iconPath = $session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/';
                $table->addColumn('icon')
                    ->format(function ($menu) use ($iconPath) {
                        $img = sprintf('<img src="%1$s" title="%2$s" class="w-24 sm:w-32 px-4 pb-2">', $iconPath.$menu['icon'], $menu['name']);
                        return Format::link($menu['url'], $img);
                    });

                $table->addColumn('name')
                    ->setClass('font-bold text-xs')
                    ->format(function ($menu) {
                        return Format::link($menu['url'], $menu['name']);
                    });

                echo $table->render(new DataSet($menuItems));
            }

            // Custom fields
            $table = DataTable::createDetails('fields');
            $container->get(CustomFieldHandler::class)->addCustomFieldsToTable($table, 'Class', [], $row['fields']);
            echo $table->render([$row]);

            // Participants
            $table = $container->get(ClassGroupTable::class);
            $table->build($session->get('gibbonSchoolYearID'), $gibbonCourseClassID);

            echo $table->getOutput();

            //Print sidebar
            if ($session->get('username')) {
                $sidebarExtra = '';

                //Print related class list
                try {
                    $dataCourse = array('gibbonCourseID' => $row['gibbonCourseID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sqlCourse = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY class';
                    $resultCourse = $connection2->prepare($sqlCourse);
                    $resultCourse->execute($dataCourse);
                } catch (PDOException $e) {
                }

                if ($resultCourse->rowCount() > 0) {
                    $sidebarExtra .= '<div class="column-no-break">';
                    $sidebarExtra .= '<h2>';
                    $sidebarExtra .= __('Related Classes');
                    $sidebarExtra .= '</h2>';

                    $sidebarExtra .= '<ul>';
                    while ($rowCourse = $resultCourse->fetch()) {
                        $sidebarExtra .= "<li><a href='".$session->get('absoluteURL')."/index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID=$gibbonDepartmentID&gibbonCourseID=".$row['gibbonCourseID'].'&gibbonCourseClassID='.$rowCourse['gibbonCourseClassID']."'>".$rowCourse['course'].'.'.$rowCourse['class'].'</a></li>';
                    }
                    $sidebarExtra .= '</ul>';
                    $sidebarExtra .= '</div>';
                }

                //Print list of all classes
                $sidebarExtra .= '<div class="column-no-break">';

                $form = Form::create('classSelect', $session->get('absoluteURL').'/index.php', 'get');
                $form->setTitle(__('Current Classes'));
                $form->setClass('smallIntBorder w-full');

                $form->addHiddenValue('q', '/modules/'.$session->get('module').'/department_course_class.php');

                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
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

                $sidebarExtra .= $form->getOutput();
                $sidebarExtra .= '</div>';

                $session->set('sidebarExtra', $session->get('sidebarExtra'). $sidebarExtra);
            }
        }
    }
}
