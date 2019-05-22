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


$URL = $gibbon->session->get('absoluteURL','').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/badgeSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badgeSettings.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $badgeCategories = '';
    foreach (explode(',', $_POST['badgeCategories']) as $category) {
        $badgeCategories .= trim($category).',';
    }
    $badgeCategories = substr($badgeCategories, 0, -1);

    //Validate Inputs
    if ($badgeCategories == '') {
        //Fail 3
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('value' => $badgeCategories);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Badges' AND name='badgeCategories'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($fail == true) {
            //Fail 2
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Success 0
            getSystemSettings($guid, $connection2);
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
