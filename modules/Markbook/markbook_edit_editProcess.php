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

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/markbook_edit_edit.php&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
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
                    //Validate Inputs
                    $gibbonUnitID = $_POST['gibbonUnitID'];
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
                    $gibbonPlannerEntryID = null;
                    if (isset($_POST['gibbonPlannerEntryID'])) {
                        if ($_POST['gibbonPlannerEntryID'] != '') {
                            $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
                        }
                    }
                    $name = $_POST['name'];
                    $description = $_POST['description'];
                    $type = $_POST['type'];
                    $date = (!empty($_POST['date']))? dateConvert($guid, $_POST['date']) : date('Y-m-d');
                    $gibbonSchoolYearTermID = (!empty($_POST['gibbonSchoolYearTermID']))? $_POST['gibbonSchoolYearTermID'] : null;
                    //Sort out attainment
                    $attainment = $_POST['attainment'];
                    $attainmentWeighting = null;
                    $attainmentRaw = 'N';
                    $attainmentRawMax = null;
                    if ($attainment == 'N') {
                        $gibbonScaleIDAttainment = null;
                        $gibbonRubricIDAttainment = null;
                    } else {
                        if ($_POST['gibbonScaleIDAttainment'] == '') {
                            $gibbonScaleIDAttainment = null;
                        } else {
                            $gibbonScaleIDAttainment = $_POST['gibbonScaleIDAttainment'];
                            if (isset($_POST['attainmentWeighting'])) {
                                if (is_numeric($_POST['attainmentWeighting']) && $_POST['attainmentWeighting'] > 0) {
                                    $attainmentWeighting = $_POST['attainmentWeighting'];
                                }
                            }
                            if (isset($_POST['attainmentRawMax'])) {
                                if (is_numeric($_POST['attainmentRawMax']) && $_POST['attainmentRawMax'] > 0) {
                                    $attainmentRawMax = $_POST['attainmentRawMax'];
                                    $attainmentRaw = 'Y';
                                }
                            }
                        }
                        if ($enableRubrics != 'Y') {
                            $gibbonRubricIDAttainment = null;
                        }
                        else {
                            if ($_POST['gibbonRubricIDAttainment'] == '') {
                                $gibbonRubricIDAttainment = null;
                            } else {
                                $gibbonRubricIDAttainment = $_POST['gibbonRubricIDAttainment'];
                            }
                        }
                    }
                    //Sort out effort
                    if ($enableEffort != 'Y') {
                        $effort = 'N';
                    }
                    else {
                        $effort = $_POST['effort'];
                    }
                    if ($effort == 'N') {
                        $gibbonScaleIDEffort = null;
                        $gibbonRubricIDEffort = null;
                    } else {
                        if ($_POST['gibbonScaleIDEffort'] == '') {
                            $gibbonScaleIDEffort = null;
                        } else {
                            $gibbonScaleIDEffort = $_POST['gibbonScaleIDEffort'];
                        }
                        if ($enableRubrics != 'Y') {
                            $gibbonRubricIDEffort = null;
                        }
                        else {
                            if ($_POST['gibbonRubricIDEffort'] == '') {
                                $gibbonRubricIDEffort = null;
                            } else {
                                $gibbonRubricIDEffort = $_POST['gibbonRubricIDEffort'];
                            }
                        }
                    }
                    $comment = $_POST['comment'];
                    $uploadedResponse = $_POST['uploadedResponse'];
                    $completeDate = $_POST['completeDate'];
                    if ($completeDate == '') {
                        $completeDate = null;
                        $complete = 'N';
                    } else {
                        $completeDate = dateConvert($guid, $completeDate);
                        $complete = 'Y';
                    }
                    $viewableStudents = $_POST['viewableStudents'];
                    $viewableParents = $_POST['viewableParents'];
                    $attachment = $_POST['attachment'];
                    $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];

                    $partialFail = false;

                    //Move attached image  file, if there is one
                    if (!empty($_FILES['file']['tmp_name'])) {
                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                        $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                        // Upload the file, return the /uploads relative path
                        $attachment = $fileUploader->uploadFromPost($file, $name);

                        if (empty($attachment)) {
                            $partialFail = true;
                        }
                    }

                    if ($name == '' or $description == '' or $type == '' or $date == '' or $viewableStudents == '' or $viewableParents == '') {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        //Write to database
                        try {
                            $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonHookID' => $gibbonHookID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'name' => $name, 'description' => $description, 'type' => $type, 'date' => $date, 'attainment' => $attainment, 'gibbonScaleIDAttainment' => $gibbonScaleIDAttainment, 'attainmentWeighting' => $attainmentWeighting, 'attainmentRaw' => $attainmentRaw, 'attainmentRawMax' => $attainmentRawMax, 'effort' => $effort, 'gibbonScaleIDEffort' => $gibbonScaleIDEffort, 'gibbonRubricIDAttainment' => $gibbonRubricIDAttainment, 'gibbonRubricIDEffort' => $gibbonRubricIDEffort, 'comment' => $comment, 'uploadedResponse' => $uploadedResponse, 'completeDate' => $completeDate, 'complete' => $complete, 'viewableStudents' => $viewableStudents, 'viewableParents' => $viewableParents, 'attachment' => $attachment, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'gibbonSchoolYearTermID' => $gibbonSchoolYearTermID, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                            $sql = 'UPDATE gibbonMarkbookColumn SET gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, date=:date, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, attainmentWeighting=:attainmentWeighting, attainmentRaw=:attainmentRaw, attainmentRawMax=:attainmentRawMax, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, gibbonRubricIDEffort=:gibbonRubricIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, gibbonSchoolYearTermID=:gibbonSchoolYearTermID WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
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
}
