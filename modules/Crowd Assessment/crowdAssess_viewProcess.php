<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

include '../../functions.php';
include '../../config.php';

@session_start();

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/'.getModuleName($_GET['address']).'/moduleFunctions.php';

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
$gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'];
$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/crowdAssess_view.php&gibbonPlannerEntryID=$gibbonPlannerEntryID";

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonPlannerEntryID == '' or $gibbonPlannerEntryHomeworkID == '' or $gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $and = " AND gibbonPlannerEntryID=$gibbonPlannerEntryID";
        $sql = getLessons($guid, $connection2, $and);
        try {
            $result = $connection2->prepare($sql[1]);
            $result->execute($sql[0]);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $row = $result->fetch();

            $role = getCARole($guid, $connection2, $row['gibbonCourseClassID']);

            if ($role == '') {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $sqlList = getStudents($guid, $connection2, $role, $row['gibbonCourseClassID'], $row['homeworkCrowdAssessOtherTeachersRead'], $row['homeworkCrowdAssessOtherParentsRead'], $row['homeworkCrowdAssessSubmitterParentsRead'], $row['homeworkCrowdAssessClassmatesParentsRead'], $row['homeworkCrowdAssessOtherStudentsRead'], $row['homeworkCrowdAssessClassmatesRead'], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID");

                if ($sqlList[1] != '') {
                    try {
                        $resultList = $connection2->prepare($sqlList[1]);
                        $resultList->execute($sqlList[0]);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($resultList->rowCount() != 1) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                    } else {
                        //Check like status
                        $likesGiven = countLikesByContextAndGiver($connection2, 'Crowd Assessment', 'gibbonPlannerEntryHomeworkID', $gibbonPlannerEntryHomeworkID, $_SESSION[$guid]['gibbonPersonID']);
                        if ($likesGiven != 1) { //INSERT LIKE
                            $return = setLike($connection2, 'Crowd Assessment', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPlannerEntryHomeworkID', $gibbonPlannerEntryHomeworkID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonID, 'Crowd Assessment Feedback', $row['course'].'.'.$row['class'].': '.$row['name']);
                            if ($return == false) {
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                            } else {
                                $URL .= '&return=success0';
                                header("Location: {$URL}");
                            }
                        } else { //DELETE LIKE
                            $return = deleteLike($connection2, 'Crowd Assessment', 'gibbonPlannerEntryHomeworkID', $gibbonPlannerEntryHomeworkID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonID, 'Crowd Assessment Feedback');
                            if ($return == false) {
                                $URL .= '&return=error2';
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
    }
}
