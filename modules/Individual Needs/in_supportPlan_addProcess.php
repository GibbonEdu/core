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

use Gibbon\Contracts\Filesystem\FileHandler;
use Gibbon\Data\Validator;
use Gibbon\Domain\IndividualNeeds\StudentSupportPlanGateway;
use Gibbon\FileUploader;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['filePath' => 'URL']);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? $_POST['gibbonPersonID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/in_supportPlan_add.php&gibbonPersonID='.urlencode($gibbonPersonID);

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_supportPlan_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$type = $_POST['type'] ?? '';
$viewableStaff = $_POST['viewableStaff'] ?? '';
$viewableParents = $_POST['viewableParents'] ?? '';
$active = $_POST['active'] ?? '';

if (empty($gibbonPersonID) || empty($gibbonSchoolYearID) || empty($name) || empty($type) || empty($viewableStaff) || empty($viewableParents) || empty($active)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$partialFail = false;
$filePath = '';
$fileMetaData = null;

if ($type == 'File') {
    if (empty($_FILES['file']['tmp_name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $fileUploader = new FileUploader($pdo, $session);
    $filePath = $fileUploader->uploadFromPost($_FILES['file'], 'in_supportPlan_');

    if (empty($filePath)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    $fileMetaData = $fileUploader->getFileMetaData($filePath);
} elseif ($type == 'Link') {
    $filePath = $_POST['filePath'] ?? '';

    if (empty($filePath)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
}

$studentSupportPlanGateway = $container->get(StudentSupportPlanGateway::class);
$studentSupportPlanID = $studentSupportPlanGateway->insert([
    'gibbonSchoolYearID'    => $gibbonSchoolYearID,
    'gibbonPersonID'        => $gibbonPersonID,
    'active'                => $active,
    'type'                  => $type,
    'filePath'              => $filePath,
    'name'                  => $name,
    'description'           => !empty($description) ? $description : null,
    'viewableStaff'         => $viewableStaff,
    'viewableParents'       => $viewableParents,
    'gibbonPersonIDCreated' => $session->get('gibbonPersonID'),
    'gibbonPersonIDModified'=> $session->get('gibbonPersonID'),
]);

if (!$studentSupportPlanID) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

if (!empty($fileMetaData)) {
    $gibbonFileID = $container->get(FileHandler::class)->recordFileUpload($fileMetaData, 'gibbonStudentSupportPlan', $studentSupportPlanID, 'filePath');
    if (empty($gibbonFileID)) {
        $partialFail = true;
    }
}

if ($partialFail) {
    $URL .= '&return=warning1';
} else {
    $URL .= '&return=success0&editID='.$studentSupportPlanID;
}

header("Location: {$URL}");
exit;
