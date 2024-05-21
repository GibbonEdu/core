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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
$viewBy = $_POST['viewBy'] ?? '';
$subView = $_POST['subView'] ?? '';
if ($viewBy != 'date' and $viewBy != 'class') {
    $viewBy = 'date';
}
$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$gibbonPlannerEntryID_org = $_POST['gibbonPlannerEntryID_org'] ?? '';
$date = !empty($_POST['date']) ? Format::dateConvert($_POST['date']) : null;
$duplicateReturnYear = 'current';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_duplicate.php&gibbonPlannerEntryID=$gibbonPlannerEntryID_org";

//Params to pass back (viewBy + date or classID)
if ($viewBy == 'date') {
    $params = "&viewBy=$viewBy&date=$date";
} else {
    $params = "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView";
}

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_duplicate.php') == false) {
    $URL .= "&return=error0$params";
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if legitimate year/class selected
        if ($gibbonPlannerEntryID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassID == '' or ($viewBy == 'class' and $gibbonCourseClassID == 'Y')) {
            $URL .= "&return=error1$params";
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPlannerEntryID' => $gibbonPlannerEntryID_org);
                $sql = 'SELECT *, gibbonPlannerEntry.description AS description FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID';
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
                $row = $result->fetch();

                //Validate Inputs
                $name = $_POST['name'] ?? '';
                $timeStart = $_POST['timeStart'] ?? '';
                $timeEnd = $_POST['timeEnd'] ?? '';
                $summary = $row['summary'];
                $description = $row['description'];
                //Add to smart blocks to description if copying to another year
                if ($gibbonSchoolYearID != $session->get('gibbonSchoolYearID') or @$_POST['keepUnit'] != 'Y') {
                    try {
                        $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlBlocks = 'SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryID IS NOT NULL';
                        $resultBlocks = $connection2->prepare($sqlBlocks);
                        $resultBlocks->execute($dataBlocks);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                    while ($rowBlocks = $resultBlocks->fetch()) {
                        $description .= '<h2>'.$rowBlocks['title'].'</h2>';
                        $description .= $rowBlocks['contents'];
                    }


                        $dataPlannerUpdate = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'description' => $description);
                        $sqlPlannerUpdate = 'UPDATE gibbonPlannerEntry SET description=:description WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                        $resultPlannerUpdate = $connection2->prepare($sqlPlannerUpdate);
                        $resultPlannerUpdate->execute($dataPlannerUpdate);
                }

                $gibbonUnitClassID = null;
                $keepUnit = $_POST['keepUnit'] ?? null;

                if ($keepUnit == 'Y') {
                    $gibbonUnitClassID = $_POST['gibbonUnitClassID'] ?? null;
                    $gibbonUnitID = !empty($row['gibbonUnitID']) ? $row['gibbonUnitID'] : null;
                } else {
                    $gibbonUnitID = null;
                }
                $teachersNotes = $row['teachersNotes'];
                $homework = $row['homework'];
                $homeworkDetails = $row['homeworkDetails'];
                $homeworkDueDateTime = $row['homeworkDueDateTime'];
                if (!empty($_POST['homeworkDueDate']) && !empty($_POST['homeworkDueDateTime'])) {
                    $homeworkDueDateTime = Format::dateConvert($_POST['homeworkDueDate']).' '.$_POST['homeworkDueDateTime'];
                }
                $homeworkTimeCap = $row['homeworkTimeCap'];
                $homeworkSubmission = $row['homeworkSubmission'];
                $homeworkSubmissionDateOpen = $row['homeworkSubmissionDateOpen'];
                if (!empty($_POST['homeworkSubmissionDateOpen'])) {
                    $homeworkSubmissionDateOpen = Format::dateConvert($_POST['homeworkSubmissionDateOpen']);
                }
                $homeworkSubmissionDrafts = $row['homeworkSubmissionDrafts'];
                $homeworkSubmissionType = $row['homeworkSubmissionType'];
                $homeworkSubmissionRequired = $row['homeworkSubmissionRequired'];
                $homeworkCrowdAssess = $row['homeworkCrowdAssess'];
                $homeworkCrowdAssessOtherTeachersRead = $row['homeworkCrowdAssessOtherTeachersRead'];
                $homeworkCrowdAssessClassmatesRead = $row['homeworkCrowdAssessClassmatesRead'];
                $homeworkCrowdAssessOtherStudentsRead = $row['homeworkCrowdAssessOtherStudentsRead'];
                $homeworkCrowdAssessSubmitterParentsRead = $row['homeworkCrowdAssessSubmitterParentsRead'];
                $homeworkCrowdAssessClassmatesParentsRead = $row['homeworkCrowdAssessClassmatesParentsRead'];
                $homeworkCrowdAssessOtherParentsRead = $row['homeworkCrowdAssessOtherParentsRead'];
                $viewableParents = $row['viewableParents'];
                $viewableStudents = $row['viewableStudents'];
                $gibbonPersonIDCreator = $session->get('gibbonPersonID');
                $gibbonPersonIDLastEdit = $session->get('gibbonPersonID');

                if ($viewBy == '' or $gibbonCourseClassID == '' or $date == '' or $timeStart == '' or $timeEnd == '' or $name == '' or $homework == '' or $viewableParents == '' or $viewableStudents == '' or ($homework == 'Y' and ($homeworkDetails == '' or $homeworkDueDateTime == ''))) {
                    $URL .= "&return=error3$params";
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd, 'gibbonUnitID' => $gibbonUnitID, 'name' => $name, 'summary' => $summary, 'description' => $description, 'teachersNotes' => $teachersNotes, 'homework' => $homework, 'homeworkDueDateTime' => $homeworkDueDateTime, 'homeworkDetails' => $homeworkDetails, 'homeworkSubmission' => $homeworkSubmission, 'homeworkTimeCap' => $homeworkTimeCap, 'homeworkSubmissionDateOpen' => $homeworkSubmissionDateOpen, 'homeworkSubmissionDrafts' => $homeworkSubmissionDrafts, 'homeworkSubmissionType' => $homeworkSubmissionType, 'homeworkSubmissionRequired' => $homeworkSubmissionRequired, 'homeworkCrowdAssess' => $homeworkCrowdAssess, 'homeworkCrowdAssessOtherTeachersRead' => $homeworkCrowdAssessOtherTeachersRead, 'homeworkCrowdAssessClassmatesRead' => $homeworkCrowdAssessClassmatesRead, 'homeworkCrowdAssessOtherStudentsRead' => $homeworkCrowdAssessOtherStudentsRead, 'homeworkCrowdAssessSubmitterParentsRead' => $homeworkCrowdAssessSubmitterParentsRead, 'homeworkCrowdAssessClassmatesParentsRead' => $homeworkCrowdAssessClassmatesParentsRead, 'homeworkCrowdAssessOtherParentsRead' => $homeworkCrowdAssessOtherParentsRead, 'viewableParents' => $viewableParents, 'viewableStudents' => $viewableStudents, 'gibbonPersonIDCreator' => $gibbonPersonIDCreator, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit);
                        $sql = 'INSERT INTO gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, name=:name, summary=:summary, description=:description, teachersNotes=:teachersNotes, homework=:homework, homeworkDueDateTime=:homeworkDueDateTime, homeworkDetails=:homeworkDetails, homeworkSubmission=:homeworkSubmission, homeworkTimeCap=:homeworkTimeCap, homeworkSubmissionDateOpen=:homeworkSubmissionDateOpen, homeworkSubmissionDrafts=:homeworkSubmissionDrafts, homeworkSubmissionType=:homeworkSubmissionType, homeworkSubmissionRequired=:homeworkSubmissionRequired, homeworkCrowdAssess=:homeworkCrowdAssess, homeworkCrowdAssessOtherTeachersRead=:homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead=:homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead=:homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead=:homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead=:homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead=:homeworkCrowdAssessOtherParentsRead, viewableParents=:viewableParents, viewableStudents=:viewableStudents, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= "&return=error2$params";
                        header("Location: {$URL}");
                        exit();
                    }

                   $AI = $connection2->lastInsertID();

                    $partialFail = false;

                    //Try to duplicate MB columns
                    $duplicate = $_POST['duplicate'] ?? '';
                    if ($duplicate == 'Y') {
                        try {
                            $dataMarkbook = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                            $sqlMarkbook = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                            $resultMarkbook = $connection2->prepare($sqlMarkbook);
                            $resultMarkbook->execute($dataMarkbook);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowMarkbook = $resultMarkbook->fetch()) {
                            try {
                                $dataMarkbookInsert = array('gibbonUnitID' => $gibbonUnitID, 'gibbonPlannerEntryID' => $AI, 'gibbonCourseClassID' => $gibbonCourseClassID, 'name' => $rowMarkbook['name'], 'description' => $rowMarkbook['description'], 'type' => $rowMarkbook['type'], 'attainment' => $rowMarkbook['attainment'], 'gibbonScaleIDAttainment' => $rowMarkbook['gibbonScaleIDAttainment'], 'effort' => $rowMarkbook['effort'], 'gibbonScaleIDEffort' => $rowMarkbook['gibbonScaleIDEffort'], 'comment' => $rowMarkbook['comment'], 'viewableStudents' => $rowMarkbook['viewableStudents'], 'viewableParents' => $rowMarkbook['viewableParents'], 'attachment' => $rowMarkbook['attachment'], 'gibbonPersonID1' => $session->get('gibbonPersonID'), 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                                $sqlMarkbookInsert = "INSERT INTO gibbonMarkbookColumn SET gibbonUnitID=:gibbonUnitID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, comment=:comment, completeDate=NULL, complete='N' ,viewableStudents=:viewableStudents, viewableParents=:viewableParents ,attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonID1, gibbonPersonIDLastEdit=:gibbonPersonID2";
                                $resultMarkbookInsert = $connection2->prepare($sqlMarkbookInsert);
                                $resultMarkbookInsert->execute($dataMarkbookInsert);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //DUPLICATE SMART BLOCKS
                    if ($gibbonUnitClassID != null) {
                        try {
                            $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                            $sqlBlocks = 'SELECT * FROM gibbonUnitClassBlock WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                            $resultBlocks = $connection2->prepare($sqlBlocks);
                            $resultBlocks->execute($dataBlocks);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            try {
                                $dataBlocksInsert = array('gibbonUnitClassID' => $gibbonUnitClassID, 'gibbonPlannerEntryID' => $AI, 'gibbonUnitBlockID' => $rowBlocks['gibbonUnitBlockID'], 'title' => $rowBlocks['title'], 'type' => $rowBlocks['type'], 'length' => $rowBlocks['length'], 'contents' => $rowBlocks['contents'], 'teachersNotes' => $rowBlocks['teachersNotes'], 'sequenceNumber' => $rowBlocks['sequenceNumber']);
                                $sqlBlocksInsert = "INSERT INTO gibbonUnitClassBlock SET gibbonUnitClassID=:gibbonUnitClassID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonUnitBlockID=:gibbonUnitBlockID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, complete='N'";
                                $resultBlocksInsert = $connection2->prepare($sqlBlocksInsert);
                                $resultBlocksInsert->execute($dataBlocksInsert);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //DUPLICATE OUTCOMES
                    try {
                        $dataBlocks = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlBlocks = 'SELECT * FROM gibbonPlannerEntryOutcome WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                        $resultBlocks = $connection2->prepare($sqlBlocks);
                        $resultBlocks->execute($dataBlocks);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                    while ($rowBlocks = $resultBlocks->fetch()) {
                        try {
                            $dataBlocksInsert = array('gibbonPlannerEntryID' => $AI, 'gibbonOutcomeID' => $rowBlocks['gibbonOutcomeID'], 'sequenceNumber' => $rowBlocks['sequenceNumber'], 'content' => $rowBlocks['content']);
                            $sqlBlocksInsert = 'INSERT INTO gibbonPlannerEntryOutcome SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonOutcomeID=:gibbonOutcomeID, sequenceNumber=:sequenceNumber, content=:content';
                            $resultBlocksInsert = $connection2->prepare($sqlBlocksInsert);
                            $resultBlocksInsert->execute($dataBlocksInsert);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    if ($partialFail == true) {
                        $URL .= "&return=warning1$params";
                        header("Location: {$URL}");
                    } else {
                        if ($gibbonSchoolYearID == $session->get('gibbonSchoolYearID')) {
                            $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_edit.php&gibbonPlannerEntryID=$AI";
                            $URL .= "&return=success1$params";
                        } else {
                            $URL .= "&return=success0$params";
                        }
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
