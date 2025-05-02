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
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'] ?? '')."/internalAssessment_manage.php&gibbonCourseClassID=$gibbonCourseClassID";

// Common validation for all actions
if (!\Gibbon\Module\FormalAssessment\AssessmentValidator::validateRequiredParams($guid, $connection2, $action, [$gibbonCourseClassID, $gibbonInternalAssessmentColumnID])) {
    \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error1');
}

// Route to appropriate handler
switch ($action) {
    case 'add': handleAdd(); break;
    case 'edit': handleEdit(); break;
    case 'delete': handleDelete(); break;
    default: redirectWithError('error1');
}

function validateRequiredParams($action, $gibbonCourseClassID, $gibbonInternalAssessmentColumnID) {
    global $session, $URL;
    
    // Common access check
    if (!isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_manage.php')) {
        redirectWithError('error0');
        return false;
    }

    // Action-specific checks
    if ($action == 'add' && empty($_POST)) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error3');
        return false;
    }

    if (in_array($action, ['edit', 'delete']) && empty($gibbonInternalAssessmentColumnID)) {
        redirectWithError('error1');
        return false;
    }

    return true;
}

function handleAdd() {
    global $session, $connection2, $URL;
    
    $required = ['name', 'description', 'type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            redirectWithError('error1');
        }
    }

    try {
        $data = [
            'groupingID' => $_POST['groupingID'] ?? null,
            'gibbonCourseClassID' => $_POST['gibbonCourseClassID'] ?? '',
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'type' => $_POST['type'] ?? '',
            'attainment' => $_POST['attainment'] ?? '',
            'gibbonScaleIDAttainment' => $_POST['gibbonScaleIDAttainment'] ?? null,
            'effort' => $_POST['effort'] ?? '',
            'gibbonScaleIDEffort' => $_POST['gibbonScaleIDEffort'] ?? null,
            'comment' => $_POST['comment'] ?? '',
            'uploadedResponse' => $_POST['uploadedResponse'] ?? '',
            'completeDate' => $_POST['completeDate'] ?? null,
            'complete' => $_POST['complete'] ?? 'N',
            'viewableStudents' => $_POST['viewableStudents'] ?? '',
            'viewableParents' => $_POST['viewableParents'] ?? '',
            'attachment' => $_POST['attachment'] ?? '',
            'gibbonPersonIDCreator' => $session->get('gibbonPersonID'),
            'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID')
        ];
        
        $sql = 'INSERT INTO gibbonInternalAssessmentColumn SET groupingID=:groupingID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithSuccess($URL, 'success0');
    } catch (PDOException $e) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error2');
    }
}

function handleEdit() {
    global $session, $connection2, $URL, $gibbonInternalAssessmentColumnID, $gibbonCourseClassID;
    
    try {
        $data = [
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'type' => $_POST['type'] ?? '',
            'attainment' => $_POST['attainment'] ?? '',
            'gibbonScaleIDAttainment' => $_POST['gibbonScaleIDAttainment'] ?? null,
            'effort' => $_POST['effort'] ?? '',
            'gibbonScaleIDEffort' => $_POST['gibbonScaleIDEffort'] ?? null,
            'comment' => $_POST['comment'] ?? '',
            'uploadedResponse' => $_POST['uploadedResponse'] ?? '',
            'completeDate' => $_POST['completeDate'] ?? null,
            'complete' => $_POST['complete'] ?? 'N',
            'viewableStudents' => $_POST['viewableStudents'] ?? '',
            'viewableParents' => $_POST['viewableParents'] ?? '',
            'attachment' => $_POST['attachment'] ?? '',
            'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'),
            'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID
        ];
        
        $sql = 'UPDATE gibbonInternalAssessmentColumn SET gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithSuccess($URL, 'success0');
    } catch (PDOException $e) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error2');
    }
}

function handleDelete() {
    global $connection2, $URL, $gibbonInternalAssessmentColumnID;
    
    try {
        $data = ['gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID];
        $sql = 'DELETE FROM gibbonInternalAssessmentColumn WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
        
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithSuccess($URL, 'success0');
    } catch (PDOException $e) {
        \Gibbon\Module\FormalAssessment\AssessmentValidator::redirectWithError($URL, 'error2');
    }
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
 header("Location: {$URL}&return=$success");
    exit();
}
