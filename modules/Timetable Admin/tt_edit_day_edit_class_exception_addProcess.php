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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonTTDayID = $_GET['gibbonTTDayID'];
$gibbonTTID = $_GET['gibbonTTID'];
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'];
$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonTTDayRowClassID = $_GET['gibbonTTDayRowClassID'];

if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '' or $gibbonTTDayRowClassID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/tt_edit_day_edit_class_exception_add.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClass=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_exception_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonTTDayID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonTTDayID' => $gibbonTTDayID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID, gibbonSpaceID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() < 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Run through each of the selected participants.
                $update = true;
                $choices = $_POST['Members'];

                if (count($choices) < 1) {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    foreach ($choices as $t) {
                        //Check to see if person is already exempted from this class
                        try {
                            $data = array('gibbonPersonID' => $t, 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
                            $sql = 'SELECT * FROM gibbonTTDayRowClassException WHERE gibbonPersonID=:gibbonPersonID AND gibbonTTDayRowClassID=:gibbonTTDayRowClassID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $update = false;
                        }

                        //If student not in course, add them
                        if ($result->rowCount() == 0) {
                            try {
                                $data = array('gibbonPersonID' => $t, 'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID);
                                $sql = 'INSERT INTO gibbonTTDayRowClassException SET gibbonPersonID=:gibbonPersonID, gibbonTTDayRowClassID=:gibbonTTDayRowClassID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $update = false;
                            }
                        }
                    }
                    //Write to database
                    if ($update == false) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                    } else {
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
