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

$_POST = $container->get(Validator::class)->sanitize($_POST, ['targetsTemplate' => 'HTML', 'teachingStrategiesTemplate' => 'HTML', 'notesReviewTemplate' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/inSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/inSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $targetsTemplate = $_POST['targetsTemplate'] ?? '';
    $teachingStrategiesTemplate = $_POST['teachingStrategiesTemplate'] ?? '';
    $notesReviewTemplate = $_POST['notesReviewTemplate'] ?? '';
    $investigationNotificationRole = $_POST['investigationNotificationRole'] ?? '';

    //Write to database
    $fail = false;

    try {
        $data = array('value' => $targetsTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Individual Needs' AND name='targetsTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $teachingStrategiesTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Individual Needs' AND name='teachingStrategiesTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $notesReviewTemplate);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Individual Needs' AND name='notesReviewTemplate'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $fail = true;
    }

    try {
        $data = array('value' => $investigationNotificationRole);
        $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Individual Needs' AND name='investigationNotificationRole'";
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
