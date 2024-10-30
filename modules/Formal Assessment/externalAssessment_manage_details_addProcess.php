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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$count = 0;
if (is_numeric($_POST['count'])) {
    $count = $_POST['count'] ?? '';
}
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonExternalAssessmentID = $_POST['gibbonExternalAssessmentID'] ?? '';
$date = !empty($_POST['date']) ? Format::dateConvert($_POST['date']) : null;
$search = $_GET['search'] ?? '';
$allStudents = $_GET['allStudents'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/externalAssessment_manage_details_add.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID&gibbonPersonID=$gibbonPersonID&step=2&search=$search&allStudents=$allStudents";

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    if ($gibbonPersonID == '' or $gibbonExternalAssessmentID == '' or $date == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $attachment = '';
        //Move attached image  file, if there is one
        if (!empty($_FILES['file']['tmp_name'])) {
            $fileUploader = new Gibbon\FileUploader($pdo, $session);

            $file = (isset($_FILES['file']))? $_FILES['file'] : null;

            // Upload the file, return the /uploads relative path
            $attachment = $fileUploader->uploadFromPost($file, 'externalAssessmentUpload');

            if (empty($attachment)) {
                $partialFail = true;
            }
        }

        //Write to database
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'gibbonPersonID' => $gibbonPersonID, 'date' => $date, 'attachment' => $attachment);
            $sql = 'INSERT INTO gibbonExternalAssessmentStudent SET gibbonExternalAssessmentID=:gibbonExternalAssessmentID, gibbonPersonID=:gibbonPersonID, date=:date, attachment=:attachment';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

        //Scan through fields
        $partialFail = false;
        for ($i = 0; $i < $count; ++$i) {
            $gibbonExternalAssessmentFieldID = @$_POST[$i.'-gibbonExternalAssessmentFieldID'];
            if (isset($_POST[$i.'-gibbonScaleGradeID']) == false) {
                $gibbonScaleGradeID = null;
            } else {
                if ($_POST[$i.'-gibbonScaleGradeID'] == '') {
                    $gibbonScaleGradeID = null;
                } else {
                    $gibbonScaleGradeID = $_POST[$i.'-gibbonScaleGradeID'];
                }
            }

            if ($gibbonExternalAssessmentFieldID != '') {
                try {
                    $data = array('AI' => $AI, 'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID, 'gibbonScaleGradeID' => $gibbonScaleGradeID);
                    $sql = 'INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonExternalAssessmentStudentID=:AI, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID, gibbonScaleGradeID=:gibbonScaleGradeID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
        }

        if ($partialFail == true) {
            $URL .= "&return=error1&editID=$AI";
            header("Location: {$URL}");
        } else {
            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
