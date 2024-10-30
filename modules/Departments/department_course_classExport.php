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

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/department_course_class.php&gibbonCourseClassID=$gibbonCourseClassID";
$highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);

if (isActionAccessible($guid, $connection2, '/modules/Departments/department_course_class.php') == false || empty($highestAction)) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {

    if ($highestAction != 'View Student Profile_full' && $highestAction != 'View Student Profile_fullNoNotes'  && $highestAction != 'View Student Profile_fullEditAllNotes') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    if ($gibbonCourseClassID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS courseName, gibbonCourseClass.nameShort AS className FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.name, gibbonCourseClass.name';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        if ($result->rowCount() < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            //Proceed!

            $data = ['gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d')];
            $sql = "SELECT role, surname, preferredName, email, studentID, gibbonFormGroup.nameShort as formGroup
                    FROM gibbonCourseClassPerson
                    JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID
                    JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full'
                    AND (dateStart IS NULL OR dateStart<=:today)
                    AND (dateEnd IS NULL  OR dateEnd>=:today)
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonCourseClassPerson.role='Student'
                    ORDER BY role DESC, surname, preferredName";

            $result = $pdo->select($sql, $data);

            $exp = new Gibbon\Excel();
            $exp->exportWithQuery($result, 'classList.xls');
        }
    }
}
