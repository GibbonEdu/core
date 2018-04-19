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

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonInternalAssessmentColumnID = $_GET['gibbonInternalAssessmentColumnID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/internalAssessment_write_data.php&gibbonInternalAssessmentColumnID=$gibbonInternalAssessmentColumnID&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write_data.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonInternalAssessmentColumnID == '' or $gibbonCourseClassID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID AND gibbonCourseClassID=:gibbonCourseClassID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $row = $result->fetch();
                $attachmentCurrent = isset($_POST['attachment'])? $_POST['attachment'] : '';
                $name = $row['name'];
                $count = $_POST['count'];
                $partialFail = false;
                $attainment = $row['attainment'];
                $gibbonScaleIDAttainment = $row['gibbonScaleIDAttainment'];
                $effort = $row['effort'];
                $gibbonScaleIDEffort = $row['gibbonScaleIDEffort'];
                $comment = $row['comment'];
                $uploadedResponse = $row['uploadedResponse'];

                for ($i = 1;$i <= $count;++$i) {
                    $gibbonPersonIDStudent = $_POST["$i-gibbonPersonID"];
                    //Attainment
                    if ($attainment == 'N') {
                        $attainmentValue = null;
                        $attainmentDescriptor = null;
                    } elseif ($gibbonScaleIDAttainment == '') {
                        $attainmentValue = '';
                        $attainmentDescriptor = '';
                    } else {
                        $attainmentValue = $_POST["$i-attainmentValue"];
                    }
                    //Effort
                    if ($effort == 'N') {
                        $effortValue = null;
                        $effortDescriptor = null;
                    } elseif ($gibbonScaleIDEffort == '') {
                        $effortValue = '';
                        $effortDescriptor = '';
                    } else {
                        $effortValue = $_POST["$i-effortValue"];
                    }
                    //Comment
                    if ($comment != 'Y') {
                        $commentValue = null;
                    } else {
                        $commentValue = $_POST["comment$i"];
                    }
                    $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];
                    $wordpressCommentPushID = null;
                    $wordpressCommentPushAction = null;
                    if (isset($_POST["$i-wordpressCommentPush"])) {
                        $wordpressCommentPushID = substr($_POST["$i-wordpressCommentPush"], 0, strpos($_POST["$i-wordpressCommentPush"], '-'));
                        $wordpressCommentPushAction = substr($_POST["$i-wordpressCommentPush"], (strpos($_POST["$i-wordpressCommentPush"], '-') + 1));
                    }

                    //SET AND CALCULATE FOR ATTAINMENT
                    if ($attainment == 'Y' and $gibbonScaleIDAttainment != '') {
                        //Without personal warnings
                        $attainmentDescriptor = '';
                        if ($attainmentValue != '') {
                            $lowestAcceptableAttainment = $_POST['lowestAcceptableAttainment'];
                            $scaleAttainment = $_POST['scaleAttainment'];
                            try {
                                $dataScale = array('attainmentValue' => $attainmentValue, 'scaleAttainment' => $scaleAttainment);
                                $sqlScale = 'SELECT * FROM gibbonScaleGrade JOIN gibbonScale ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE value=:attainmentValue AND gibbonScaleGrade.gibbonScaleID=:scaleAttainment';
                                $resultScale = $connection2->prepare($sqlScale);
                                $resultScale->execute($dataScale);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            if ($resultScale->rowCount() != 1) {
                                $partialFail = true;
                            } else {
                                $rowScale = $resultScale->fetch();
                                $sequence = $rowScale['sequenceNumber'];
                                $attainmentDescriptor = $rowScale['descriptor'];
                            }
                        }
                    }

                    //SET AND CALCULATE FOR EFFORT
                    if ($effort == 'Y' and $gibbonScaleIDEffort != '') {
                        $effortDescriptor = '';
                        if ($effortValue != '') {
                            $lowestAcceptableEffort = $_POST['lowestAcceptableEffort'];
                            $scaleEffort = $_POST['scaleEffort'];
                            try {
                                $dataScale = array('effortValue' => $effortValue, 'scaleEffort' => $scaleEffort);
                                $sqlScale = 'SELECT * FROM gibbonScaleGrade JOIN gibbonScale ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE value=:effortValue AND gibbonScaleGrade.gibbonScaleID=:scaleEffort';
                                $resultScale = $connection2->prepare($sqlScale);
                                $resultScale->execute($dataScale);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            if ($resultScale->rowCount() != 1) {
                                $partialFail = true;
                            } else {
                                $rowScale = $resultScale->fetch();
                                $sequence = $rowScale['sequenceNumber'];
                                $effortDescriptor = $rowScale['descriptor'];
                            }
                        }
                    }

                    $time = time();

                    $attachment = isset($_POST["attachment$i"])? $_POST["attachment$i"] : '';

                    //Move attached file, if there is one
                    if ($uploadedResponse == 'Y') {
                        if (!empty($_FILES["response$i"]['tmp_name'])) {
                            $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                
                            $file = (isset($_FILES["response$i"]))? $_FILES["response$i"] : null;

                            // Upload the file, return the /uploads relative path
                            $attachment = $fileUploader->uploadFromPost($file, $name.'_Uploaded Response');

                            if (empty($attachment)) {
                                $partialFail = true;
                            }
                        }
                    }

                    $selectFail = false;
                    try {
                        $data = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                        $sql = 'SELECT * FROM gibbonInternalAssessmentEntry WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                        $selectFail = true;
                    }
                    if (!($selectFail)) {
                        if ($result->rowCount() < 1) {
                            try {
                                $data = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'attainmentValue' => $attainmentValue, 'attainmentDescriptor' => $attainmentDescriptor, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'comment' => $commentValue, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit);
                                $sql = 'INSERT INTO gibbonInternalAssessmentEntry SET gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, attainmentValue=:attainmentValue, attainmentDescriptor=:attainmentDescriptor, effortValue=:effortValue, effortDescriptor=:effortDescriptor, comment=:comment, response=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        } else {
                            $row = $result->fetch();
                            //Update
                            try {
                                $data = array('gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'attainmentValue' => $attainmentValue, 'attainmentDescriptor' => $attainmentDescriptor, 'comment' => $commentValue, 'attachment' => $attachment, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'gibbonInternalAssessmentEntryID' => $row['gibbonInternalAssessmentEntryID']);
                                $sql = 'UPDATE gibbonInternalAssessmentEntry SET gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, attainmentValue=:attainmentValue, attainmentDescriptor=:attainmentDescriptor, effortValue=:effortValue, effortDescriptor=:effortDescriptor, comment=:comment, response=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE gibbonInternalAssessmentEntryID=:gibbonInternalAssessmentEntryID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Update column
                $description = $_POST['description'];
                $time = time();
                //Move attached file, if there is one
                if (!empty($_FILES['file']['tmp_name'])) {
                    $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
                
                    $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                    // Upload the file, return the /uploads relative path
                    $attachment = $fileUploader->uploadFromPost($file, $name);

                    if (empty($attachment)) {
                        $partialFail = true;
                    }
                } else {
                    $attachment = $attachmentCurrent;
                }
                $completeDate = $_POST['completeDate'];
                if ($completeDate == '') {
                    $completeDate = null;
                    $complete = 'N';
                } else {
                    $completeDate = dateConvert($guid, $completeDate);
                    $complete = 'Y';
                }
                try {
                    $data = array('attachment' => $attachment, 'description' => $description, 'completeDate' => $completeDate, 'complete' => $complete, 'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID);
                    $sql = 'UPDATE gibbonInternalAssessmentColumn SET attachment=:attachment, description=:description, completeDate=:completeDate, complete=:complete WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                //Return!
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
