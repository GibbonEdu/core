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

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonFamilyIDExisting = isset($_POST['gibbonFamilyIDExisting'])? $_POST['gibbonFamilyIDExisting'] : '';
$gibbonApplicationFormID = isset($_POST['gibbonApplicationFormID'])? $_POST['gibbonApplicationFormID'] : '';
$gibbonSchoolYearID = isset($_POST['gibbonSchoolYearID'])? $_POST['gibbonSchoolYearID'] : '';
$search = isset($_GET['search'])? $_GET['search'] : '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_edit.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (empty($gibbonApplicationFormID) || empty($gibbonFamilyIDExisting) || empty($gibbonSchoolYearID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    try {
        $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
        $sql = "SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($result->rowCount() != 1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $application = $result->fetch();
    $partialFail = false;

    $parent1gibbonPersonID = isset($_POST['parent1gibbonPersonID'])? $_POST['parent1gibbonPersonID'] : '';
    $parent1relationship = isset($_POST['parent1relationship'])? $_POST['parent1relationship'] : '';

    if ($parent1gibbonPersonID == 'new') {
        $parent1gibbonPersonID = '';
    } else if (!empty($parent1gibbonPersonID)) {
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonPersonID' => $parent1gibbonPersonID, 'relationship' => $parent1relationship);
            $sql = "INSERT INTO gibbonApplicationFormRelationship SET gibbonApplicationFormID=:gibbonApplicationFormID, gibbonPersonID=:gibbonPersonID, relationship=:relationship";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $partialFail = true;
        }
    }

    $parent2gibbonPersonID = isset($_POST['parent2gibbonPersonID'])? $_POST['parent2gibbonPersonID'] : '';
    $parent2relationship = isset($_POST['parent2relationship'])? $_POST['parent2relationship'] : '';

    if ($parent2gibbonPersonID == 'new') {
        $parent2gibbonPersonID = '';
    } else if (!empty($parent1gibbonPersonID)) {
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonPersonID' => $parent2gibbonPersonID, 'relationship' => $parent2relationship);
            $sql = "INSERT INTO gibbonApplicationFormRelationship SET gibbonApplicationFormID=:gibbonApplicationFormID, gibbonPersonID=:gibbonPersonID, relationship=:relationship";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $partialFail = true;
        }
    }

    try {
        $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonFamilyID' => $gibbonFamilyIDExisting, 'parent1gibbonPersonID' => $parent1gibbonPersonID, 'parent1relationship' => $parent1relationship, 'parent2relationship' => $parent2relationship);
        $sql = "UPDATE gibbonApplicationForm SET gibbonFamilyID=:gibbonFamilyID, parent1gibbonPersonID=:parent1gibbonPersonID, parent1relationship=:parent1relationship,  parent2relationship=:parent2relationship WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
        $partialFail = true;
    }

    

    if ($partialFail == true) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
        exit;
    } else {
       $URL .= '&return=success0';
       header("Location: {$URL}");
       exit;
    }
}
