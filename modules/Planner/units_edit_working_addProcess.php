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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonUnitClassID = $_GET['gibbonUnitClassID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_edit_working.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit_working_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        $lessonsChecked = $_POST['lessons'] ?? [];

        //Proceed!
        //Validate Inputs
        if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $gibbonUnitID == '' or $gibbonCourseClassID == '' or $gibbonUnitClassID == '' or empty($lessonsChecked)) {
            $URL .= '&return=error1';
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
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check existence of specified unit
                try {
                    $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&deployReturn=fail2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();
                    $partialFail = false;

                    $plannerEntryGateway = $container->get(PlannerEntryGateway::class);

                    $lessons = $plannerEntryGateway->selectPlannerEntriesByUnitAndClass($gibbonUnitID, $gibbonCourseClassID)->fetchAll();
                    $lessonCount = count($lessons);

                    foreach ($lessonsChecked as $lesson) {
                        [$gibbonTTDayRowClassID, $gibbonTTDayDateID] = explode('-', $lesson);
                        $values = $plannerEntryGateway->getPlannerTTByIDs($gibbonTTDayRowClassID, $gibbonTTDayDateID);

                        $summary = 'Part of the '.$row['name'].' unit.';
                        $teachersNotes = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'teachersNotesTemplate');

                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $values['date'], 'timeStart' => $values['timeStart'], 'timeEnd' => $values['timeEnd'], 'gibbonUnitID' => $gibbonUnitID, 'name' => $row['name'].' '.($lessonCount + 1), 'summary' => $summary, 'teachersNotes' => $teachersNotes, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'));
                        $sql = "INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, name=:name, summary=:summary, description='', teachersNotes=:teachersNotes, homework='N', viewableParents='Y', viewableStudents='Y', gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit";

                        $inserted = $pdo->insert($sql, $data);
                        $partialFail &= !$inserted;
                        $lessonCount++;
                    }

                    //RETURN
                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
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
