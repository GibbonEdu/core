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
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync.php&step=3';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonYearGroupIDList = (isset($_POST['gibbonYearGroupIDList']))? $_POST['gibbonYearGroupIDList'] : null;
    $syncBy = (isset($_POST['syncBy']))? $_POST['syncBy'] : null;
    $pattern = (isset($_POST['pattern']))? $_POST['pattern'] : null;

    $syncEnabled = (isset($_POST['syncEnabled']))? $_POST['syncEnabled'] : null;
    $syncTo = (isset($_POST['syncTo']))? $_POST['syncTo'] : null;

    if ($step != 3 || empty($gibbonYearGroupIDList) || empty($syncBy) || empty($syncTo) || empty($syncEnabled) || empty($pattern)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        $includeStudents = (isset($_POST['includeStudents']))? $_POST['includeStudents'] : false;
        $includeTeachers = (isset($_POST['includeTeachers']))? $_POST['includeTeachers'] : false;

        foreach ($syncTo as $gibbonCourseClassID => $syncID) {
            // Skip any courses that have been disabled
            if (!isset($syncEnabled[$gibbonCourseClassID]) || $syncEnabled[$gibbonCourseClassID] == false) continue;

            // Skip any enabled courses that have no sync selected
            if (empty($syncID)) continue;

            $data = array(
                'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'],
                'gibbonCourseClassID' => $gibbonCourseClassID,
                'gibbonYearGroupIDList' => $gibbonYearGroupIDList,
                'syncID' => $syncID,
                'date' => date('Y-m-d'),
            );

            if ($syncBy == 'rollGroup') {
                $subQuery = "gibbonStudentEnrolment.gibbonRollGroupID=:syncID";
            } else if ($syncBy == 'yearGroup') {
                $subQuery = "gibbonStudentEnrolment.gibbonYearGroupID=:syncID";
            } else if ($syncBy == 'house') {
                $subQuery = "gibbonPerson.gibbonHouseID=:syncID";
            }

            // Sync students
            if ($includeStudents) {
                $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                SELECT :gibbonCourseClassID, gibbonPerson.gibbonPersonID, 'Student', 'Y'
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID)
                WHERE $subQuery
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";
            }

            $pdo->executeQuery($data, $sql);
            if (!$pdo->getQuerySuccess()) $partialFail = true;

            // Sync teachers by homeroom if enabled
            if ($syncBy == 'rollGroup' && $includeTeachers) {
                $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                SELECT :gibbonCourseClassID, gibbonPerson.gibbonPersonID, 'Teacher', 'Y'
                FROM gibbonPerson
                JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID || gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID || gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=:gibbonCourseClassID)
                WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonRollGroup.gibbonRollGroupID=:syncID
                AND gibbonPerson.status='Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)
                AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";
            }

            $pdo->executeQuery($data, $sql);
            if (!$pdo->getQuerySuccess()) $partialFail = true;
        }

        if ($partialFail) {
            $URL .= '&return=warning3';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
