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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonStudentEnrolmentID = $_POST['gibbonStudentEnrolmentID'];
$search = $_GET['search'];

if ($gibbonStudentEnrolmentID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage_edit.php&gibbonStudentEnrolmentID=$gibbonStudentEnrolmentID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonStudentEnrolmentID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                $sql = 'SELECT gibbonRollGroup.gibbonRollGroupID, gibbonYearGroup.gibbonYearGroupID,gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID ORDER BY surname, preferredName';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            } else {
                $gibbonYearGroupID = $_POST['gibbonYearGroupID'];
                $gibbonRollGroupID = $_POST['gibbonRollGroupID'];

                $rollOrder = $_POST['rollOrder'];
                if ($rollOrder == '') {
                    $rollOrder = null;
                }

                //Check unique inputs for uniquness
                try {
                    $data = array('gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'rollOrder' => $rollOrder, 'gibbonRollGroupID' => $gibbonRollGroupID);
                    $sql = "SELECT * FROM gibbonStudentEnrolment WHERE rollOrder=:rollOrder AND gibbonRollGroupID=:gibbonRollGroupID AND NOT gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID AND NOT rollOrder=''";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                    exit;
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID, 'rollOrder' => $rollOrder, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                        $sql = 'UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID, rollOrder=:rollOrder WHERE gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit;
                    }

                    // Handle automatic course enrolment if enabled
                    $autoEnrolStudent = (isset($_POST['autoEnrolStudent']))? $_POST['autoEnrolStudent'] : 'N';
                    if ($autoEnrolStudent == 'Y') {

                        // Remove existing auto-enrolment: moving a student from one Roll Group to another
                        $gibbonRollGroupIDOriginal = (isset($_POST['gibbonRollGroupIDOriginal']))? $_POST['gibbonRollGroupIDOriginal'] : 'N';

                        $data = array('gibbonRollGroupIDOriginal' => $gibbonRollGroupIDOriginal, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                        $sql = "DELETE gibbonCourseClassPerson FROM gibbonStudentEnrolment
                                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                                JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                                WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID
                                AND gibbonCourseClassMap.gibbonRollGroupID=:gibbonRollGroupIDOriginal";
                        $pdo->executeQuery($data, $sql);

                        if ($pdo->getQuerySuccess() == false) {
                            $URL .= "&return=warning3";
                            header("Location: {$URL}");
                            exit;
                        }

                        // Add course enrolments for new Roll Group
                        $data = array('gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                        $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                                SELECT gibbonCourseClassMap.gibbonCourseClassID, gibbonStudentEnrolment.gibbonPersonID, 'Student', 'Y'
                                FROM gibbonStudentEnrolment JOIN gibbonCourseClassMap ON (gibbonCourseClassMap.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID";
                        $pdo->executeQuery($data, $sql);

                        if ($pdo->getQuerySuccess() == false) {
                            $URL .= "&return=warning3";
                            header("Location: {$URL}");
                            exit;
                        }
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                    exit;
                }
            }
        }
    }
}
