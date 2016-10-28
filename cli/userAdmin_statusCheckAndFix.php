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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $count = 0;

    //Scan through every user to correct own status
    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = 'SELECT gibbonPersonID, status, dateEnd, dateStart, gibbonRoleIDAll FROM gibbonPerson ORDER BY gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        //Check for status=='Expected' when met or exceeded start date and set to 'Full'
        if ($row['dateStart'] != '' and date('Y-m-d') >= $row['dateStart'] and $row['status'] == 'Expected') {
            try {
                $dataUpdate = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlUpdate = "UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID";
                $resultUpdate = $connection2->prepare($sqlUpdate);
                $resultUpdate->execute($dataUpdate);
            } catch (PDOException $e) {
            }
            ++$count;
        }

        //Check for status=='Full' when end date exceeded, and set to 'Left'
        if ($row['dateEnd'] != '' and date('Y-m-d') > $row['dateEnd'] and $row['status'] == 'Full') {
            try {
                $dataUpdate = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlUpdate = "UPDATE gibbonPerson SET status='Left' WHERE gibbonPersonID=:gibbonPersonID";
                $resultUpdate = $connection2->prepare($sqlUpdate);
                $resultUpdate->execute($dataUpdate);
            } catch (PDOException $e) {
            }
            ++$count;
        }
    }
    //Scan through every user who is child in a family to correct parent status
    try {
        $data = array();
        $sql = "SELECT gibbonFamilyID, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Left' ORDER BY gibbonPersonID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        //Check to see if all siblings are left
        try {
            $dataCheck1 = array('gibbonFamilyID' => $row['gibbonFamilyID']);
            $sqlCheck1 = "SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT status='Left' ORDER BY gibbonPersonID";
            $resultCheck1 = $connection2->prepare($sqlCheck1);
            $resultCheck1->execute($dataCheck1);
        } catch (PDOException $e) {
        }

        if ($resultCheck1->rowCount() == 0) { //There are no active siblings, so let's check parents to see if we can set anyone to left
            try {
                $dataCheck2 = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                $sqlCheck2 = "SELECT gibbonPerson.gibbonPersonID, status, gibbonRoleIDAll FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT status='Left' ORDER BY gibbonPersonID";
                $resultCheck2 = $connection2->prepare($sqlCheck2);
                $resultCheck2->execute($dataCheck2);
            } catch (PDOException $e) {
            }

            while ($rowCheck2 = $resultCheck2->fetch()) {
                //Check to see if parent has any non-staff roles. If not, mark as 'Left'
                $nonParentRole = false;
                $roles = explode(',', $rowCheck2['gibbonRoleIDAll']);
                foreach ($roles as $role) {
                    if (getRoleCategory($role, $connection2) != 'Parent') {
                        $nonParentRole = true;
                    }
                }

                if ($nonParentRole == false) {
                    //Update status to 'Left'
                    try {
                        $dataUpdate = array('gibbonPersonID' => $rowCheck2['gibbonPersonID']);
                        $sqlUpdate = "UPDATE gibbonPerson SET status='Left' WHERE gibbonPersonID=:gibbonPersonID";
                        $resultUpdate = $connection2->prepare($sqlUpdate);
                        $resultUpdate->execute($dataUpdate);
                    } catch (PDOException $e) {
                    }
                    ++$count;
                }
            }
        }
    }

    //Notify admin
    $notificationText = sprintf(__($guid, 'A User Admin CLI script has run, updating %1$s users.'), $count);
    setNotification($connection2, $guid, $_SESSION[$guid]['organisationAdministrator'], $notificationText, 'User Admin', '/index.php?q=/modules/User Admin/user_manage.php');
}
