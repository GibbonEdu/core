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

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/markbookSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/markbookSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $markbookType = '';
    foreach (explode(',', $_POST['markbookType']) as $type) {
        $markbookType .= trim($type).',';
    }
    $markbookType = substr($markbookType, 0, -1);
    $enableEffort = $_POST['enableEffort'] ?? '';
    $enableRubrics = $_POST['enableRubrics'] ?? '';
    $enableColumnWeighting = $_POST['enableColumnWeighting'] ?? '';
    $enableDisplayCumulativeMarks = $_POST['enableDisplayCumulativeMarks'] ?? 'N';
    $enableRawAttainment = $_POST['enableRawAttainment'] ?? '';
    $enableModifiedAssessment = $_POST['enableModifiedAssessment'] ?? '';
    $enableGroupByTerm = $_POST['enableGroupByTerm'] ?? '';
    $attainmentAlternativeName = $_POST['attainmentAlternativeName'] ?? '';
    $attainmentAlternativeNameAbrev = $_POST['attainmentAlternativeNameAbrev'] ?? '';
    $effortAlternativeName = $_POST['effortAlternativeName'] ?? '';
    $effortAlternativeNameAbrev = $_POST['effortAlternativeNameAbrev'] ?? '';
    $showStudentAttainmentWarning = $_POST['showStudentAttainmentWarning'] ?? '';
    $showStudentEffortWarning = $_POST['showStudentEffortWarning'] ?? '';
    $showParentAttainmentWarning = $_POST['showParentAttainmentWarning'] ?? '';
    $showParentEffortWarning = $_POST['showParentEffortWarning'] ?? '';
    $personalisedWarnings = $_POST['personalisedWarnings'] ?? '';

    //Validate Inputs
    if ($markbookType == '' or $enableRubrics == '' or $enableRubrics == '' or $enableColumnWeighting == '' or $enableRawAttainment == '' or $enableModifiedAssessment == '' or $enableGroupByTerm == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('value' => $markbookType);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='markbookType'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enableEffort);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableEffort'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enableRubrics);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableRubrics'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enableColumnWeighting);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableColumnWeighting'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enableDisplayCumulativeMarks);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableDisplayCumulativeMarks'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enableRawAttainment);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableRawAttainment'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $enableModifiedAssessment);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableModifiedAssessment'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }


        try {
            $data = array('value' => $enableGroupByTerm);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='enableGroupByTerm'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $attainmentAlternativeName);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='attainmentAlternativeName'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $attainmentAlternativeNameAbrev);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='attainmentAlternativeNameAbrev'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $effortAlternativeName);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='effortAlternativeName'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $effortAlternativeNameAbrev);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='effortAlternativeNameAbrev'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $showStudentAttainmentWarning);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showStudentAttainmentWarning'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $showStudentEffortWarning);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showStudentEffortWarning'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $showParentAttainmentWarning);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showParentAttainmentWarning'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $showParentEffortWarning);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='showParentEffortWarning'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $personalisedWarnings);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Markbook' AND name='personalisedWarnings'";
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
