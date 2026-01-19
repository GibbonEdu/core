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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Data\Validator;
use Gibbon\Domain\Planner\UnitClassBlockGateway;
use Gibbon\Domain\Planner\UnitGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['contents*' => 'HTML', 'teachersNotes*' => 'HTML']);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';
$gibbonUnitClassID = $_GET['gibbonUnitClassID'] ?? '';
$orders = $_POST['order'] ?? [];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_deploy.php') == false) {
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
    $unit = $unitGateway->getByID($gibbonUnitID);
    if (empty($unit) || !$courseGateway->exists($gibbonCourseID)) {
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
    $lessonNumber = 1;
    $sequenceNumber = 0;

    foreach ($blocks as $blockIndex => $block) {

        if (substr($blockIndex, 0, 6) == 'lesson') {
            list($gibbonTTDayRowClassID, $gibbonTTDayDateID) = explode('-', $block);
            $lessonData = $plannerGateway->getPlannerTTByIDs($gibbonTTDayRowClassID, $gibbonTTDayDateID);

            $data = [
                'gibbonCourseClassID'    => $gibbonCourseClassID,
                'date'                   => $lessonData['date'],
                'timeStart'              => $lessonData['timeStart'],
                'timeEnd'                => $lessonData['timeEnd'],
                'gibbonUnitID'           => $gibbonUnitID,
                'name'                   => !empty($lessons[$block]) ? $lessons[$block] : trim($unit['name']).' '.$lessonNumber,
                'summary'                => $summary ?? '',
                'viewableParents'        => $_POST['viewableParents'] ?? 'N',
                'viewableStudents'       => $_POST['viewableStudents'] ?? 'N',
                'gibbonPersonIDCreator'  => $session->get('gibbonPersonID'),
                'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'),
            ];

            $gibbonPlannerEntryID = $plannerGateway->insert($data);
            $lessonNumber++;
            continue;
        }

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

        $gibbonUnitClassBlockID = $unitClassBlockGateway->insert($data);

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
