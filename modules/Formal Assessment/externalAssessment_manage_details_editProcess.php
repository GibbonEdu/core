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

$gibbonPersonID = $_POST['gibbonPersonID'];
$gibbonExternalAssessmentStudentID = $_POST['gibbonExternalAssessmentStudentID'];
$search = $_GET['search'];
$allStudents = '';
if (isset($_GET['allStudents'])) {
    $allStudents = $_GET['allStudents'];
}

if ($gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/externalAssessment_manage_details_edit.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=$gibbonExternalAssessmentStudentID&search=$search&allStudents=$allStudents";

    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if tt specified
        if ($gibbonExternalAssessmentStudentID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
                $sql = 'SELECT * FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
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

                //Validate Inputs
                $count = 0;
                if (is_numeric($_POST['count'])) {
                    $count = $_POST['count'];
                }
                $date = dateConvert($guid, $_POST['date']);

                $time = time();
                //Move attached file, if there is one
                if (isset($_FILES['file'])) {
                    if ($_FILES['file']['tmp_name'] != '') {
                        //Check for folder in uploads based on today's date
                        $path = $_SESSION[$guid]['absolutePath'];
                        if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                            mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                        }
                        $unique = false;
                        $count = 0;
                        while ($unique == false and $count < 100) {
                            $suffix = randomPassword(16);
                            $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time)."/externalAssessmentUpload_$suffix".strrchr($_FILES['file']['name'], '.');
                            if (!(file_exists($path.'/'.$attachment))) {
                                $unique = true;
                            }
                            ++$count;
                        }

                        if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$attachment))) {
                            $URL .= '&return=error1';
                            header("Location: {$URL}");
                        }
                    } else {
                        $attachment = $row['attachment'];
                    }
                } else {
                    $attachment = $row['attachment'];
                }

                if ($date == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Scan through fields
                    $partialFail = false;
                    for ($i = 0; $i < $count; ++$i) {
                        $gibbonExternalAssessmentStudentEntryID = @$_POST[$i.'-gibbonExternalAssessmentStudentEntryID'];
                        if (isset($_POST[$i.'-gibbonScaleGradeID']) == false) {
                            $gibbonScaleGradeID = null;
                        } else {
                            if ($_POST[$i.'-gibbonScaleGradeID'] == '') {
                                $gibbonScaleGradeID = null;
                            } else {
                                $gibbonScaleGradeID = $_POST[$i.'-gibbonScaleGradeID'];
                            }
                        }
                        if (isset($_POST[$i.'-gibbonScaleGradeIDPAS']) == false) {
                            $gibbonScaleGradeIDPAS = null;
                        } else {
                            if ($_POST[$i.'-gibbonScaleGradeIDPAS'] == '') {
                                $gibbonScaleGradeIDPAS = null;
                            } else {
                                $gibbonScaleGradeIDPAS = $_POST[$i.'-gibbonScaleGradeIDPAS'];
                            }
                        }

                        if ($gibbonExternalAssessmentStudentEntryID != '') {
                            try {
                                $data = array('gibbonScaleGradeID' => $gibbonScaleGradeID, 'gibbonScaleGradeIDPAS' => $gibbonScaleGradeIDPAS, 'gibbonExternalAssessmentStudentEntryID' => $gibbonExternalAssessmentStudentEntryID);
                                $sql = 'UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=:gibbonScaleGradeID, gibbonScaleGradeIDPrimaryAssessmentScale=:gibbonScaleGradeIDPAS WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //Write to database
                    try {
                        $data = array('date' => $date, 'attachment' => $attachment, 'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
                        $sql = 'UPDATE gibbonExternalAssessmentStudent SET date=:date, attachment=:attachment WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
