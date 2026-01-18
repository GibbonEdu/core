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
use Gibbon\Domain\Planner\UnitBlockGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['contents*' => 'HTML', 'teachersNotes*' => 'HTML']);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';
$gibbonUnitClassID = $_GET['gibbonUnitClassID'] ?? '';
$unitBlockCount = $_POST['unitBlockCount'] ?? 0;

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

    $validator = $container->get(Validator::class);
    $courseGateway = $container->get(CourseGateway::class);
    $unitGateway = $container->get(UnitGateway::class);
    $plannerGateway = $container->get(PlannerEntryGateway::class);
    $unitBlockGateway = $container->get(UnitBlockGateway::class);
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
    
    $blockIDs = [];
    $blocks = $_POST['blocks'] ?? [];
    $lessons = $_POST['lessons'] ?? [];
    $lessonDetails = [];

    $gibbonPlannerEntryID = 0;
    $sequenceNumber = 0;

    foreach ($blocks as $blockIndex => $block) {

        if (substr($blockIndex, 0, 6) == 'lesson') {
            $gibbonPlannerEntryID = $block;
            if (!empty($lessons[$gibbonPlannerEntryID])) {
                $lessonDetails[$gibbonPlannerEntryID]['name'] = $lessons[$gibbonPlannerEntryID];
            }
            continue;
        }

        $gibbonUnitClassBlockID = $block['gibbonUnitClassBlockID'] ?? null;

        $blockData = [
            'gibbonUnitClassID'    => $gibbonUnitClassID,
            'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
            'complete'             => $block['complete'] ?? 'N',
        ];

        $data = [
            'gibbonUnitBlockID'    => $block['gibbonUnitBlockID'] ?? '',
            'title'                => $block['title'] ?? '',
            'type'                 => $block['type'] ?? '',
            'length'               => $block['length'] ?? '',
            'contents'             => $block['contents'] ?? '',
            'teachersNotes'        => $block['teachersNotes'] ?? '',
            'sequenceNumber'       => $sequenceNumber,
        ];

        // Add new unit blocks
        if (empty($data['gibbonUnitBlockID'])) {
            $data['gibbonUnitBlockID'] = $unitBlockGateway->insert([
                'gibbonUnitID'   => $gibbonUnitID,
                'sequenceNumber' => $unitBlockCount + 1,
            ] + $data);
        }

        if (!empty($gibbonUnitClassBlockID) && $existingBlock = $unitClassBlockGateway->getByID($gibbonUnitClassBlockID)) {
            $unitClassBlockGateway->update($gibbonUnitClassBlockID, $blockData + $data);
        } else {
            $gibbonUnitClassBlockID = $unitClassBlockGateway->insert($blockData + $data);
        }

        // Update lesson details based on the first block
        if (empty($lessonDetails[$gibbonPlannerEntryID])) {
            $contents = strip_tags($data['contents']);
            $lessonDetails[$gibbonPlannerEntryID]['summary'] = strlen($contents) > 72 ? substr($contents, 0, 72) : $contents;
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

    // Update lesson details
    foreach ($lessonDetails as $gibbonPlannerEntryID => $details) {
        $plannerGateway->update($gibbonPlannerEntryID, $details);
    }

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
