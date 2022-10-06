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

$type = $_POST['type'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$action = $_POST['action'] ?? '';
$allUsers = $_GET['allUsers'] ?? '';
$search = $_GET['search'] ?? '';

$URL = Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_manage_byPerson_edit')
    ->withQueryParams([
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonPersonID' => $gibbonPersonID,
        'type' => $type,
        'allUsers' => $allUsers,
        'search' => $search,
    ]);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else if ($gibbonPersonID == '' or $gibbonSchoolYearID == '' or $action == '') {
    header('Location: ' . $URL->withReturn('error1'));
} else {
    $classes = isset($_POST['gibbonCourseClassID'])? $_POST['gibbonCourseClassID'] : array();

    //Proceed!
    //Check if person specified
    if (count($classes) <= 0) {
        header('Location: ' . $URL->withReturn('error3'));
    } else {
        $partialFail = false;
        if ($action == 'Delete') {
            foreach ($classes as $gibbonCourseClassID) {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail == true;
                }
            }
        } else {
            foreach ($classes as $gibbonCourseClassID) {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $gibbonPersonID, 'dateUnenrolled' => date('Y-m-d'));
                    $sql = "UPDATE gibbonCourseClassPerson SET role=CONCAT(role, ' - Left'), dateUnenrolled=:dateUnenrolled WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND (role = 'Student' OR role = 'Teacher')";
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
