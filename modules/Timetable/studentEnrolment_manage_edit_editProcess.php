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
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';

if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage_edit_edit.php&gibbonCourseID=$gibbonCourseID&gibbonPersonID=$gibbonPersonID&gibbonCourseClassID=$gibbonCourseClassID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable/studentEnrolment_manage_edit_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID2' => $gibbonPersonID);
                $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList, gibbonCourseClassPerson.role, gibbonCourseClassPerson.dateEnrolled, gibbonCourseClassPerson.dateUnenrolled  FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonDepartmentStaff.role='Coordinator' OR gibbonDepartmentStaff.role='Assistant Coordinator') AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID2";
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

                if ($role == '') {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    $dateEnrolled = $role != $values['role'] && stripos($role, 'Left') === false ? date('Y-m-d') : $values['dateEnrolled'];
                    $dateUnenrolled = $role != $values['role'] && stripos($role, 'Left') !== false ? date('Y-m-d') : null;
                    try {
                        $data = array('role' => $role, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID, 'dateEnrolled' => $dateEnrolled, 'dateUnenrolled' => $dateUnenrolled);
                        $sql = 'UPDATE gibbonCourseClassPerson SET role=:role, dateEnrolled=:dateEnrolled, dateUnenrolled=:dateUnenrolled WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
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
