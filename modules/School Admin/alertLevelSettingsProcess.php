<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/alertLevelSettings.php';

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

            // Filter valid colour values
            $color = preg_replace('/[^a-fA-F0-9\#]/', '', mb_substr($color, 0, 7));
            $colorBG = preg_replace('/[^a-fA-F0-9\#]/', '', mb_substr($colorBG, 0, 7));

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
