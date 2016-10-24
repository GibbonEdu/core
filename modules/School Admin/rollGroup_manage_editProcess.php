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

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$gibbonRollGroupID = $_GET['gibbonRollGroupID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rollGroup_manage_edit.php&gibbonRollGroupID=$gibbonRollGroupID&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/rollGroup_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonRollGroupID == '' or $gibbonSchoolYearID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID';
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
            //Validate Inputs
            $name = $_POST['name'];
            $nameShort = $_POST['nameShort'];
            $gibbonPersonIDTutor = null;
            if ($_POST['gibbonPersonIDTutor'] != '') {
                $gibbonPersonIDTutor = $_POST['gibbonPersonIDTutor'];
            }
            $gibbonPersonIDTutor2 = null;
            if ($_POST['gibbonPersonIDTutor2'] != '') {
                $gibbonPersonIDTutor2 = $_POST['gibbonPersonIDTutor2'];
            }
            $gibbonPersonIDTutor3 = null;
            if ($_POST['gibbonPersonIDTutor3'] != '') {
                $gibbonPersonIDTutor3 = $_POST['gibbonPersonIDTutor3'];
            }
            $gibbonPersonIDEA = null;
            if ($_POST['gibbonPersonIDEA'] != '') {
                $gibbonPersonIDEA = $_POST['gibbonPersonIDEA'];
            }
            $gibbonPersonIDEA2 = null;
            if ($_POST['gibbonPersonIDEA2'] != '') {
                $gibbonPersonIDEA2 = $_POST['gibbonPersonIDEA2'];
            }
            $gibbonPersonIDEA3 = null;
            if ($_POST['gibbonPersonIDEA3'] != '') {
                $gibbonPersonIDEA3 = $_POST['gibbonPersonIDEA3'];
            }
            $gibbonSpaceID = null;
            if ($_POST['gibbonSpaceID'] != '') {
                $gibbonSpaceID = $_POST['gibbonSpaceID'];
            }
            $gibbonRollGroupIDNext = null;
            if (isset($_POST['gibbonRollGroupIDNext'])) {
                $gibbonRollGroupIDNext = $_POST['gibbonRollGroupIDNext'];
            }
            $website = null;
            if (isset($_POST['website'])) {
                $website = $_POST['website'];
            }

            $attendance = (isset($_POST['attendance']))? $_POST['attendance'] : NULL;

            if ($gibbonSchoolYearID == '' or $name == '' or $nameShort == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonRollGroupID' => $gibbonRollGroupID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                    $sql = 'SELECT * FROM gibbonRollGroup WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonRollGroupID=:gibbonRollGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID';
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
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonPersonIDTutor' => $gibbonPersonIDTutor, 'gibbonPersonIDTutor2' => $gibbonPersonIDTutor2, 'gibbonPersonIDTutor3' => $gibbonPersonIDTutor3, 'gibbonPersonIDEA' => $gibbonPersonIDEA, 'gibbonPersonIDEA2' => $gibbonPersonIDEA2, 'gibbonPersonIDEA3' => $gibbonPersonIDEA3, 'gibbonSpaceID' => $gibbonSpaceID, 'gibbonRollGroupIDNext' => $gibbonRollGroupIDNext, 'attendance' => $attendance, 'website' => $website, 'gibbonRollGroupID' => $gibbonRollGroupID);
                        $sql = 'UPDATE gibbonRollGroup SET name=:name, nameShort=:nameShort, gibbonPersonIDTutor=:gibbonPersonIDTutor, gibbonPersonIDTutor2=:gibbonPersonIDTutor2, gibbonPersonIDTutor3=:gibbonPersonIDTutor3, gibbonPersonIDEA=:gibbonPersonIDEA, gibbonPersonIDEA2=:gibbonPersonIDEA2, gibbonPersonIDEA3=:gibbonPersonIDEA3, gibbonSpaceID=:gibbonSpaceID, gibbonRollGroupIDNext=:gibbonRollGroupIDNext, attendance=:attendance, website=:website WHERE gibbonRollGroupID=:gibbonRollGroupID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
