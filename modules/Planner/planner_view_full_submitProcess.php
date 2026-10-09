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
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Planner\PlannerEntryHomeworkGateway;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['link' => 'URL']);

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
$currentDate = $_POST['currentDate'] ?? '';
$today = date('Y-m-d');
$params = [
    'date' => $_GET['date'] ?? '',
    'viewBy' => $_GET['viewBy'] ?? '',
    'gibbonCourseClassID' => $_GET['gibbonCourseClassID'] ?? '',
];

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&".http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=error6';
        header("Location: {$URL}");
        exit;
    } 
    
    //Proceed!
    //Check if planner specified
    if ($gibbonPlannerEntryID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 

    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);
    $plannerEntryHomeworkGateway = $container->get(PlannerEntryHomeworkGateway::class);

    $values = $plannerEntryGateway->getByID($gibbonPlannerEntryID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    //Check that date is not in the future
    if ($currentDate > $today) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    } 

    //Get variables
    $type = $_POST['type'] ?? '';
    $version = $_POST['version'] ?? '';
    $link = $_POST['link'] ?? '';
    $status = $_POST['status'] ?? '';
    $timestamp = date('Y-m-d H:i:s') ?? '';
    $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'] ?? '';
    $count = $_POST['count'] ?? '';
    $lesson = $_POST['lesson'] ?? '';

    //Recheck status in case page held open during the deadline
    if ($timestamp > $values['homeworkDueDateTime']) {
        $status = 'Late';
    }

    //Validation
    if ($type == '' || $version == '' || (empty($_FILES['file']['name']) && $link == '') || $status == '' || $count == '' || $lesson == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } 

    $partialFail = false;
    $attachment = null;
    $fileMetaData = null;

    if ($type == 'Link') {
        if (substr($link, 0, 7) != 'http://' and substr($link, 0, 8) != 'https://') {
            $partialFail = true;
        } else {
            $attachment = $link;
        }
    } elseif ($type == 'File') {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);

        $file = (isset($_FILES['file']))? $_FILES['file'] : null;

        // Upload the file, return the /uploads relative path
        $attachment = $fileUploader->uploadFromPost($file, $session->get('username').'_'.$lesson);

        if (empty($attachment)) {
            $partialFail = true;
        } else {
            $fileMetaData = $fileUploader->getFileMetaData($attachment);
        }
    } else {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Write to database
    $gibbonPlannerEntryHomeworkID = $plannerEntryHomeworkGateway->insert(['gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'type' => $type, 'version' => $version, 'status' => $status, 'location' => $attachment, 'count' => ($count + 1), 'timestamp' => $timestamp]);

    if (!$gibbonPlannerEntryHomeworkID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Record file tracking (only if file uploaded)
    if (!empty($fileMetaData) && !empty($gibbonPlannerEntryHomeworkID)) {
        $gibbonFileID = $container->get(FileHandler::class)->recordFileUpload($fileMetaData, 'gibbonPlannerEntryHomework', $gibbonPlannerEntryHomeworkID, 'location');

        if (empty($gibbonFileID)) {
            $partialFail = true;
        }
    }

    // Deal with partial fail
    if ($partialFail == true) {
        $URL .= '&return=error6';
        header("Location: {$URL}");
        exit;
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");

}
