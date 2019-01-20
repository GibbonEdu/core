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

$gibbonScaleGradeID = $_GET['gibbonScaleGradeID'];
$gibbonScaleID = $_GET['gibbonScaleID'];

if ($gibbonScaleID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/gradeScales_manage_edit_grade_delete.php&gibbonScaleID=$gibbonScaleID&gibbonScaleGradeID=$gibbonScaleGradeID";
    $URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/gradeScales_manage_edit.php&gibbonScaleID=$gibbonScaleID&gibbonScaleGradeID=$gibbonScaleGradeID";

    if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit_grade_delete.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonScaleGradeID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonScaleGradeID' => $gibbonScaleGradeID);
                $sql = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonScaleGradeID' => $gibbonScaleGradeID);
                    $sql = 'DELETE FROM gibbonScaleGrade WHERE gibbonScaleGradeID=:gibbonScaleGradeID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
