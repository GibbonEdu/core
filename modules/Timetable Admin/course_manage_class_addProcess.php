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

$name = $_POST['name'];
$nameShort = $_POST['nameShort'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$gibbonCourseID = $_POST['gibbonCourseID'];
$reportable = $_POST['reportable'];
$attendance = (isset($_POST['attendance']))? $_POST['attendance'] : 'N';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/course_manage_class_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_manage_class_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $name == '' or $nameShort == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonCourseID' => $gibbonCourseID);
            $sql = 'SELECT * FROM gibbonCourseClass WHERE ((name=:name) OR (nameShort=:nameShort)) AND gibbonCourseID=:gibbonCourseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() > 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonCourseID' => $gibbonCourseID, 'name' => $name, 'nameShort' => $nameShort, 'reportable' => $reportable, 'attendance' => $attendance);
                $sql = 'INSERT INTO gibbonCourseClass SET gibbonCourseID=:gibbonCourseID, name=:name, nameShort=:nameShort, reportable=:reportable, attendance=:attendance';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
