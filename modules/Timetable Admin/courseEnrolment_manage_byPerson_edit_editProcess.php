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
use Gibbon\Data\Validator;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$type = $_GET['type'] ?? '';
$allUsers = $_GET['allUsers'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonCourseClassID == '' or $gibbonSchoolYearID == '' or $gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_manage_byPerson_edit_edit')
        ->withQueryParams([
            'type' => $type,
            'gibbonPersonID' => $gibbonPersonID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'allUsers' => $allUsers,
            'search' => $search,
        ]);

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_edit.php') == false) {
        header('Location: ' . $URL->withReturn('error0'));
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonPersonID == '') {
            header('Location: ' . $URL->withReturn('error1'));
        } else {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT role, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonCourseClassPerson.role, gibbonCourseClassPerson.dateEnrolled, gibbonCourseClassPerson.dateUnenrolled FROM gibbonPerson, gibbonCourseClass, gibbonCourseClassPerson,gibbonCourse, gibbonSchoolYear WHERE gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            if ($result->rowCount() != 1) {
                header('Location: ' . $URL->withReturn('error2'));
            } else {
                //Validate Inputs
                $values = $result->fetch();
                $role = $_POST['role'] ?? '';
                $reportable = $_POST['reportable'] ?? '';

                if ($role == '') {
                    header('Location: ' . $URL->withReturn('error3'));
                } else {
                    //Write to database
                    $dateEnrolled = $role != $values['role'] && stripos($role, 'Left') === false ? date('Y-m-d') : $values['dateEnrolled'];
                    $dateUnenrolled = $role != $values['role'] && stripos($role, 'Left') !== false ? date('Y-m-d') : $values['dateUnenrolled'];
                    try {
                        $data = array('role' => $role, 'reportable' => $reportable, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID, 'dateEnrolled' => $dateEnrolled, 'dateUnenrolled' => $dateUnenrolled);
                        $sql = 'UPDATE gibbonCourseClassPerson SET role=:role, dateEnrolled=:dateEnrolled, dateUnenrolled=:dateUnenrolled, reportable=:reportable WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        header('Location: ' . $URL->withReturn('error2'));
                        exit();
                    }

                    header('Location: ' . $URL->withReturn('success0'));
                }
            }
        }
    }
}
