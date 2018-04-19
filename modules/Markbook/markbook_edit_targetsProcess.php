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

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/markbook_edit_targets.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_targets.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonCourseClassID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $count = $_POST['count'];
        $gibbonScaleIDTarget = $_POST['gibbonScaleIDTarget'];
        if ($gibbonScaleIDTarget == '')
            $gibbonScaleIDTarget = null;
        $partialFail = false;

        //Update target scale
        try {
            $data = array('gibbonScaleIDTarget' => $gibbonScaleIDTarget, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'UPDATE gibbonCourseClass SET gibbonScaleIDTarget=:gibbonScaleIDTarget WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $partialFail = true;
        }

        for ($i = 1;$i <= $count;++$i) {
            $gibbonPersonIDStudent = $_POST["$i-gibbonPersonID"];
            $gibbonScaleGradeID = null;
            if (!empty($_POST["$i-gibbonScaleGradeID"])) {
                $gibbonScaleGradeID = $_POST["$i-gibbonScaleGradeID"];
            }

            $selectFail = false;
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                $sql = 'SELECT * FROM gibbonMarkbookTarget WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
                $selectFail = true;
            }
            if (!($selectFail)) {
                if ($result->rowCount() < 1) {
                    try {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonScaleGradeID' => $gibbonScaleGradeID);
                        $sql = 'INSERT INTO gibbonMarkbookTarget SET gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonScaleGradeID=:gibbonScaleGradeID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                        $partialFail = true;
                    }
                } else {
                    $row = $result->fetch();
                    //Update
                    try {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonScaleGradeID' => $gibbonScaleGradeID);
                        $sql = 'UPDATE gibbonMarkbookTarget SET gibbonScaleGradeID=:gibbonScaleGradeID WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }

        //Return!
        if ($partialFail == true) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
