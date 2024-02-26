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
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$settingGateway = $container->get(SettingGateway::class);
$enableEffort = $settingGateway->getSettingByScope('Markbook', 'enableEffort');
$enableRubrics = $settingGateway->getSettingByScope('Markbook', 'enableRubrics');

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$address = $_GET['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        $gibbonUnitID = $_POST['gibbonUnitID'] ?? '';
        $gibbonPlannerEntryID = !empty($_POST['gibbonPlannerEntryID']) ? $_POST['gibbonPlannerEntryID'] : null;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? '';
        $date = (!empty($_POST['date']))? Format::dateConvert($_POST['date']) : date('Y-m-d');
        $gibbonSchoolYearTermID = $_POST['gibbonSchoolYearTermID'] ?? null;

        // Grab the appropriate term ID if the date is provided and the term ID is not
        if (empty($gibbonSchoolYearTermID) && !empty($date)) {
            try {
                $dataTerm = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => $date);
                $sqlTerm = "SELECT gibbonSchoolYearTermID FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND :date BETWEEN firstDay AND lastDay";
                $resultTerm = $connection2->prepare($sqlTerm);
                $resultTerm->execute($dataTerm);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
            if ($resultTerm->rowCount() > 0) {
                $gibbonSchoolYearTermID = $resultTerm->fetchColumn(0);
            }
        }

        //Sort out attainment
        $attainment = $_POST['attainment'] ?? '';
        $attainmentWeighting = 1;
        $attainmentRaw = 'N';
        $attainmentRawMax = null;
        if ($attainment == 'N') {
            $gibbonScaleIDAttainment = null;
            $gibbonRubricIDAttainment = null;
        } else {
            if ($_POST['gibbonScaleIDAttainment'] == '') {
                $gibbonScaleIDAttainment = null;
            } else {
                $gibbonScaleIDAttainment = $_POST['gibbonScaleIDAttainment'] ?? '';
                if (isset($_POST['attainmentWeighting'])) {
                    if (is_numeric($_POST['attainmentWeighting']) && $_POST['attainmentWeighting'] > 0) {
                        $attainmentWeighting = $_POST['attainmentWeighting'] ?? '';
                    }
                }
                if (isset($_POST['attainmentRawMax'])) {
                    if (is_numeric($_POST['attainmentRawMax']) && $_POST['attainmentRawMax'] > 0) {
                        $attainmentRawMax = $_POST['attainmentRawMax'] ?? '';
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
                    $gibbonRubricIDAttainment = $_POST['gibbonRubricIDAttainment'] ?? '';
                }
            }
        }
        //Sort out effort
        if ($enableEffort != 'Y') {
            $effort = 'N';
        }
        else {
            $effort = $_POST['effort'] ?? '';
        }
        if ($effort == 'N') {
            $gibbonScaleIDEffort = null;
            $gibbonRubricIDEffort = null;
        } else {
            if ($_POST['gibbonScaleIDEffort'] == '') {
                $gibbonScaleIDEffort = null;
            } else {
                $gibbonScaleIDEffort = $_POST['gibbonScaleIDEffort'] ?? '';
            }
            if ($enableRubrics != 'Y') {
                $gibbonRubricIDEffort = null;
            }
            else {
                if ($_POST['gibbonRubricIDEffort'] == '') {
                    $gibbonRubricIDEffort = null;
                } else {
                    $gibbonRubricIDEffort = $_POST['gibbonRubricIDEffort'] ?? '';
                }
            }
        }
        $comment = $_POST['comment'] ?? '';
        $uploadedResponse = $_POST['uploadedResponse'] ?? '';
        $completeDate = $_POST['completeDate'] ?? '';
        if ($completeDate == '') {
            $completeDate = null;
            $complete = 'N';
        } else {
            $completeDate = Format::dateConvert($completeDate);
            $complete = 'Y';
        }
        $viewableStudents = $_POST['viewableStudents'] ?? '';
        $viewableParents = $_POST['viewableParents'] ?? '';
        $attachment = '';
        $gibbonPersonIDCreator = $session->get('gibbonPersonID');
        $gibbonPersonIDLastEdit = $session->get('gibbonPersonID');

        $sequenceNumber = null;

        // Build the initial column counts for this class
        try {
            $dataSequence = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sqlSequence = 'SELECT max(sequenceNumber) as max FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $resultSequence = $connection2->prepare($sqlSequence);
            $resultSequence->execute($dataSequence);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($resultSequence && $resultSequence->rowCount() > 0) {
            $sequenceNumber = $resultSequence->fetchColumn() + 1;
        }

        $partialFail = false;

        //Move attached image  file, if there is one
        if (!empty($_FILES['file']['tmp_name'])) {
            $fileUploader = new Gibbon\FileUploader($pdo, $session);

            $file = (isset($_FILES['file']))? $_FILES['file'] : null;

            // Upload the file, return the /uploads relative path
            $attachment = $fileUploader->uploadFromPost($file, $name);

            if (empty($attachment)) {
                $partialFail = true;
            }
        }

        if ($name == '' or $description == '' or $type == '' or $date == '' or $viewableStudents == '' or $viewableParents == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'name' => $name, 'description' => $description, 'type' => $type, 'date' => $date, 'sequenceNumber' => $sequenceNumber, 'attainment' => $attainment, 'gibbonScaleIDAttainment' => $gibbonScaleIDAttainment, 'attainmentWeighting' => $attainmentWeighting, 'attainmentRaw' => $attainmentRaw, 'attainmentRawMax' => $attainmentRawMax, 'effort' => $effort, 'gibbonScaleIDEffort' => $gibbonScaleIDEffort, 'gibbonRubricIDAttainment' => $gibbonRubricIDAttainment, 'gibbonRubricIDEffort' => $gibbonRubricIDEffort, 'comment' => $comment, 'uploadedResponse' => $uploadedResponse, 'completeDate' => $completeDate, 'complete' => $complete, 'viewableStudents' => $viewableStudents, 'viewableParents' => $viewableParents, 'attachment' => $attachment, 'gibbonPersonIDCreator' => $gibbonPersonIDCreator, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'gibbonSchoolYearTermID' => $gibbonSchoolYearTermID);
                $sql = 'INSERT INTO gibbonMarkbookColumn SET gibbonUnitID=:gibbonUnitID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, date=:date, sequenceNumber=:sequenceNumber, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, attainmentWeighting=:attainmentWeighting, attainmentRaw=:attainmentRaw, attainmentRawMax=:attainmentRawMax, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, gibbonRubricIDEffort=:gibbonRubricIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, gibbonSchoolYearTermID=:gibbonSchoolYearTermID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 10, '0', STR_PAD_LEFT);

            //Unlock module table

                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
            }
        }
    }
}
