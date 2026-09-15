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
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Planner\PlannerEntryHomeworkGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['link' => 'URL']);

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=".$_POST['search'].($_POST['params'] ?? '');

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full_submit_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $highestAction = getHighestGroupedAction($guid, '/modules/Planner/planner_view_full_submit_edit.php', $connection2); 
    if (empty($highestAction)) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
        exit;
    } 

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

    if ($_POST['submission'] != 'true' and $_POST['submission'] != 'false') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 

    if ($_POST['submission'] == 'true') {
        $submission = true;
        $gibbonPlannerEntryHomeworkID = $_POST['gibbonPlannerEntryHomeworkID'] ?? '';
    } else {
        $submission = false;
    }

    $type = $_POST['type'] ?? '';
    $version = $_POST['version'] ?? '';
    $link = $_POST['link'] ?? '';
    $status = $_POST['status'] ?? '';
    $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'] ?? '';
    $count = $_POST['count'] ?? '';
    $lesson = $_POST['lesson'] ?? '';
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';


    if (($submission == true and $gibbonPlannerEntryHomeworkID == '') or ($submission == false and ($gibbonPersonID == '' or $type == '' or $version == '' or ($type == 'File' and $_FILES['file']['name'] == '') or ($type == 'Link' and $link == '') or $status == '' or $lesson == '' or $count == ''))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Ensure the student and/or teacher is part of the target class
    if ($highestAction == 'Lesson Planner_viewEditAllClasses') {
        $plannerClass = $plannerEntryGateway->getPlannerClassAccessByPerson($gibbonPlannerEntryID, $gibbonPersonID);
    } elseif ($highestAction == 'Lesson Planner_viewAllEditMyClasses') {
        $plannerClass = $plannerEntryGateway->getPlannerClassAccessByPerson($gibbonPlannerEntryID, $session->get('gibbonPersonID'));
    }

    if (empty($plannerClass)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($submission == true) {
        $plannerEntryHomeworkGateway->update($gibbonPlannerEntryHomeworkID, ['status' => $status]);

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    } 

    $partialFail = false;
    $attachment = null;
    if ($type == 'Link') {
        if (substr($link, 0, 7) != 'http://' and substr($link, 0, 8) != 'https://') {
            $partialFail = true;
        } else {
            $attachment = $link;
        }
    }

    if ($type == 'File') {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);

        $file = (isset($_FILES['file']))? $_FILES['file'] : null;

        // Upload the file, return the /uploads relative path
        $attachment = $fileUploader->uploadFromPost($file, $session->get('username').'_'.$lesson);

        if (empty($attachment)) {
            $partialFail = true;
        }
    }

    //Deal with partial fail
    if ($partialFail == true) {
        $URL .= '&return=error6';
        header("Location: {$URL}");
        exit;
    } 

    //Write to database
    $inserted = $plannerEntryHomeworkGateway->insert(['gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'version' => $version, 'status' => $status, 'location' => $attachment, 'count' => ($count + 1), 'timestamp' => date('Y-m-d H:i:s')]);

    if (!$inserted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
