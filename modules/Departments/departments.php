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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\Departments\DepartmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$makeDepartmentsPublic = $container->get(SettingGateway::class)->getSettingByScope('Departments', 'makeDepartmentsPublic');
if (isActionAccessible($guid, $connection2, '/modules/Departments/departments.php') == false and $makeDepartmentsPublic != 'Y') {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('Departments'), $session->has('username') ? 'departments.php' : '/modules/Departments/departments.php')
        ->add(__('View All'));

    $departmentGateway = $container->get(DepartmentGateway::class);

    // QUERY
    $criteria = $departmentGateway->newQueryCriteria()
        ->sortBy(['sequenceNumber', 'name'])
        ->pageSize(0)
        ->fromPOST('departments');

    // Data Table
    $gridRenderer = new GridView($container->get('twig'));
    $table = $container->get(DataTable::class)->setRenderer($gridRenderer);
    $table->getRenderer()->setCriteria($criteria);
    $table->setTitle(__('Departments'));

    $table->addColumn('logo')
        ->format(function ($department) {
            $departmentPhoto = Format::userPhoto($department['logo'], 125, 'w-20 h-20 sm:w-32 sm:h-32 p-1');
            $url = "./index.php?q=/modules/Departments/department.php&gibbonDepartmentID=".$department['gibbonDepartmentID'];
            return Format::link($url, $departmentPhoto);
        });

    $table->addColumn('name')
        ->setClass('text-xs font-bold mt-1 mb-4')
        ->format(function ($department) {
            $url = "./index.php?q=/modules/Departments/department.php&gibbonDepartmentID=".$department['gibbonDepartmentID'];
            return Format::link($url, $department['name']);
        });

    // Learning Areas
    $learningAreas = $departmentGateway->queryDepartments($criteria, 'Learning Area');

    if (count($learningAreas) > 0) {
        $tableLA = clone $table;
        $tableLA->setId('learningAreas');
        $tableLA->setTitle(__('Learning Areas'));

        echo $tableLA->render($learningAreas);
    }

    // Administration
    $administration = $departmentGateway->queryDepartments($criteria, 'Administration');

    if (count($administration) > 0) {
        $tableAdmin = clone $table;
        $tableAdmin->setId('administration');
        $tableAdmin->setTitle(__('Administration'));

        echo $tableAdmin->render($administration);
    }

    if (count($learningAreas) == 0 && count($administration) == 0) {
        echo $table->render(new DataSet([]));
    }

    if ($session->has('username')) {
        //Print sidebar
        $sidebarExtra = '';


            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE \'% - Left%\' ORDER BY course, class';
            $result = $connection2->prepare($sql);
            $result->execute($data);

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

            $session->set('sidebarExtra', $sidebarExtra);
        }
    }
}
