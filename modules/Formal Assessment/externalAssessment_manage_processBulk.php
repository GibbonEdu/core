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

use Gibbon\Services\Format;
use Gibbon\Domain\FormalAssessment\ExternalAssessmentStudentGateway;
use Gibbon\Domain\FormalAssessment\ExternalAssessmentStudentEntryGateway;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';
$search = $_POST['search'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Formal Assessment/externalAssessment.php&search='.$search;

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($action) || empty($gibbonPersonID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {
    // Proceed!
    $gibbonPersonIDList = is_array($gibbonPersonID)? $gibbonPersonID : [$gibbonPersonID];
    $eaStudentGateway = $container->get(ExternalAssessmentStudentGateway::class);
    $eaStudentEntryGateway = $container->get(ExternalAssessmentStudentEntryGateway::class);
    $partialFail = false;

    if ($action == 'Add') {
        $gibbonExternalAssessmentID = $_POST['gibbonExternalAssessmentID'] ?? '';
        $copyToGCSECheck = $_POST['copyToGCSECheck'] ?? 'N';
        $date = $_POST['date'] ?? '';

        if (empty($gibbonExternalAssessmentID) || empty($copyToGCSECheck) || empty($date)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        }
    
        foreach ($gibbonPersonIDList as $gibbonPersonID) {
            $data = [
                'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID,
                'gibbonPersonID' => $gibbonPersonID,
                'date' => Format::dateConvert($date),
            ];

            // Do not create a record if it already exists
            $isUnique = $eaStudentGateway->unique($data, ['gibbonExternalAssessmentID', 'gibbonPersonID', 'date']);
            if (!$isUnique) continue;

            // Insert the student, then add the entries
            if ($gibbonExternalAssessmentStudentID = $eaStudentGateway->insert($data)) {

                // Optionally copy CAT data to GCSE
                if ($gibbonExternalAssessmentID == 2 && $copyToGCSECheck == 'Y') {
                    $data = [
                        'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID,
                        'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID,
                        'gibbonPersonID' => $gibbonPersonID,
                    ];

                    $sql = "INSERT INTO gibbonExternalAssessmentStudentEntry 
                            (`gibbonExternalAssessmentStudentID`, `gibbonExternalAssessmentFieldID`, `gibbonScaleGradeID`) 
                            SELECT :gibbonExternalAssessmentStudentID, field.gibbonExternalAssessmentFieldID, 
                            (
                                SELECT gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID FROM gibbonExternalAssessment 
                                JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) 
                                JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID)
                                JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID AND gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) 
                                WHERE gibbonExternalAssessment.name='Cognitive Abilities Test' 
                                    AND gibbonExternalAssessmentStudent.gibbonPersonID=:gibbonPersonID
                                    AND gibbonExternalAssessmentField.name=field.name
                                    AND gibbonExternalAssessmentField.category LIKE '%GCSE Target Grades' 
                                    AND NOT (gibbonScaleGradeID IS NULL)
                                LIMIT 1
                            )
                            FROM gibbonExternalAssessmentField as field
                            WHERE field.gibbonExternalAssessmentID=:gibbonExternalAssessmentID
                            AND field.category='0_Target Grade'";
                } else {
                    $data = [
                        'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID,
                        'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID,
                    ];

                    $sql = "INSERT INTO gibbonExternalAssessmentStudentEntry 
                            (`gibbonExternalAssessmentStudentID`, `gibbonExternalAssessmentFieldID`, `gibbonScaleGradeID`) 
                            SELECT :gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID, NULL
                            FROM gibbonExternalAssessmentField
                            WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID";
                }

                $inserted = $pdo->insert($sql, $data);
                $partialFail &= !$inserted;
            }
        }

        $URL .= $partialFail
            ? '&return=warning1'
            : '&return=success0';
        header("Location: {$URL}");
    } else {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    }
}
