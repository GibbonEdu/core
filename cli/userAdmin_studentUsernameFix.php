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

require getcwd().'/../config.php';
require getcwd().'/../functions.php';
require getcwd().'/../lib/PHPMailer/PHPMailerAutoload.php';

$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $count = 0;

    try {
        // Update student photos to match studentID
        $sql = "UPDATE `gibbonPerson` SET `image_240` = CONCAT( 'uploads/photos/', studentID, '.jpg') WHERE (image_240 = '' OR image_240 IS NULL) AND `gibbonRoleIDPrimary` = 003 AND LEFT(studentID, 1) = '2'";
        $result = $pdo->executeQuery(array(), $sql);

        // Update staff photos
        $sql = "UPDATE `gibbonPerson` SET `image_240` = CONCAT( 'uploads/photos/', username, '.jpg') WHERE (image_240 = '' OR image_240 IS NULL) AND `gibbonRoleIDPrimary` <> 003 AND `gibbonRoleIDPrimary` <> 004 AND LEFT(username, 1) = '1'";
        $result = $pdo->executeQuery(array(), $sql);

        // Update usernames to match studentID
        $sql = "UPDATE `gibbonPerson` SET `username`=`studentID` WHERE `gibbonRoleIDPrimary`=003 AND LEFT(`username`, 1) <> '2' AND `studentID` <> ''";
        $result = $pdo->executeQuery(array(), $sql);
    } catch (PDOException $e) {
    }
}
