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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/alertLevelSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/alertLevelSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $count = $_POST['count'] ?? '';
    $partialFail = false;
    //Proceed!
    if ($count < 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        for ($i = 0; $i < $count; ++$i) {
            $gibbonAlertLevelID = $_POST['gibbonAlertLevelID'.$i] ?? '';
            $name = $_POST['name'.$i] ?? '';
            $nameShort = $_POST['nameShort'.$i] ?? '';
            $color = $_POST['color'.$i] ?? '';
            $colorBG = $_POST['colorBG'.$i] ?? '';
            $description = $_POST['description'.$i] ?? '';

            //Validate Inputs
            if ($gibbonAlertLevelID == '' or $name == '' or $nameShort == '' or $color == '' or $colorBG == '') {
                $partialFail = true;
            } else {
                try {
                    $dataUpdate = array('name' => $name, 'nameShort' => $nameShort, 'color' => $color, 'colorBG' => $colorBG, 'description' => $description, 'gibbonAlertLevelID' => $gibbonAlertLevelID);
                    $sqlUpdate = 'UPDATE gibbonAlertLevel SET name=:name, nameShort=:nameShort, color=:color, colorBG=:colorBG, description=:description WHERE gibbonAlertLevelID=:gibbonAlertLevelID';
                    $resultUpdate = $connection2->prepare($sqlUpdate);
                    $resultUpdate->execute($dataUpdate);
                } catch (PDOException $e) {
                    $partialFail = false;
                }
            }
        }

        //Deal with failed update
        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
