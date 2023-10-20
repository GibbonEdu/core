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

$gibbonYearGroupIDList = $_POST['gibbonYearGroupIDList'] ?? null;
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? null;

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync_run.php&gibbonSchoolYearID='.$gibbonSchoolYearID.'&gibbonYearGroupIDList='.$gibbonYearGroupIDList;
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_run.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $syncData = (isset($_POST['syncData']))? $_POST['syncData'] : false;

    if (empty($gibbonYearGroupIDList) || empty($gibbonSchoolYearID) || empty($syncData)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
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
