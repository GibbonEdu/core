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

$gibbonYearGroupIDList = $_POST['gibbonYearGroupIDList'] ?? null;
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? null;

$URL = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_run')
    ->withQueryParams([
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonYearGroupIDList' => $gibbonYearGroupIDList,
    ]);
$URLSuccess = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync.php');

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_run.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
    exit;
} else {
    //Proceed!
    $syncData = (isset($_POST['syncData']))? $_POST['syncData'] : false;

    if (empty($gibbonYearGroupIDList) || empty($gibbonSchoolYearID) || empty($syncData)) {
        header('Location: ' . $URL->withReturn('error1'));
        exit;
    } else {
        $partialFail = false;

        foreach ($syncData as $gibbonFormGroupID => $usersToEnrol) {
            if (empty($usersToEnrol)) continue;

            foreach ($usersToEnrol as $gibbonPersonID => $role) {

                $data = array(
                    'gibbonFormGroupID' => $gibbonFormGroupID,
                    'gibbonPersonID' => $gibbonPersonID,
                    'role' => $role,
                    'dateEnrolled' => date('Y-m-d'),
                );

                // Update existing course enrolments
                $sql = "UPDATE gibbonCourseClassPerson
                        JOIN gibbonStudentEnrolment ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                        JOIN gibbonCourseClassMap ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID
                            AND gibbonCourseClassMap.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                        SET gibbonCourseClassPerson.role=:role, gibbonCourseClassPerson.dateEnrolled=:dateEnrolled, gibbonCourseClassPerson.dateUnenrolled=NULL, reportable='Y'
                        WHERE gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                        AND gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID
                        AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NOT NULL";
                $pdo->executeQuery($data, $sql);

                // Add course enrolments
                $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `dateEnrolled`, `reportable`)
                        SELECT gibbonCourseClassMap.gibbonCourseClassID, :gibbonPersonID, :role, :dateEnrolled, 'Y'
                        FROM gibbonCourseClassMap
                        LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID AND gibbonCourseClassPerson.role=:role)
                        LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID)
                        WHERE gibbonCourseClassMap.gibbonFormGroupID=:gibbonFormGroupID
                        AND (:role='Teacher' OR gibbonCourseClassMap.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                        AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";
                $pdo->executeQuery($data, $sql);

                if (!$pdo->getQuerySuccess()) $partialFail = true;
            }
        }

        if ($partialFail) {
            header('Location: ' . $URL->withReturn('warning3'));
            exit;
        } else {
            header('Location: ' . $URLSuccess->withReturn('success0'));
            exit;
        }
    }
}
