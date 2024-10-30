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
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseClassPersonID = $_POST['gibbonCourseClassPersonID'] ?? '';

if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseEnrolment_manage_class_edit_edit.php&gibbonCourseID=$gibbonCourseID&gibbonCourseClassPersonID=$gibbonCourseClassPersonID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseClassID=$gibbonCourseClassID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonCourseClassPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID);
                $sql = "SELECT gibbonCourseClassPerson.role, gibbonCourseClassPerson.dateEnrolled, gibbonCourseClassPerson.dateUnenrolled,  gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.gibbonPersonID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName FROM gibbonPerson, gibbonCourseClass, gibbonCourseClassPerson,gibbonCourse, gibbonSchoolYear WHERE gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonCourseClassPersonID=:gibbonCourseClassPersonID AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Validate Inputs
                $values = $result->fetch();
                $role = $_POST['role'] ?? '';
                $reportable = $_POST['reportable'] ?? '';

                if ($role == '') {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    $dateEnrolled = $role != $values['role'] && stripos($role, 'Left') === false ? date('Y-m-d') : $values['dateEnrolled'];
                    $dateUnenrolled = $role != $values['role'] && stripos($role, 'Left') !== false ? date('Y-m-d') : $values['dateUnenrolled'];
                    try {
                        $data = array('role' => $role, 'reportable' => $reportable, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID, 'dateEnrolled' => $dateEnrolled, 'dateUnenrolled' => $dateUnenrolled);
                        $sql = 'UPDATE gibbonCourseClassPerson SET role=:role, dateEnrolled=:dateEnrolled, dateUnenrolled=:dateUnenrolled, reportable=:reportable WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPersonID=:gibbonCourseClassPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
