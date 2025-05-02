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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonExternalAssessmentStudentID = $_GET['gibbonExternalAssessmentStudentID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'] ?? '')."/externalAssessment_manage_details.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID&gibbonPersonID=$gibbonPersonID";

// Common validation for all actions
if (!\Gibbon\Module\FormalAssessment\AssessmentValidator::validateRequiredParams($guid, $connection2, $action, [$gibbonExternalAssessmentID, $gibbonPersonID, $gibbonExternalAssessmentStudentID])) {
    \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error1');
}

// Route to appropriate handler
switch ($action) {
    case 'add': handleAdd(); break;
    case 'edit': handleEdit(); break;
    case 'delete': handleDelete(); break;
    default: redirectWithError('error1');
}

function validateRequiredParams($action, $gibbonExternalAssessmentID, $gibbonPersonID, $gibbonExternalAssessmentStudentID) {
    global $session, $URL;
    
    // Common access check
    if (!isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details.php')) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error0');
        return false;
    }

    // Action-specific checks
    if ($action == 'add' && empty($_POST)) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error3');
        return false;
    }

    if (in_array($action, ['edit', 'delete']) && empty($gibbonExternalAssessmentStudentID)) {
        redirectWithError('error1');
        return false;
    }

    return true;
}

function handleAdd() {
    global $session, $connection2, $URL, $gibbonExternalAssessmentID, $gibbonPersonID;
    
    $required = ['date'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            redirectWithError('error1');
        }
    }

    try {
        $data = [
            'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID,
            'gibbonPersonID' => $gibbonPersonID,
            'date' => Format::dateConvert($_POST['date']),
            'attachment' => handleFileUpload()
        ];
        
        $sql = 'INSERT INTO gibbonExternalAssessmentStudent SET gibbonExternalAssessmentID=:gibbonExternalAssessmentID, gibbonPersonID=:gibbonPersonID, date=:date, attachment=:attachment';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);
        
        // Handle additional fields
        $count = $_POST['count'] ?? 0;
        $partialFail = false;
        
        for ($i = 0; $i < $count; ++$i) {
            $gibbonExternalAssessmentFieldID = $_POST[$i.'-gibbonExternalAssessmentFieldID'] ?? '';
            $gibbonScaleGradeID = $_POST[$i.'-gibbonScaleGradeID'] ?? null;
            
            if (!empty($gibbonExternalAssessmentFieldID)) {
                try {
                    $data = [
                        'gibbonExternalAssessmentStudentID' => $AI,
                        'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID,
                        'gibbonScaleGradeID' => $gibbonScaleGradeID
                    ];
                    $sql = 'INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID, gibbonScaleGradeID=:gibbonScaleGradeID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
        }
        
        if ($partialFail) {
            redirectWithError("error1&editID=$AI");
        } else {
            redirectWithSuccess("success0&editID=$AI");
        }
    } catch (PDOException $e) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error2');
    }
}

function handleEdit() {
    global $session, $connection2, $URL, $gibbonExternalAssessmentStudentID;
    
    try {
        $data = [
            'date' => Format::dateConvert($_POST['date']),
            'attachment' => handleFileUpload(),
            'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID
        ];
        
        $sql = 'UPDATE gibbonExternalAssessmentStudent SET date=:date, attachment=:attachment WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Handle additional fields
        $count = $_POST['count'] ?? 0;
        $partialFail = false;
        
        for ($i = 0; $i < $count; ++$i) {
            $gibbonExternalAssessmentStudentEntryID = $_POST[$i.'-gibbonExternalAssessmentStudentEntryID'] ?? '';
            $gibbonScaleGradeID = $_POST[$i.'-gibbonScaleGradeID'] ?? null;
            
            if (!empty($gibbonExternalAssessmentStudentEntryID)) {
                try {
                    $data = [
                        'gibbonScaleGradeID' => $gibbonScaleGradeID,
                        'gibbonExternalAssessmentStudentEntryID' => $gibbonExternalAssessmentStudentEntryID
                    ];
                    $sql = 'UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=:gibbonScaleGradeID WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
        }
        
        if ($partialFail) {
            redirectWithError('warning1');
        } else {
            \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithSuccess($URL, 'success0');
        }
    } catch (PDOException $e) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error2');
    }
}

function handleDelete() {
    global $connection2, $URL, $gibbonExternalAssessmentStudentID;
    
    try {
        // Delete entries
        $data = ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID];
        $sql = 'DELETE FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        // Delete assessment
        $sql = 'DELETE FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithSuccess($URL, 'success0');
    } catch (PDOException $e) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error2');
    }
}

function handleFileUpload() {
    global $session, $pdo;
    
    if (!empty($_FILES['file']['tmp_name'])) {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);
        $file = $_FILES['file'] ?? null;
        return $fileUploader->uploadFromPost($file, 'externalAssessmentUpload');
    }
    
    return $_POST['attachment'] ?? null;
}

function redirectWithError($error) {
    global $URL;
    header("Location: {$URL}&return=$error");
    exit();
}

function redirectWithSuccess($success) {
    global $URL;
    header("Location: {$URL}&return=$success");
    exit();
}
