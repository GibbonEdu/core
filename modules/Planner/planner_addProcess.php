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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address']).'/planner_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        if (empty($_POST)) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Validate Inputs
            $viewBy = $_GET['viewBy'];
            $subView = $_GET['subView'];
            if ($viewBy != 'date' and $viewBy != 'class') {
                $viewBy = 'date';
            }
            $gibbonCourseClassID = $_POST['gibbonCourseClassID'];
            $date = dateConvert($guid, $_POST['date']);
            $timeStart = $_POST['timeStart'];
            $timeEnd = $_POST['timeEnd'];
            @$gibbonUnitID = $_POST['gibbonUnitID'];
            if ($gibbonUnitID == '') {
                $gibbonUnitID = null;
                $gibbonHookID = null;
            } else {
                //Check for hooked unit (will have - in value)
                if (strpos($gibbonUnitID, '-') == false or strpos($gibbonUnitID, '-') == 0) {
                    //No hook
                    $gibbonUnitID = $gibbonUnitID;
                    $gibbonHookID = null;
                } else {
                    //Hook!
                    $gibbonUnitID = substr($_POST['gibbonUnitID'], 0, strpos($gibbonUnitID, '-'));
                    $gibbonHookID = substr($_POST['gibbonUnitID'], (strpos($_POST['gibbonUnitID'], '-') + 1));
                }
            }
            $name = $_POST['name'];
            $summary = $_POST['summary'];
            $description = $_POST['description'];
            $teachersNotes = $_POST['teachersNotes'];
            $homework = $_POST['homework'];
            if ($_POST['homework'] == 'Yes') {
                $homework = 'Y';
                $homeworkDetails = $_POST['homeworkDetails'];
                if ($_POST['homeworkDueDateTime'] != '') {
                    $homeworkDueDateTime = $_POST['homeworkDueDateTime'].':59';
                } else {
                    $homeworkDueDateTime = '21:00:00';
                }
                if ($_POST['homeworkDueDate'] != '') {
                    $homeworkDueDate = dateConvert($guid, $_POST['homeworkDueDate']).' '.$homeworkDueDateTime;
                }

                if ($_POST['homeworkSubmission'] == 'Yes') {
                    $homeworkSubmission = 'Y';
                    if ($_POST['homeworkSubmissionDateOpen'] != '') {
                        $homeworkSubmissionDateOpen = dateConvert($guid, $_POST['homeworkSubmissionDateOpen']);
                    } else {
                        $homeworkSubmissionDateOpen = dateConvert($guid, $_POST['date']);
                    }
                    $homeworkSubmissionDrafts = $_POST['homeworkSubmissionDrafts'];
                    $homeworkSubmissionType = $_POST['homeworkSubmissionType'];
                    $homeworkSubmissionRequired = $_POST['homeworkSubmissionRequired'];
                    if ($_POST['homeworkCrowdAssess'] == 'Yes') {
                        $homeworkCrowdAssess = 'Y';
                        if (isset($_POST['homeworkCrowdAssessOtherTeachersRead'])) {
                            $homeworkCrowdAssessOtherTeachersRead = 'Y';
                        } else {
                            $homeworkCrowdAssessOtherTeachersRead = 'N';
                        }
                        if (isset($_POST['homeworkCrowdAssessClassmatesRead'])) {
                            $homeworkCrowdAssessClassmatesRead = 'Y';
                        } else {
                            $homeworkCrowdAssessClassmatesRead = 'N';
                        }
                        if (isset($_POST['homeworkCrowdAssessOtherStudentsRead'])) {
                            $homeworkCrowdAssessOtherStudentsRead = 'Y';
                        } else {
                            $homeworkCrowdAssessOtherStudentsRead = 'N';
                        }
                        if (isset($_POST['homeworkCrowdAssessSubmitterParentsRead'])) {
                            $homeworkCrowdAssessSubmitterParentsRead = 'Y';
                        } else {
                            $homeworkCrowdAssessSubmitterParentsRead = 'N';
                        }
                        if (isset($_POST['homeworkCrowdAssessClassmatesParentsRead'])) {
                            $homeworkCrowdAssessClassmatesParentsRead = 'Y';
                        } else {
                            $homeworkCrowdAssessClassmatesParentsRead = 'N';
                        }
                        if (isset($_POST['homeworkCrowdAssessOtherParentsRead'])) {
                            $homeworkCrowdAssessOtherParentsRead = 'Y';
                        } else {
                            $homeworkCrowdAssessOtherParentsRead = 'N';
                        }
                    } else {
                        $homeworkCrowdAssess = 'N';
                        $homeworkCrowdAssessOtherTeachersRead = 'N';
                        $homeworkCrowdAssessClassmatesRead = 'N';
                        $homeworkCrowdAssessOtherStudentsRead = 'N';
                        $homeworkCrowdAssessSubmitterParentsRead = 'N';
                        $homeworkCrowdAssessClassmatesParentsRead = 'N';
                        $homeworkCrowdAssessOtherParentsRead = 'N';
                    }
                } else {
                    $homeworkSubmission = 'N';
                    $homeworkSubmissionDateOpen = null;
                    $homeworkSubmissionType = '';
                    $homeworkSubmissionDrafts = null;
                    $homeworkSubmissionRequired = null;
                    $homeworkCrowdAssess = 'N';
                    $homeworkCrowdAssessOtherTeachersRead = 'N';
                    $homeworkCrowdAssessClassmatesRead = 'N';
                    $homeworkCrowdAssessOtherStudentsRead = 'N';
                    $homeworkCrowdAssessSubmitterParentsRead = 'N';
                    $homeworkCrowdAssessClassmatesParentsRead = 'N';
                    $homeworkCrowdAssessOtherParentsRead = 'N';
                }
            } else {
                $homework = 'N';
                $homeworkDueDate = null;
                $homeworkDetails = '';
                $homeworkSubmission = 'N';
                $homeworkSubmissionDateOpen = null;
                $homeworkSubmissionType = '';
                $homeworkSubmissionDrafts = null;
                $homeworkSubmissionRequired = null;
                $homeworkCrowdAssess = 'N';
                $homeworkCrowdAssessOtherTeachersRead = 'N';
                $homeworkCrowdAssessClassmatesRead = 'N';
                $homeworkCrowdAssessOtherStudentsRead = 'N';
                $homeworkCrowdAssessSubmitterParentsRead = 'N';
                $homeworkCrowdAssessClassmatesParentsRead = 'N';
                $homeworkCrowdAssessOtherParentsRead = 'N';
            }

            $viewableParents = $_POST['viewableParents'];
            $viewableStudents = $_POST['viewableStudents'];
            $gibbonPersonIDCreator = $_SESSION[$guid]['gibbonPersonID'];
            $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];

            //Params to pass back (viewBy + date or classID)
            if ($viewBy == 'date') {
                $params = "&viewBy=$viewBy&date=$date";
            } else {
                $params = "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView";
            }

            //Lock markbook column table
            try {
                $sql = 'LOCK TABLES gibbonPlannerEntry WRITE, gibbonPlannerEntryGuest WRITE, gibbonCourseClassPerson WRITE, gibbonPlannerEntryOutcome WRITE';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
                $URL .= "&return=error2$params";
                header("Location: {$URL}");
                exit();
            }

            //Get next autoincrement
            try {
                $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonPlannerEntry'";
                $resultAI = $connection2->query($sqlAI);
            } catch (PDOException $e) {
                $URL .= "&return=error2$params";
                header("Location: {$URL}");
                exit();
            }

            $rowAI = $resultAI->fetch();
            $AI = str_pad($rowAI['Auto_increment'], 14, '0', STR_PAD_LEFT);

            if ($viewBy == '' or $gibbonCourseClassID == '' or $date == '' or $timeStart == '' or $timeEnd == '' or $name == '' or $summary == '' or $homework == '' or $viewableParents == '' or $viewableStudents == '' or ($homework == 'Y' and ($homeworkDetails == '' or $homeworkDueDate == ''))) {
                $URL .= "&return=error1$params";
                header("Location: {$URL}");
            } else {
                $partialFail = false;

                //Scan through guests
                $guests = null;
                if (isset($_POST['guests'])) {
                    $guests = $_POST['guests'];
                }
                $role = $_POST['role'];
                if ($role == '') {
                    $role = 'Student';
                }
                if (count($guests) > 0) {
                    foreach ($guests as $t) {
                        //Check to see if person is already registered in this class
                        try {
                            $dataGuest = array('gibbonPersonID' => $t, 'gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlGuest = 'SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID';
                            $resultGuest = $connection2->prepare($sqlGuest);
                            $resultGuest->execute($dataGuest);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        if ($resultGuest->rowCount() == 0) {
                            try {
                                $data = array('gibbonPersonID' => $t, 'gibbonPlannerEntryID' => $AI, 'role' => $role);
                                $sql = 'INSERT INTO gibbonPlannerEntryGuest SET gibbonPersonID=:gibbonPersonID, gibbonPlannerEntryID=:gibbonPlannerEntryID, role=:role';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Insert outcomes
                $count = 0;
                if (isset($_POST['outcomeorder'])) {
                    if (count($_POST['outcomeorder']) > 0) {
                        foreach ($_POST['outcomeorder'] as $outcome) {
                            if ($_POST["outcomegibbonOutcomeID$outcome"] != '') {
                                try {
                                    $dataInsert = array('AI' => $AI, 'gibbonOutcomeID' => $_POST["outcomegibbonOutcomeID$outcome"], 'content' => $_POST["outcomecontents$outcome"], 'count' => $count);
                                    $sqlInsert = 'INSERT INTO gibbonPlannerEntryOutcome SET gibbonPlannerEntryID=:AI, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count';
                                    $resultInsert = $connection2->prepare($sqlInsert);
                                    $resultInsert->execute($dataInsert);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                            ++$count;
                        }
                    }
                }

                //Write to database
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'gibbonUnitID' => $gibbonUnitID, 'gibbonHookID' => $gibbonHookID, 'name' => $name, 'summary' => $summary, 'description' => $description, 'teachersNotes' => $teachersNotes, 'homework' => $homework, 'homeworkDueDate' => $homeworkDueDate, 'homeworkDetails' => $homeworkDetails, 'homeworkSubmission' => $homeworkSubmission, 'homeworkSubmissionDateOpen' => $homeworkSubmissionDateOpen, 'homeworkSubmissionDrafts' => $homeworkSubmissionDrafts, 'homeworkSubmissionType' => $homeworkSubmissionType, 'homeworkSubmissionRequired' => $homeworkSubmissionRequired, 'homeworkCrowdAssess' => $homeworkCrowdAssess, 'homeworkCrowdAssessOtherTeachersRead' => $homeworkCrowdAssessOtherTeachersRead, 'homeworkCrowdAssessClassmatesRead' => $homeworkCrowdAssessClassmatesRead, 'homeworkCrowdAssessOtherStudentsRead' => $homeworkCrowdAssessOtherStudentsRead, 'homeworkCrowdAssessSubmitterParentsRead' => $homeworkCrowdAssessSubmitterParentsRead, 'homeworkCrowdAssessClassmatesParentsRead' => $homeworkCrowdAssessClassmatesParentsRead, 'homeworkCrowdAssessOtherParentsRead' => $homeworkCrowdAssessOtherParentsRead, 'viewableParents' => $viewableParents, 'viewableStudents' => $viewableStudents, 'gibbonPersonIDCreator' => $gibbonPersonIDCreator, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit);
                    $sql = 'INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, name=:name, summary=:summary, description=:description, teachersNotes=:teachersNotes, homework=:homework, homeworkDueDateTime=:homeworkDueDate, homeworkDetails=:homeworkDetails, homeworkSubmission=:homeworkSubmission, homeworkSubmissionDateOpen=:homeworkSubmissionDateOpen, homeworkSubmissionDrafts=:homeworkSubmissionDrafts, homeworkSubmissionType=:homeworkSubmissionType, homeworkSubmissionRequired=:homeworkSubmissionRequired, homeworkCrowdAssess=:homeworkCrowdAssess, homeworkCrowdAssessOtherTeachersRead=:homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead=:homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead=:homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead=:homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead=:homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead=:homeworkCrowdAssessOtherParentsRead, viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= "&return=error2$params";
                    header("Location: {$URL}");
                    exit();
                }

                //Unlock module table
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }

                if ($partialFail == true) {
                    $URL .= "&return=warning1$params";
                    header("Location: {$URL}");
                } else {
                    //Jump to Markbook?
                    $markbook = $_POST['markbook'];
                    if ($markbook == 'Y') {
                        $URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_edit_add.php&gibbonPlannerEntryID=$AI&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitID=".$_POST['gibbonUnitID']."&date=$date&viewableParents=$viewableParents&viewableStudents=$viewableStudents&name=$name&summary=$summary&return=1";
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
