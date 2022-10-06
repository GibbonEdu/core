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

$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$URL = Url::fromModuleRoute('Timetable Admin', 'course_manage_delete')
    ->withQueryParams([
        'gibbonCourseID' => $gibbonCourseID,
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'search' => $_POST['search'] ?? '',
    ]);
$URLDelete = Url::fromModuleRoute('Timetable Admin', 'course_manage')
    ->withQueryParams([
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'search' => $_POST['search'] ?? '',
    ]);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_delete.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
    exit();
} else {
    // Proceed!
    // Check if school year specified
    if ($gibbonCourseID == '') {
        header('Location: ' . $URL->withReturn('error1'));
        exit();
    } else {
        try {
            $data = array('gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            header('Location: ' . $URL->withReturn('error2'));
            exit();
        }

        if ($result->rowCount() != 1) {
            header('Location: ' . $URL->withReturn('error2'));
        } else {
            // Try to delete entries in gibbonTTDayRowClass
            $dataSelect = array('gibbonCourseID' => $gibbonCourseID);
            $sqlSelect = 'SELECT gibbonTTDayRowClassID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseID=:gibbonCourseID';
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
            if ($resultSelect->rowCount() > 0) {
                while ($rowSelect = $resultSelect->fetch()) {
                    $dataDelete = array('gibbonTTDayRowClassID' => $rowSelect['gibbonTTDayRowClassID']);
                    $sqlDelete = 'DELETE FROM gibbonTTDayRowClassException WHERE gibbonTTDayRowClassID=:gibbonTTDayRowClassID';
                    $resultDelete = $connection2->prepare($sqlDelete);
                    $resultDelete->execute($dataDelete);
                }
            }

            $dataSelect = array('gibbonCourseID' => $gibbonCourseID);
            $sqlSelect = 'SELECT gibbonCourseClassID FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID';
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
            if ($resultSelect->rowCount() > 0) {
                while ($rowSelect = $resultSelect->fetch()) {
                    $dataDelete = array('gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
                    $sqlDelete = 'DELETE FROM gibbonTTDayRowClass WHERE gibbonCourseClassID=:gibbonCourseClassID';
                    $resultDelete = $connection2->prepare($sqlDelete);
                    $resultDelete->execute($dataDelete);
                }
            }

            // Delete students
            $dataStudent = array('gibbonCourseID' => $gibbonCourseID);
            $sqlStudent = 'SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID';
            $resultStudent = $connection2->prepare($sqlStudent);
            $resultStudent->execute($dataStudent);
            while ($rowStudent = $resultStudent->fetch()) {
                $dataDelete = array('gibbonCourseClassID' => $rowStudent['gibbonCourseClassID']);
                $sqlDelete = 'DELETE FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID';
                $resultDelete = $connection2->prepare($sqlDelete);
                $resultDelete->execute($dataDelete);
            }

            // Delete classes
            try {
                $dataDelete = array('gibbonCourseID' => $gibbonCourseID);
                $sqlDelete = 'DELETE FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID';
                $resultDelete = $connection2->prepare($sqlDelete);
                $resultDelete->execute($dataDelete);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            // Delete Course
            try {
                $dataDelete = array('gibbonCourseID' => $gibbonCourseID);
                $sqlDelete = 'DELETE FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
                $resultDelete = $connection2->prepare($sqlDelete);
                $resultDelete->execute($dataDelete);
            } catch (PDOException $e) {
                header('Location: ' . $URL->withReturn('error2'));
                exit();
            }

            header('Location: ' . $URLDelete->withReturn('success0'));
        }
    }
}
