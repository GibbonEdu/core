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

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/'.getModuleName($_GET['address']).'/moduleFunctions.php';

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
$viewBy = null;
if (isset($_GET['viewBy'])) {
    $viewBy = $_GET['viewBy'];
}
$subView = null;
if (isset($_GET['subView'])) {
    $subView = $_GET['subView'];
}
if ($viewBy != 'date' and $viewBy != 'class') {
    $viewBy = 'date';
}
$gibbonCourseClassID = null;
if (isset($_GET['gibbonCourseClassID'])) {
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
}
$date = $_GET['date'];
$returnToIndex = null;
if (isset($_GET['returnToIndex'])) {
    $returnToIndex = $_GET['returnToIndex'];
}
$gibbonPersonID2 = null;
if (isset($_GET['gibbonPersonID'])) {
    $gibbonPersonID2 = $_GET['gibbonPersonID'];
}
if ($returnToIndex == 'Y') {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?blank=blank';
    $params = "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView&search=$gibbonPersonID2";
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/planner.php&gibbonPlannerEntryID=$gibbonPlannerEntryID";

    //Params to pass back (viewBy + date or classID)
    if ($viewBy == 'date') {
        $params = "&viewBy=$viewBy&date=$date&search=".$gibbonPersonID2;
    } else {
        $params = "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView&search=$gibbonPersonID2";
    }
}

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php') == false) {
    $URL .= "&return=error0$params";
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonPlannerEntryID == '' or ($viewBy == 'class' and $gibbonCourseClassID == '')) {
        $URL .= "&return=error1$params";
        header("Location: {$URL}");
    } else {
        //Get action with highest precendence
        $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
        if ($highestAction == false) {
            $URL .= "&return=error0$params";
            header("Location: {$URL}");
        } else {
            $proceed = true;
            if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                try {
                    $dataChild = array('gibbonPersonID1' => $gibbonPersonID2, 'gibbonPersonID2' => $gibbonPersonID);
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                } catch (PDOException $e) {
                    $proceed = false;
                }
                if ($resultChild->rowCount() != 1) {
                    $proceed = false;
                } else {
                    $data = array('gibbonPersonID' => $gibbonPersonID2, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'date' => $date, 'gibbonPersonID2' => $gibbonPersonID2, 'gibbonPlannerEntryID2' => $gibbonPlannerEntryID);
                    $sql = "(SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID) UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClass.gibbonCourseClassID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonPlannerEntry.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, date, timeStart, timeEnd, summary, gibbonPlannerEntry.description, teachersNotes, homework, homeworkDueDateTime, homeworkDetails, viewableStudents, viewableParents, role, homeworkSubmission, homeworkSubmissionDateOpen, homeworkSubmissionDrafts, homeworkSubmissionType FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2 AND gibbonPlannerEntry.gibbonPlannerEntryID=:gibbonPlannerEntryID2) ORDER BY date, timeStart";
                }
            }
            if ($viewBy == 'date') {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = 'SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Teacher' AND gibbonPlannerEntryID=:gibbonPlannerEntryID";
                }
            } else {
                if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = 'SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID';
                } elseif ($highestAction == 'Lesson Planner_viewMyClasses') {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = "SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Teacher' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPlannerEntryID=:gibbonPlannerEntryID";
                }
            }

            if ($proceed == false) {
                $URL .= "&return=error2$params";
                header("Location: {$URL}");
            } else {
                try {
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= "&return=error2$params";
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= "&return=error2$params";
                    header("Location: {$URL}");
                } else {
                    //Check like statatus
                    $likesGiven = countLikesByContextAndGiver($connection2, 'Planner', 'gibbonPlannerEntryID', $gibbonPlannerEntryID, $_SESSION[$guid]['gibbonPersonID']);
                    if ($likesGiven != 1) {
                        //One like for each teacher
                        $insertFail = false;
                        try {
                            $dataTeachers = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                            $sqlTeachers = "SELECT gibbonPlannerEntry.name, gibbonCourseClassPerson.gibbonPersonID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND role='Teacher' AND status='Full'";
                            $resultTeachers = $connection2->prepare($sqlTeachers);
                            $resultTeachers->execute($dataTeachers);
                        } catch (PDOException $e) {
                            $insertFail = true;
                        }

                        while ($rowTeachers = $resultTeachers->fetch()) {
                            $return = setLike($connection2, 'Planner', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPlannerEntryID', $gibbonPlannerEntryID, $_SESSION[$guid]['gibbonPersonID'], $rowTeachers['gibbonPersonID'], 'Planner - Lesson Design', $rowTeachers['course'].'.'.$rowTeachers['class'].': '.$rowTeachers['name']);
                            if ($return == false) {
                                $insertFail = true;
                            }
                        }

                        if ($insertFail == true) {
                            $URL .= "&return=error2$params";
                            header("Location: {$URL}");
                        } else {
                            $URL .= "&return=success0$params";
                            header("Location: {$URL}");
                        }
                    }
                    //DELETE
                    else {
                        //One like for each teacher
                        $insertFail = false;
                        try {
                            $dataTeachers = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                            $sqlTeachers = "SELECT gibbonCourseClassPerson.gibbonPersonID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND role='Teacher' AND status='Full'";
                            $resultTeachers = $connection2->prepare($sqlTeachers);
                            $resultTeachers->execute($dataTeachers);
                        } catch (PDOException $e) {
                            $insertFail = true;
                        }

                        while ($rowTeachers = $resultTeachers->fetch()) {
                            $return = deleteLike($connection2, 'Planner', 'gibbonPlannerEntryID', $gibbonPlannerEntryID, $_SESSION[$guid]['gibbonPersonID'], $rowTeachers['gibbonPersonID'], 'Planner - Lesson Design');
                            if ($return == false) {
                                $insertFail = true;
                            }
                        }

                        if ($insertFail == true) {
                            $URL .= "&return=error2$params";
                            header("Location: {$URL}");
                        } else {
                            $URL .= "&return=success0$params";
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
