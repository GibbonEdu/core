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
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Planner\UnitClassBlockGateway;
use Gibbon\Domain\Planner\UnitGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['contents*' => 'HTML', 'teachersNotes*' => 'HTML']);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';
$gibbonUnitClassID = $_GET['gibbonUnitClassID'] ?? '';
$lessonNameReplace = $_POST['lessonNameReplace'] ?? 'N';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_edit_working.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0";
        header("Location: {$URL}");
        exit;
    } 

    // Validate Inputs
    if (empty($gibbonSchoolYearID) || empty($gibbonCourseID) || empty($gibbonUnitID) ) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';
    // die();


    $validator = $container->get(Validator::class);
    $courseGateway = $container->get(CourseGateway::class);
    $unitGateway = $container->get(UnitGateway::class);
    $plannerGateway = $container->get(PlannerEntryGateway::class);
    $unitClassBlockGateway = $container->get(UnitClassBlockGateway::class);

    // Check access to specified course
    if ($highestAction == 'Unit Planner_all') {
        $result = $courseGateway->selectCourseDetailsByClass($gibbonCourseClassID);
    } elseif ($highestAction == 'Unit Planner_learningAreas') {
        $result = $courseGateway->selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $session->get('gibbonPersonID'));
    }

    if ($result->rowCount() != 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } 

    // Check existence of specified unit
    if (!$unitGateway->exists($gibbonUnitID) || !$courseGateway->exists($gibbonCourseID)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } 

    $partialFail = false;
    
    $lessonDescriptions = [];

    
    $blockIDs = [];
    $blocks = $_POST['blocks'] ?? [];
    $lessons = $_POST['lessons'] ?? [];
    $count = $_POST['blocksCount'] ?? 0;

    $gibbonPlannerEntryID = 0;
    $sequenceNumber = 0;

    foreach ($blocks as $blockIndex => $block) {

        if (substr($blockIndex, 0, 6) == 'lesson') {
            $gibbonPlannerEntryID = $block;
            if (!empty($lessons[$gibbonPlannerEntryID])) {
                $plannerGateway->update($gibbonPlannerEntryID, ['name' => $lessons[$gibbonPlannerEntryID]]);
            }
            continue;
        }

        $gibbonUnitClassBlockID = $block['gibbonUnitClassBlockID'] ?? null;
        $data = [
            'gibbonUnitClassID'    => $gibbonUnitClassID,
            'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
            'gibbonUnitBlockID'    => $block['gibbonUnitBlockID'] ?? '',
            'title'                => $block['title'] ?? '',
            'type'                 => $block['type'] ?? '',
            'length'               => $block['length'] ?? '',
            'complete'             => $block['complete'] ?? 'N',
            'contents'             => $block['contents'] ?? '',
            'teachersNotes'        => $block['teachersNotes'] ?? '',
            'sequenceNumber'       => $sequenceNumber,
        ];

        if (!empty($gibbonUnitClassBlockID) && $existingBlock = $unitClassBlockGateway->getByID($gibbonUnitClassBlockID)) {
            $unitClassBlockGateway->update($gibbonUnitClassBlockID, $data);
        } else {
            $gibbonUnitClassBlockID = $unitClassBlockGateway->insert($data);
        }

        if (!empty($gibbonUnitClassBlockID)) {
            $gibbonUnitClassBlockID = str_pad($gibbonUnitClassBlockID, 14, '0', STR_PAD_LEFT);
            $blockIDs[] = $gibbonUnitClassBlockID;
        } else {
            $partialFail = true;
        }

        ++$sequenceNumber;
    }

    // Remove deleted blocks
    $unitClassBlockGateway->deleteBlocksNotInList($gibbonUnitClassID, $blockIDs);

    //Update lesson description
    // foreach ($lessonDescriptions as $lessonDescription) {
    //     $lessonDescription[1] = substr($lessonDescription[1], 0, -2);
    //     if (strlen($lessonDescription[1]) > 75) {
    //         $lessonDescription[1] = substr($lessonDescription[1], 0, 72).'...';
    //     }
    //     try {
    //         $data = array('summary' => $lessonDescription[1], 'gibbonPlannerEntryID' => $lessonDescription[0]);
    //         $sql = 'UPDATE gibbonPlannerEntry SET summary=:summary WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
    //         $result = $connection2->prepare($sql);
    //         $result->execute($data);
    //     } catch (PDOException $e) {
    //         $partialFail = true;
    //     }

    //     if ($lessonNameReplace == 'Y' && !empty($lessonDescription[2])) {
    //         $plannerGateway->update($lessonDescription[0], ['name' => $lessonDescription[2]]);
    //     }
    // }

    //RETURN
    if ($partialFail == true) {
        $URL .= '&updateReturn=error6';
        header("Location: {$URL}");
        exit;
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    }
}
