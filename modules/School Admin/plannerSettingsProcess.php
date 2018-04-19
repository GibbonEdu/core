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

include '../../functions.php';
include '../../config.php';

@session_start();

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/plannerSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/plannerSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $lessonDetailsTemplate = $_POST['lessonDetailsTemplate'];
    $teachersNotesTemplate = $_POST['teachersNotesTemplate'];
    $unitOutlineTemplate = $_POST['unitOutlineTemplate'];
    $smartBlockTemplate = $_POST['smartBlockTemplate'];
    $makeUnitsPublic = $_POST['makeUnitsPublic'];
    $shareUnitOutline = $_POST['shareUnitOutline'];
    $allowOutcomeEditing = $_POST['allowOutcomeEditing'];
    $sharingDefaultParents = $_POST['sharingDefaultParents'];
    $sharingDefaultStudents = $_POST['sharingDefaultStudents'];
    $parentWeeklyEmailSummaryIncludeBehaviour = $_POST['parentWeeklyEmailSummaryIncludeBehaviour'];
    $parentWeeklyEmailSummaryIncludeMarkbook = $_POST['parentWeeklyEmailSummaryIncludeMarkbook'];

    //Write to database
    $fail = false;

    try {
        $data = array('value' => $lessonDetailsTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='lessonDetailsTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $teachersNotesTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='teachersNotesTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $unitOutlineTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='unitOutlineTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $smartBlockTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='smartBlockTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $makeUnitsPublic);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='makeUnitsPublic'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $shareUnitOutline);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='shareUnitOutline'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $allowOutcomeEditing);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='allowOutcomeEditing'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $sharingDefaultParents);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='sharingDefaultParents'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $sharingDefaultStudents);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='sharingDefaultStudents'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $parentWeeklyEmailSummaryIncludeBehaviour);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='parentWeeklyEmailSummaryIncludeBehaviour'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $parentWeeklyEmailSummaryIncludeMarkbook);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Planner' AND name='parentWeeklyEmailSummaryIncludeMarkbook'";
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
