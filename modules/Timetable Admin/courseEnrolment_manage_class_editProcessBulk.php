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

$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$gibbonCourseID = $_POST['gibbonCourseID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$action = $_POST['action'] ?? '';
$search = $_POST['search'] ?? '';

$URL = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_manage_class_edit')
    ->withQueryParams([
        'gibbonCourseID' => $gibbonCourseID,
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseClassID' => $gibbonCourseClassID,
        'search' => $search,
    ]);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '' or $action == '') {
    header('Location: ' . $URL->withReturn('error1'));
} else {
    $people = isset($_POST['gibbonCourseClassPersonID']) ? $_POST['gibbonCourseClassPersonID'] : array();

    //Proceed!
    //Check if person specified
    if (count($people) < 1) {
        header('Location: ' . $URL->withReturn('error3'));
    } else {
        $partialFail = false;
        if ($action == 'Delete') {
            foreach ($people as $gibbonCourseClassPersonID) {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID);
                    $sql = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPersonID=:gibbonCourseClassPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail == true;
                }
            }
        }
        else if ($action == 'Copy to class') {
            $gibbonCourseClassIDCopyTo = (isset($_POST['gibbonCourseClassIDCopyTo']))? $_POST['gibbonCourseClassIDCopyTo'] : NULL;
            if (!empty($gibbonCourseClassIDCopyTo)) {

                foreach ($people as $gibbonCourseClassPersonID) {
                    // Check for duplicates
                    try {
                        $dataCheck = array('gibbonCourseClassIDCopyTo' => $gibbonCourseClassIDCopyTo, 'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID);
                        $sqlCheck = 'SELECT gibbonPersonID FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassIDCopyTo AND gibbonCourseClassPersonID=:gibbonCourseClassPersonID';
                        $resultCheck = $connection2->prepare($sqlCheck);
                        $resultCheck->execute($dataCheck);
                    } catch (PDOException $e) {
                        $partialFail == true;
                    }

                    // Insert new course participants
                    if ($resultCheck->rowCount() == 0) {
                        try {
                            $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID, 'gibbonCourseClassIDCopyTo' => $gibbonCourseClassIDCopyTo, 'dateEnrolled' => date('Y-m-d'));
                            $sql = 'INSERT INTO gibbonCourseClassPerson (gibbonCourseClassID, gibbonPersonID, role, dateEnrolled, reportable) SELECT :gibbonCourseClassIDCopyTo, gibbonPersonID, role, :dateEnrolled, reportable FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPersonID=:gibbonCourseClassPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail == true;
                        }
                    }


                }
            } else {
                header('Location: ' . $URL->withReturn('error3'));
            }
        } else if ($action == 'Mark as left') {
            foreach ($people as $gibbonCourseClassPersonID) {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonCourseClassPersonID' => $gibbonCourseClassPersonID, 'dateUnenrolled' => date('Y-m-d'));
                    $sql = "UPDATE gibbonCourseClassPerson SET role=CONCAT(role, ' - Left '), dateUnenrolled=:dateUnenrolled WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPersonID=:gibbonCourseClassPersonID AND (role = 'Student' OR role = 'Teacher')";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail == true;
                }
            }
        }

        if ($partialFail == true) {
            header('Location: ' . $URL->withReturn('warning1'));
        } else {
            header('Location: ' . $URL->withReturn('success0'));
        }
    }

}
