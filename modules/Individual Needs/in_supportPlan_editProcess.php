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
use Gibbon\Contracts\Filesystem\FileHandler;
use Gibbon\Domain\IndividualNeeds\StudentSupportPlanGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['filePath' => 'URL']);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? $_POST['gibbonPersonID'] ?? '';
$gibbonStudentSupportPlanID = $_GET['gibbonStudentSupportPlanID'] ?? $_POST['gibbonStudentSupportPlanID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/in_supportPlan_edit.php'
    .'&gibbonPersonID='.urlencode($gibbonPersonID)
    .'&gibbonStudentSupportPlanID='.urlencode($gibbonStudentSupportPlanID);

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_supportPlan_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

if (empty($gibbonStudentSupportPlanID) || empty($gibbonPersonID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$studentSupportPlanGateway = $container->get(StudentSupportPlanGateway::class);
$plan = $studentSupportPlanGateway->getByID($gibbonStudentSupportPlanID);

if (empty($plan) || $plan['gibbonPersonID'] != $gibbonPersonID) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

$gibbonSchoolYearID = $plan['gibbonSchoolYearID'];;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$newType = $_POST['type'] ?? '';
$viewableStaff = $_POST['viewableStaff'] ?? '';
$viewableParents = $_POST['viewableParents'] ?? '';
$active = $_POST['active'] ?? '';

if (empty($name) || empty($newType) || empty($viewableStaff) || empty($viewableParents) || empty($active)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$partialFail = false;
$fileMetaData = null;
$oldType = $plan['type'];
$fileHandler = $container->get(FileHandler::class);

// Determine new filePath
$newFilePath = $plan['filePath'];

if ($newType == 'File') {
    $hasNewFile = !empty($_FILES['file']['tmp_name']);

    if ($hasNewFile) {
        // Delete old file if it was a File type
        if ($oldType == 'File' && !empty($plan['filePath'])) {
            $fileHandler->deleteFile('gibbonStudentSupportPlan', $gibbonStudentSupportPlanID, 'filePath');
        }

        $fileUploader = new Gibbon\FileUploader($pdo, $session);
        $newFilePath = $fileUploader->uploadFromPost($_FILES['file'], 'in_supportPlan_');

        if (empty($newFilePath)) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        }

        $fileMetaData = $fileUploader->getFileMetaData($newFilePath);
    } else {
        // No new file — if switching from Link to File with no upload, error
        if ($oldType == 'Link') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
        // Keep existing filePath (no update to filePath)
        $newFilePath = $plan['filePath'];
    }
} elseif ($newType == 'Link') {
    $newFilePath = $_POST['filePath'] ?? '';

    if (empty($newFilePath)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Delete old physical file if switching from File to Link
    if ($oldType == 'File' && !empty($plan['filePath'])) {
        $fileHandler->deleteFile('gibbonStudentSupportPlan', $gibbonStudentSupportPlanID, 'filePath');
    }
}

$updated = $studentSupportPlanGateway->update($gibbonStudentSupportPlanID, [
    'gibbonSchoolYearID'     => $gibbonSchoolYearID,
    'active'                 => $active,
    'type'                   => $newType,
    'filePath'               => $newFilePath,
    'name'                   => $name,
    'description'            => !empty($description) ? $description : null,
    'viewableStaff'          => $viewableStaff,
    'viewableParents'        => $viewableParents,
    'timestampModified'      => date('Y-m-d H:i:s'),
    'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
]);

if (!$updated) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

if (!empty($fileMetaData)) {
    $gibbonFileID = $fileHandler->recordFileUpload($fileMetaData, 'gibbonStudentSupportPlan', $gibbonStudentSupportPlanID, 'filePath');
    if (empty($gibbonFileID)) {
        $partialFail = true;
    }
}

$URL .= $partialFail ? '&return=warning1' : '&return=success0';
header("Location: {$URL}");
exit;
