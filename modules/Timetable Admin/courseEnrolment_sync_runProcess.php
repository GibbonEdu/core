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

$step = (isset($_POST['step']) && $_POST['step'] <= 3)? $_POST['step'] : 1;
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync_run.php';
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_run.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $gibbonYearGroupID = (isset($_POST['gibbonYearGroupID']))? $_POST['gibbonYearGroupID'] : null;

    if (empty($gibbonYearGroupID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $partialFail = false;

        $includeStudents = (isset($_POST['includeStudents']))? $_POST['includeStudents'] : false;
        $includeTeachers = (isset($_POST['includeTeachers']))? $_POST['includeTeachers'] : false;

        // Pull up the class mapping for this year group
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = "SELECT gibbonCourseClassMap.*
                FROM gibbonCourseClassMap
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonCourseClassMap.gibbonRollGroupID)
                WHERE gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID
                AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $pdo->executeQuery($data, $sql);

        if ($result->rowCount() == 0) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        while ($classMap = $result->fetch()) {
            $data = array(
                'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'],
                'gibbonCourseClassID' => $classMap['gibbonCourseClassID'],
                'gibbonYearGroupID' => $classMap['gibbonYearGroupID'],
                'gibbonRollGroupID' => $classMap['gibbonRollGroupID'],
                'date' => date('Y-m-d'),
            );

            // Sync students
            if ($includeStudents) {
                $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                SELECT :gibbonCourseClassID, gibbonPerson.gibbonPersonID, 'Student', 'Y'
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID
                AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";

                $pdo->executeQuery($data, $sql);
                if (!$pdo->getQuerySuccess()) $partialFail = true;
            }

            // Sync teachers by homeroom if enabled
            if ($includeTeachers) {
                $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                SELECT :gibbonCourseClassID, gibbonPerson.gibbonPersonID, 'Teacher', 'Y'
                FROM gibbonPerson
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID || gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID || gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID)
                WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonRollGroup.gibbonRollGroupID=:gibbonRollGroupID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";

                $pdo->executeQuery($data, $sql);
                if (!$pdo->getQuerySuccess()) $partialFail = true;
            }
        }

        if ($partialFail) {
            $URL .= '&return=warning3';
            header("Location: {$URL}");
            exit;
        } else {
            $URLSuccess .= '&return=success0';
            header("Location: {$URLSuccess}");
            exit;
        }
    }
}
