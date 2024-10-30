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

$_POST = $container->get(Validator::class)->sanitize($_POST, ['policyLink' => 'URL']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/behaviourSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/behaviourSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $enableDescriptors = $_POST['enableDescriptors'] ?? '';
    $enableLevels = $_POST['enableLevels'] ?? '';
    $enableNegativeBehaviourLetters = $_POST['enableNegativeBehaviourLetters'] ?? '';
    $enablePositiveBehaviourLetters = $_POST['enablePositiveBehaviourLetters'] ?? '';
    $positiveDescriptors = '';
    $negativeDescriptors = '';
    if ($enableDescriptors == 'Y') {
        foreach (explode(',', $_POST['positiveDescriptors']) as $descriptor) {
            $positiveDescriptors .= trim($descriptor).',';
        }
        $positiveDescriptors = substr($positiveDescriptors, 0, -1);

        foreach (explode(',', $_POST['negativeDescriptors']) as $descriptor) {
            $negativeDescriptors .= trim($descriptor).',';
        }
        $negativeDescriptors = substr($negativeDescriptors, 0, -1);
    }
    $levels = '';
    if ($enableLevels == 'Y') {
        foreach (explode(',', $_POST['levels']) as $level) {
            $levels .= trim($level).',';
        }
        $levels = substr($levels, 0, -1);
    }

    $behaviourLettersNegativeLetter1Count = $_POST['behaviourLettersNegativeLetter1Count'] ?? '';
    $behaviourLettersNegativeLetter2Count = $_POST['behaviourLettersNegativeLetter2Count'] ?? '';
    $behaviourLettersNegativeLetter3Count = $_POST['behaviourLettersNegativeLetter3Count'] ?? '';

    $behaviourLettersPositiveLetter1Count = $_POST['behaviourLettersPositiveLetter1Count'] ?? '';
    $behaviourLettersPositiveLetter2Count = $_POST['behaviourLettersPositiveLetter2Count'] ?? '';
    $behaviourLettersPositiveLetter3Count = $_POST['behaviourLettersPositiveLetter3Count'] ?? '';

    $notifyTutors = $_POST['notifyTutors'] ?? 'Y';
    $notifyEducationalAssistants = $_POST['notifyEducationalAssistants'] ?? 'N';
    $policyLink = $_POST['policyLink'] ?? '';

    //Validate Inputs
    if ($enableDescriptors == '' or $enableLevels == '' or ($positiveDescriptors == '' and $enableDescriptors == 'Y') or ($negativeDescriptors == '' and $enableDescriptors == 'Y') or ($levels == '' and $enableLevels == 'Y') or (($behaviourLettersNegativeLetter1Count == '' or $behaviourLettersNegativeLetter2Count == '' or $behaviourLettersNegativeLetter3Count == '') and $enableNegativeBehaviourLetters == 'Y')) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('value' => $enableDescriptors);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='enableDescriptors'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        if ($enableDescriptors == 'Y') {
            try {
                $data = array('value' => $positiveDescriptors);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='positiveDescriptors'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }

            try {
                $data = array('value' => $negativeDescriptors);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='negativeDescriptors'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }
        }
        try {
            $data = array('value' => $enableLevels);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='enableLevels'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($enableLevels == 'Y') {
            try {
                $data = array('value' => $levels);
                $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='levels'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $fail = true;
            }
        }

        try {
            $data = array('value' => $enableNegativeBehaviourLetters);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='enableNegativeBehaviourLetters'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        try {
            $data = array('value' => $behaviourLettersNegativeLetter1Count);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='behaviourLettersNegativeLetter1Count'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        try {
            $data = array('value' => $behaviourLettersNegativeLetter2Count);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='behaviourLettersNegativeLetter2Count'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        try {
            $data = array('value' => $behaviourLettersNegativeLetter3Count);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='behaviourLettersNegativeLetter3Count'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enablePositiveBehaviourLetters);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='enablePositiveBehaviourLetters'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        try {
            $data = array('value' => $behaviourLettersPositiveLetter1Count);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='behaviourLettersPositiveLetter1Count'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        try {
            $data = array('value' => $behaviourLettersPositiveLetter2Count);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='behaviourLettersPositiveLetter2Count'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }
        try {
            $data = array('value' => $behaviourLettersPositiveLetter3Count);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='behaviourLettersPositiveLetter3Count'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $notifyTutors);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='notifyTutors'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $notifyEducationalAssistants);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='notifyEducationalAssistants'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $policyLink);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Behaviour' AND name='policyLink'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($fail == true) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            getSystemSettings($guid, $connection2);
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
