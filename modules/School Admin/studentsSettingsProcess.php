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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/studentsSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/studentsSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $enableStudentNotes = $_POST['enableStudentNotes'];
    $noteCreationNotification = 'Tutors';
    if ($_POST['noteCreationNotification'] == 'Tutors & Teachers')
        $noteCreationNotification = 'Tutors & Teachers';
    $academicAlertLowThreshold = $_POST['academicAlertLowThreshold'];
    $academicAlertMediumThreshold = $_POST['academicAlertMediumThreshold'];
    $academicAlertHighThreshold = $_POST['academicAlertHighThreshold'];
    $behaviourAlertLowThreshold = $_POST['behaviourAlertLowThreshold'];
    $behaviourAlertMediumThreshold = $_POST['behaviourAlertMediumThreshold'];
    $behaviourAlertHighThreshold = $_POST['behaviourAlertHighThreshold'];
    $extendedBriefProfile = $_POST['extendedBriefProfile'];
    $studentAgreementOptions = '';
    foreach (explode(',', $_POST['studentAgreementOptions']) as $agreement) {
        $studentAgreementOptions .= trim($agreement).',';
    }
    $studentAgreementOptions = substr($studentAgreementOptions, 0, -1);

    //Write to database
    $fail = false;

    try {
        $data = array('value' => $enableStudentNotes);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='enableStudentNotes'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $noteCreationNotification);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='noteCreationNotification'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $academicAlertLowThreshold);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='academicAlertLowThreshold'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $academicAlertMediumThreshold);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='academicAlertMediumThreshold'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $academicAlertHighThreshold);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='academicAlertHighThreshold'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $behaviourAlertLowThreshold);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='behaviourAlertLowThreshold'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $behaviourAlertMediumThreshold);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='behaviourAlertMediumThreshold'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $behaviourAlertHighThreshold);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='behaviourAlertHighThreshold'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $extendedBriefProfile);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Students' AND name='extendedBriefProfile'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $studentAgreementOptions);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='School Admin' AND name='studentAgreementOptions'";
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
