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
$gibbonSchoolYearIDNext = $_GET['gibbonSchoolYearIDNext'];
$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/School Admin/rollGroup_manage.php&gibbonSchoolYearID=$gibbonSchoolYearIDNext";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/rollGroup_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school years specified (current and next)
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearIDNext == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //GET CURRENT ROLL GROUPS
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
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
            $partialFail = false;
            while ($row = $result->fetch()) {
                //Write to database
                try {
                    $dataInsert = array('gibbonSchoolYearID' => $gibbonSchoolYearIDNext, 'name' => $row['name'], 'nameShort' => $row['nameShort'], 'gibbonPersonIDTutor' => $row['gibbonPersonIDTutor'], 'gibbonPersonIDTutor2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonIDTutor3' => $row['gibbonPersonIDTutor3'], 'gibbonSpaceID' => $row['gibbonSpaceID'], 'website' => $row['website']);
                    $sqlInsert = 'INSERT INTO gibbonRollGroup SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, gibbonPersonIDTutor=:gibbonPersonIDTutor, gibbonPersonIDTutor2=:gibbonPersonIDTutor2, gibbonPersonIDTutor3=:gibbonPersonIDTutor3, gibbonSpaceID=:gibbonSpaceID, gibbonRollGroupIDNext=NULL, website=:website';
                    $resultInsert = $connection2->prepare($sqlInsert);
                    $resultInsert->execute($dataInsert);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=error5';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
