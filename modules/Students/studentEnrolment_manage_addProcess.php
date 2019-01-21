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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonPersonID = $_POST['gibbonPersonID'];
$search = $_GET['search'];

if ($gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Students/studentEnrolment_manage_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPersonID FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full'";
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
                //Check for existing enrolment
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                    $sql = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
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
                    $gibbonYearGroupID = $_POST['gibbonYearGroupID'];
                    $gibbonRollGroupID = $_POST['gibbonRollGroupID'];
                    $rollOrder = $_POST['rollOrder'];
                    if ($rollOrder == '') {
                        $rollOrder = null;
                    }

                    //Check unique inputs for uniquness
                    try {
                        $data = array('rollOrder' => $rollOrder, 'gibbonRollGroupID' => $gibbonRollGroupID);
                        $sql = "SELECT * FROM gibbonStudentEnrolment WHERE rollOrder=:rollOrder AND gibbonRollGroupID=:gibbonRollGroupID AND NOT rollOrder=''";
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
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID, 'rollOrder' => $rollOrder);
                            $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonPersonID=:gibbonPersonID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID, rollOrder=:rollOrder';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit;
                        }

                        //Last insert ID
                        $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                        // Handle automatic course enrolment if enabled
                        $autoEnrolStudent = (isset($_POST['autoEnrolStudent']))? $_POST['autoEnrolStudent'] : 'N';
                        if ($autoEnrolStudent == 'Y') {
                            $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                                    SELECT gibbonCourseClassMap.gibbonCourseClassID, :gibbonPersonID, 'Student', 'Y'
                                    FROM gibbonCourseClassMap
                                    LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClassMap.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student')
                                    WHERE gibbonCourseClassMap.gibbonRollGroupID=:gibbonRollGroupID
                                    AND gibbonCourseClassPerson.gibbonCourseClassPersonID IS NULL";
                            $pdo->executeQuery($data, $sql);

                            if ($pdo->getQuerySuccess() == false) {
                                $URL .= "&return=warning3&editID=$AI";
                                header("Location: {$URL}");
                                exit;
                            }
                        }

                        $URL .= "&return=success0&editID=$AI";
                        header("Location: {$URL}");
                        exit;
                    }
                }
            }
        }
    }
}
