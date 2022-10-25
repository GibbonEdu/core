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

use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$type = $_GET['type'] ?? '';
$allUsers = $_GET['allUsers'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonPersonID == '' or $gibbonCourseClassID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
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
    $URLDelete = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_manage_byPerson_edit')
        ->withQueryParams([
            'type' => $type,
            'gibbonPersonID' => $gibbonPersonID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'allUsers' => $allUsers,
            'search' => $search,
        ]);

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit_delete.php') == false) {
        header('Location: ' . $URL->withReturn('error0'));
    } else {
        //Proceed!
        //Check if gibbonPersonID specified
        if ($gibbonPersonID == '') {
            header('Location: ' . $URL->withReturn('error1'));
        } else {
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT role, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName FROM gibbonPerson, gibbonCourseClass, gibbonCourseClassPerson,gibbonCourse, gibbonSchoolYear WHERE gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPerson.gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            if ($result->rowCount() != 1) {
                header('Location: ' . $URL->withReturn('error2'));
            } else {
                //Write to database
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    header('Location: ' . $URL->withReturn('error2'));
                    exit();
                }

                header('Location: ' . $URLDelete->withReturn('success0'));
            }
        }
    }
}
