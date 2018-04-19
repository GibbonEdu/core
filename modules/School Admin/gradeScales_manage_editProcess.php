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
$gibbonScaleID = $_POST['gibbonScaleID'];
$usage = $_POST['usage'];
$active = $_POST['active'];
$numeric = $_POST['numeric'];
$lowestAcceptable = $_POST['lowestAcceptable'];

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/gradeScales_manage_edit.php&gibbonScaleID='.$gibbonScaleID;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if special day specified
    if ($gibbonScaleID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
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
            if ($name == '' or $nameShort == '' or $usage == '' or $active == '' or $numeric == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'gibbonScaleID' => $gibbonScaleID);
                    $sql = 'SELECT * FROM gibbonScale WHERE (name=:name OR nameShort=:nameShort) AND NOT gibbonScaleID=:gibbonScaleID';
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
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'usage' => $usage, 'active' => $active, 'numeric' => $numeric, 'lowestAcceptable' => $lowestAcceptable, 'gibbonScaleID' => $gibbonScaleID);
                        $sql = 'UPDATE gibbonScale SET name=:name, nameShort=:nameShort, `usage`=:usage, active=:active, `numeric`=:numeric, lowestAcceptable=:lowestAcceptable WHERE gibbonScaleID=:gibbonScaleID';
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
