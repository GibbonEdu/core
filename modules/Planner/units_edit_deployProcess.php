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
use Gibbon\Data\Validator;

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
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonUnitID == '' or $gibbonUnitClassID == '' or $orders == []) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $courseGateway = $container->get(CourseGateway::class);

            // Check access to specified course
            if ($highestAction == 'Unit Planner_all') {
                $result = $courseGateway->selectCourseDetailsByClass($gibbonCourseClassID);
            } elseif ($highestAction == 'Unit Planner_learningAreas') {
                $result = $courseGateway->selectCourseDetailsByClassAndPerson($gibbonCourseClassID, $session->get('gibbonPersonID'));
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error4';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit
                try {
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2b';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error4';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    $partialFail = false;

                    $lessonCount = 0;
                    $sequenceNumber = 0;
                    $lessDescriptions = array();
                    foreach ($orders as $order) {
                        //It is a lesson, so add it
                        if (strpos($order, 'lessonHeader-') !== false) {
                            $summary = 'Part of the '.$row['name'].' unit.';
                            $teachersNotes = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'teachersNotesTemplate');
                            $viewableStudents = $_POST['viewableStudents'] ?? 'N';
                            $viewableParents = $_POST['viewableParents'] ?? 'N';

                            try {
                                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $_POST["date$lessonCount"], 'timeStart' => $_POST["timeStart$lessonCount"], 'timeEnd' => $_POST["timeEnd$lessonCount"], 'gibbonUnitID' => $gibbonUnitID, 'name' => $row['name'].' '.($lessonCount + 1), 'summary' => $summary, 'teachersNotes' => $teachersNotes, 'viewableParents' => $viewableParents, 'viewableStudents' => $viewableStudents, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'));
                                $sql = "INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, name=:name, summary=:summary, description='', teachersNotes=:teachersNotes, homework='N', viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }

                            $AI = $connection2->lastInsertID();

                            $lessonDescriptions[$AI][0] = $AI;
                            $lessonDescriptions[$AI][1] = '';

                            ++$lessonCount;
                        }
                        //It is a block, so add it to the last added lesson
                        else {
                            $titles = $_POST['title'.$order] ?? '';
                            $lessonDescriptions[$AI][1] .= $titles.', ';
                            $types = $_POST['type'.$order] ?? '';
                            $lengths = $_POST['length'.$order] ?? '';
                            $contents = $_POST['contents'.$order] ?? '';
                            $teachersNotes = $_POST['teachersNotes'.$order] ?? '';
                            $gibbonUnitBlockID = $_POST['gibbonUnitBlockID'.$order] ?? '';

                            try {
                                $data = array('gibbonUnitClassID' => $gibbonUnitClassID, 'gibbonPlannerEntryID' => $AI, 'gibbonUnitBlockID' => $gibbonUnitBlockID, 'title' => $titles, 'type' => $types, 'length' => $lengths, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                $sql = "INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, complete='N'";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            ++$sequenceNumber;
                        }
                    }

                    //Update lesson description
                    foreach ($lessonDescriptions as $lessonDescription) {
                        $lessonDescription[1] = substr($lessonDescription[1], 0, -2);
                        if (strlen($lessonDescription[1]) > 75) {
                            $lessonDescription[1] = substr($lessonDescription[1], 0, 72).'...';
                        }
                        try {
                            $data = array('summary' => $lessonDescription[1], 'gibbonPlannerEntryID' => $lessonDescription[0]);
                            $sql = 'UPDATE gibbonPlannerEntry SET summary=:summary WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    //RETURN
                    if ($partialFail == true) {
                        $URL .= '&return=error6';
                        header("Location: {$URL}");
                    } else {
                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
