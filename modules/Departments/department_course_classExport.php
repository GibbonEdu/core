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

use Gibbon\Domain\Departments\CourseGateway;
use Gibbon\Domain\Departments\CourseClassPersonGateway;
use Gibbon\Tables\Prefab\ReportTable;

include '../../gibbon.php';

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/department_course_class.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') == false or getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2) != 'View Student Profile_full') {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($gibbonCourseClassID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {

        $courseGateway = $container->get(CourseGateway::class);

        $result = $courseGateway->queryByCourseClass($gibbonCourseClassID);

        if (! $result->getResultCount() === 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Proceed!
            $courseClassPersonGateway = $container->get(CourseClassPersonGateway::class);

            $criteria = $courseClassPersonGateway->newQueryCriteria()
                ->sortBy(['role DESC', 'surname', 'preferredName'])
                ->pageSize(0);

            $students = $courseClassPersonGateway->queryFullByDate($gibbonCourseClassID, date('Y-m-d'));

            $courseClass = $result->getRow(0);

            $table = ReportTable::createPaginated(__('Course').' '.__('Class').' '.$courseClass['course'].'_'.$courseClass['class'], $criteria)->setViewMode('export',$gibbon->session);
            $table->setTitle(__('Course').' '.__('Class').' '.$courseClass['course'].'.'.$courseClass['class']);
            $table->setDescription(__('Course').' '.__('Class').' '.$courseClass['courseLong'].'.'.$courseClass['class'] . __(' in School Year') . ' ' . $courseClass['year']);

            $table->addColumn('role', __('Role'));
            $table->addColumn('surname', __('Surname'));
            $table->addColumn('preferredName', __('Preferred Name'));
            $table->addColumn('email', __('Email'));
            $table->addColumn('studentID', __('Student Identifier'));

            $table->render($students);
        }
    }
}
