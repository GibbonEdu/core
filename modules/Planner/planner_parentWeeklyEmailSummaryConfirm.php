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

//Get variables
$gibbonSchoolYearID = '';
if (isset($_GET['gibbonSchoolYearID'])) {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
}
$key = '';
if (isset($_GET['key'])) {
    $key = $_GET['key'] ?? '';
}
$gibbonPersonIDStudent = '';
if (isset($_GET['gibbonPersonIDStudent'])) {
    $gibbonPersonIDStudent = $_GET['gibbonPersonIDStudent'] ?? '';
}
$gibbonPersonIDParent = '';
if (isset($_GET['gibbonPersonIDParent'])) {
    $gibbonPersonIDParent = $_GET['gibbonPersonIDParent'] ?? '';
}

//Check variables
if ($gibbonSchoolYearID == '' or $key == '' or $gibbonPersonIDStudent == '' or $gibbonPersonIDParent == '') { $page->addError(__('You have not specified one or more required parameters.'));
} else {
    //Check for record
    $keyReadFail = false;
    try {
        $dataKeyRead = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'key' => $key);
        $sqlKeyRead = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent AND `key`=:key';
        $resultKeyRead = $connection2->prepare($sqlKeyRead);
        $resultKeyRead->execute($dataKeyRead);
    } catch (PDOException $e) {
        $page->addError(__('Your request failed due to a database error.'));
    }

    if ($resultKeyRead->rowCount() != 1) { //If not exists, report error
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
    } else {    //If exists check confirmed
        $rowKeyRead = $resultKeyRead->fetch();

        if ($rowKeyRead['confirmed'] == 'Y') { //If already confirmed, report success
            $page->addSuccess(__('Thank you for confirming receipt and reading of this email.'));
        } else { //If not confirmed, confirm
            $keyWriteFail = false;
            try {
                $dataKeyWrite = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'gibbonPersonIDParent' => $gibbonPersonIDParent, 'key' => $key);
                $sqlKeyWrite = "UPDATE gibbonPlannerParentWeeklyEmailSummary SET confirmed='Y', gibbonPersonIDParent=:gibbonPersonIDParent WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND `key`=:key";
                $resultKeyWrite = $connection2->prepare($sqlKeyWrite);
                $resultKeyWrite->execute($dataKeyWrite);
            } catch (PDOException $e) {
                $keyWriteFail = true;
            }

            if ($keyWriteFail == true) { //Report error
                $page->addError(__('Your request failed due to a database error.'));
            } else { //Report success
                $page->addSuccess(__('Thank you for confirming receipt and reading of this email.'));
            }
        }
    }
}
