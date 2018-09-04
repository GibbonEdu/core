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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/dataUpdaterSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/dataUpdaterSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $settingDefaults = array('title' => 'N', 'surname' => 'Y', 'firstName' => 'N', 'preferredName' => 'Y', 'officialName' => 'Y', 'nameInCharacters' => 'N', 'dob' => 'N', 'email' => 'N', 'emailAlternate' => 'N', 'phone1' => 'N', 'phone2' => 'N', 'phone3' => 'N', 'phone4' => 'N', 'languageFirst' => 'N', 'languageSecond' => 'N', 'languageThird' => 'N', 'countryOfBirth' => 'N', 'ethnicity' => 'N', 'citizenship1' => 'N', 'citizenship1Passport' => 'N', 'citizenship2' => 'N', 'citizenship2Passport' => 'N', 'religion' => 'N', 'nationalIDCardNumber' => 'N', 'residencyStatus' => 'N', 'visaExpiryDate' => 'N', 'profession' => 'N', 'employer' => 'N', 'jobTitle' => 'N', 'emergency1Name' => 'N', 'emergency1Number1' => 'N', 'emergency1Number2' => 'N', 'emergency1Relationship' => 'N', 'emergency2Name' => 'N', 'emergency2Number1' => 'N', 'emergency2Number2' => 'N', 'emergency2Relationship' => 'N', 'vehicleRegistration' => 'N');

    $settings = array();

    // Loop through $_POST, look only at valid settings
    foreach ($settingDefaults as $name => $defaultValue) {
        $settings[$name] = (isset($_POST[$name]) && $_POST[$name] == 'Y')? 'Y' : 'N';
    }

    //Write to database
    $fail = false;

    try {
        $data = array('value' => serialize($settings));
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='User Admin' AND name='personalDataUpdaterRequiredFields'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    if ($fail == true) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        //Success 0
        getSystemSettings($guid, $connection2);
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
