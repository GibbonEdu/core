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
                $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonCourseClassID=:gibbonCourseClassID';
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
                        $attainmentConcern = null;
                    } elseif ($gibbonScaleIDAttainment == '') {
                        $attainmentValue = '';
                        $attainmentDescriptor = '';
                        $attainmentConcern = '';
                    } else {
                        $attainmentValue = $_POST["$i-attainmentValue"];
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
                    $wordpressCommentPushID = null;
                    $wordpressCommentPushAction = null;
                    if (isset($_POST["$i-wordpressCommentPush"])) {
                        $wordpressCommentPushID = substr($_POST["$i-wordpressCommentPush"], 0, strpos($_POST["$i-wordpressCommentPush"], '-'));
                        $wordpressCommentPushAction = substr($_POST["$i-wordpressCommentPush"], (strpos($_POST["$i-wordpressCommentPush"], '-') + 1));
                    }

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
                        if ($personalisedWarnings == 'Y' and $resultTarget->rowCount() == 1 and $attainmentValue != '') {
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
                                    $attainmentDescriptor = sprintf(__($guid, 'Below personalised target of %1$s'), $rowTarget['value']);
                                }
                                //Above target
                                elseif ($attainmentSequence <= $target) {
                                    $attainmentConcern = 'P';
                                    $attainmentDescriptor = sprintf(__($guid, 'Equal to or above personalised target of %1$s'), $rowTarget['value']);
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

                    $time = time();
                    //Move attached file, if there is one
                    if ($uploadedResponse == 'Y') {
                        if (@$_FILES["response$i"]['tmp_name'] != '') {
                            //Check for folder in uploads based on today's date
                            $path = $_SESSION[$guid]['absolutePath'];
                            if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                                mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                            }
                            $unique = false;
                            $count2 = 0;
                            while ($unique == false and $count2 < 100) {
                                $suffix = randomPassword(16);
                                $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_Uploaded Response_$suffix".strrchr($_FILES["response$i"]['name'], '.');
                                if (!(file_exists($path.'/'.$attachment))) {
                                    $unique = true;
                                }
                                ++$count2;
                            }

                            if (!(move_uploaded_file($_FILES["response$i"]['tmp_name'], $path.'/'.$attachment))) {
                                $partialFail = true;
                            }
                        } else {
                            $attachment = null;
                            if (isset($_POST["response$i"])) {
                                $attachment = $_POST["response$i"];
                            }
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
                                $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'attainmentValue' => $attainmentValue, 'attainmentDescriptor' => $attainmentDescriptor, 'attainmentConcern' => $attainmentConcern, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'effortConcern' => $effortConcern, 'comment' => $commentValue, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'attachment' => $attachment);
                                $sql = 'INSERT INTO gibbonMarkbookEntry SET gibbonMarkbookColumnID=:gibbonMarkbookColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, attainmentValue=:attainmentValue, attainmentDescriptor=:attainmentDescriptor, attainmentConcern=:attainmentConcern, effortValue=:effortValue, effortDescriptor=:effortDescriptor, effortConcern=:effortConcern, comment=:comment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, response=:attachment';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        } else {
                            $row = $result->fetch();
                            //Update
                            try {
                                $data = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'attainmentValue' => $attainmentValue, 'attainmentDescriptor' => $attainmentDescriptor, 'attainmentConcern' => $attainmentConcern, 'effortValue' => $effortValue, 'effortDescriptor' => $effortDescriptor, 'effortConcern' => $effortConcern, 'comment' => $commentValue, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'attachment' => $attachment, 'gibbonMarkbookEntryID' => $row['gibbonMarkbookEntryID']);
                                $sql = 'UPDATE gibbonMarkbookEntry SET gibbonMarkbookColumnID=:gibbonMarkbookColumnID, gibbonPersonIDStudent=:gibbonPersonIDStudent, attainmentValue=:attainmentValue, attainmentDescriptor=:attainmentDescriptor, attainmentConcern=:attainmentConcern, effortValue=:effortValue, effortDescriptor=:effortDescriptor, effortConcern=:effortConcern, comment=:comment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, response=:attachment WHERE gibbonMarkbookEntryID=:gibbonMarkbookEntryID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //Attempt WordPress Comment Push
                    if ($wordpressCommentPushAction != '' and $wordpressCommentPushID != '') {
                        $data = 'comment_post_ID='.urlencode($wordpressCommentPushID).'&author='.urlencode(formatName($_SESSION[$guid]['title'], $_SESSION[$guid]['preferredName'], $_SESSION[$guid]['surname'], 'Staff')).'&email='.urlencode($_SESSION[$guid]['email']).'&url='.urlencode($_SESSION[$guid]['website']).'&comment='.urlencode($commentValue);
                        $params = array('http' => array('method' => 'POST', 'content' => $data));
                        $ctx = stream_context_create($params);
                        $fp = @fopen($wordpressCommentPushAction, 'rb', false, $ctx);
                        if (!$fp) {
                            $partialFail = true;
                        }
                        if (@stream_get_contents($fp) === false) {
                            $partialFail = true;
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
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
