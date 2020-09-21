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

include '../../gibbon.php';

$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
$enableModifiedAssessment = getSettingByScope($connection2, 'Markbook', 'enableModifiedAssessment');

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/markbook_edit_data.php&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonCourseClassID=$gibbonCourseClassID";

$personalisedWarnings = getSettingByScope($connection2, 'Markbook', 'personalisedWarnings');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_data.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        if ($gibbonMarkbookColumnID == '' or $gibbonCourseClassID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = 'SELECT gibbonMarkbookColumn.*, gibbonScaleIDTarget FROM gibbonMarkbookColumn JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonMarkbookColumn.gibbonCourseClassID=:gibbonCourseClassID';
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
                $name = $row['name' ];
                $count = $_POST['count'];
                $partialFail = false;
                $attachmentFail = false;
                $attainment = $row['attainment'];
                $gibbonScaleIDAttainment = $row['gibbonScaleIDAttainment'];
                if ($enableEffort != 'Y') {
                    $effort = 'N';
                    $gibbonScaleIDEffort = null;
                }
                else {
                    $effort = $row['effort'];
                    $gibbonScaleIDEffort = $row['gibbonScaleIDEffort'];
                }
                $comment = $row['comment'];
                $uploadedResponse = $row['uploadedResponse'];
                $gibbonScaleIDAttainment = $row['gibbonScaleIDAttainment'];
                $gibbonScaleIDTarget = $row['gibbonScaleIDTarget'];

                for ($i = 1;$i <= $count;++$i) {
                    $gibbonPersonIDStudent = $_POST["$i-gibbonPersonID"];
                    //Modified Assessment
                    if ($enableModifiedAssessment != 'Y') {
                        $modifiedAssessment = NULL;
                    }
                    else {
                        if (isset($_POST["$i-modifiedAssessmentEligible"])) { //Checkbox exists
                            if (isset($_POST["$i-modifiedAssessment"])) {
                                $modifiedAssessment = 'Y';
                            }
                            else {
                                $modifiedAssessment = 'N';
                            }
                        }
                        else { //Checkbox does not exist
                            $modifiedAssessment = NULL;
                        }                        
                    }
                    //Attainment
                    if ($attainment == 'N') {
                        $attainmentValue = null;
                        $attainmentValueRaw = null;
                        $attainmentDescriptor = null;
                        $attainmentConcern = null;
                    } elseif ($gibbonScaleIDAttainment == '') {
                        $attainmentValue = '';
                        $attainmentValueRaw = '';
                        $attainmentDescriptor = '';
                        $attainmentConcern = '';
                    } else {
                        $attainmentValue = (isset($_POST["$i-attainmentValue"]))? $_POST["$i-attainmentValue"] : null;
                        $attainmentValueRaw = (isset($_POST["$i-attainmentValueRaw"]))? $_POST["$i-attainmentValueRaw"] : null;
                    }
                    //Effort
                    if ($effort == 'N') {
                        $effortValue = null;
                        $effortDescriptor = null;
                        $effortConcern = null;
                    } elseif ($gibbonScaleIDEffort == '') {
                        $effortValue = '';
                        $effortDescriptor = '';
                        $effortConcern = '';
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

                    //SET AND CALCULATE FOR ATTAINMENT
                    if ($attainment == 'Y' and $gibbonScaleIDAttainment != '') {
                        //Check for target grade
                        try {
                            $dataTarget = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                            $sqlTarget = 'SELECT * FROM gibbonMarkbookTarget JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        //With personal warnings
                        if ($personalisedWarnings == 'Y' && $resultTarget->rowCount() == 1 && $attainmentValue != '' && $gibbonScaleIDAttainment == $gibbonScaleIDTarget) {
                            $attainmentConcern = 'N';
                            $attainmentDescriptor = '';
                            $rowTarget = $resultTarget->fetch();

                            //Get details of attainment grade (sequenceNumber)
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
                                $target = $rowTarget['sequenceNumber'];
                                $attainmentSequence = $rowScale['sequenceNumber'];

                                //Test against target grade and set values accordingly
                                //Below target
                                if ($attainmentSequence > $target) {
                                    $attainmentConcern = 'Y';
                                    $attainmentDescriptor = sprintf(__('Below personalised target of %1$s'), $rowTarget['value']);
                                }
                                //Above target
                                elseif ($attainmentSequence <= $target) {
                                    $attainmentConcern = 'P';
                                    $attainmentDescriptor = sprintf(__('Equal to or above personalised target of %1$s'), $rowTarget['value']);
                                }
                            }
                        }
                        //Without personal warnings
                        else {
                            $attainmentConcern = 'N';
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

                                if ($lowestAcceptableAttainment != '' and $sequence != '' and $attainmentValue != '') {
                                    if ($sequence > $lowestAcceptableAttainment) {
                                        $attainmentConcern = 'Y';
                                    }
                                }
                            }
                        }
                    }

                    //SET AND CALCULATE FOR EFFORT
                    if ($effort == 'Y' and $gibbonScaleIDEffort != '') {
                        $effortConcern = 'N';
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

                            if ($lowestAcceptableEffort != '' and $sequence != '' and $effortValue != '') {
                                if ($sequence > $lowestAcceptableEffort) {
                                    $effortConcern = 'Y';
                                }
                            }
                        }
                    }

                    //Move attached file, if there is one
                    if ($uploadedResponse == 'Y') {
                        //Move attached image  file, if there is one
                        if (!empty($_FILES['response'.$i]['tmp_name'])) {
                            $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                            $file = (isset($_FILES['response'.$i]))? $_FILES['response'.$i] : null;

                            // Upload the file, return the /uploads relative path
                            $attachment = $fileUploader->uploadFromPost($file, $name."_Uploaded Response");

                            if (empty($attachment)) {
                                $partialFail = true;
                            }

                            // Create a log of failed uploads
                            $errorMessage = $fileUploader->getLastError();
                            if (!empty($errorMessage) || filesize($attachment) == 0) {
                                $gibbonModuleID = getModuleIDFromName($connection2, 'Markbook');
                                setLog($connection2, $gibbon->session->get('gibbonSchoolYearID'), $gibbonModuleID, $gibbon->session->get('gibbonPersonID'), 'Uploaded Response Failed', [
                                    'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID,
                                    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
                                    'name' => $row['name'],
                                    'attachment' => $attachment,
                                    'errorMessage' => $errorMessage,
                                    'fileType' => $file['type'] ?? '',
                                    'fileError' => $file['error'] ?? '',
                                ]);

                                $attachmentFail = true;
                            }
                        } else {
                            $attachment = (isset($_POST["attachment$i"]))? $_POST["attachment$i"] : '';
                        }
                    } else {
                        $attachment = null;
                    }

                    $selectFail = false;
                    try {
                        $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
                        $sql = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                        $selectFail = true;
                    }
                    if (!($selectFail)) {
                        if ($result->rowCount() < 1) {
                            try {
                                $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'modifiedAssessment' => $modifiedAssessment, 'attainmentValue' => $attainmentValue, 'attainmentValueRaw' => $attainmentValueRaw, 'attainmentDescriptor' => $attainmentDescriptor, 'attainmentConcern' => $attainmentConcern, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'effortConcern' => $effortConcern, 'comment' => $commentValue, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'attachment' => $attachment);
                                $sql = 'INSERT INTO gibbonMarkbookEntry SET gibbonMarkbookColumnID=:gibbonMarkbookColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, modifiedAssessment=:modifiedAssessment, attainmentValue=:attainmentValue, attainmentValueRaw=:attainmentValueRaw, attainmentDescriptor=:attainmentDescriptor, attainmentConcern=:attainmentConcern, effortValue=:effortValue, effortDescriptor=:effortDescriptor, effortConcern=:effortConcern, comment=:comment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, response=:attachment';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        } else {
                            $row = $result->fetch();
                            //Update
                            try {
                                $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'modifiedAssessment' => $modifiedAssessment, 'attainmentValue' => $attainmentValue, 'attainmentValueRaw' => $attainmentValueRaw, 'attainmentDescriptor' => $attainmentDescriptor, 'attainmentConcern' => $attainmentConcern, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'effortConcern' => $effortConcern, 'comment' => $commentValue, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'attachment' => $attachment, 'gibbonMarkbookEntryID' => $row['gibbonMarkbookEntryID']);
                                $sql = 'UPDATE gibbonMarkbookEntry SET gibbonMarkbookColumnID=:gibbonMarkbookColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, modifiedAssessment=:modifiedAssessment, attainmentValue=:attainmentValue, attainmentValueRaw=:attainmentValueRaw, attainmentDescriptor=:attainmentDescriptor, attainmentConcern=:attainmentConcern, effortValue=:effortValue, effortDescriptor=:effortDescriptor, effortConcern=:effortConcern, comment=:comment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, response=:attachment WHERE gibbonMarkbookEntryID=:gibbonMarkbookEntryID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Update column
                $completeDate = $_POST['completeDate'];
                if ($completeDate == '') {
                    $completeDate = null;
                    $complete = 'N';
                } else {
                    $completeDate = dateConvert($guid, $completeDate);
                    $complete = 'Y';
                }
                try {
                    $data = array('completeDate' => $completeDate, 'complete' => $complete, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql = 'UPDATE gibbonMarkbookColumn SET completeDate=:completeDate, complete=:complete WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                //Return!
                if ($partialFail == true) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } elseif ($attachmentFail == true) {
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
