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

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';
$connection = $container->get('db');

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/department_course_class.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') == false or getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2) != 'View Student Profile_full') {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if ($gibbonCourseClassID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name';
            $result = $connection->statement($sql,$data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if (! $result) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Proceed!
            try {
                $sql = "SELECT CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name";
                $courseClassName = $connection->selectOne($sql, $data);
                $sql = "SELECT role, surname, preferredName, email, studentID FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<=:dateStart) AND (dateEnd IS NULL  OR dateEnd>=:dateEnd) ORDER BY role DESC, surname, preferredName";
                $result = $connection->select($sql,['gibbonCourseClassID' => $gibbonCourseClassID, 'dateStart' => date('Y-m-d'), 'dateEnd' => date('Y-m-d')]);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $exp = new Gibbon\Spreadsheet();
            $exp->getActiveSheet()->setTitle('Course CLass '.$courseClassName);
            $exp->exportWithQueryResult($result, 'Course Class '.str_replace('.', '_', $courseClassName).' List.xlsx');
        }
    }
}
