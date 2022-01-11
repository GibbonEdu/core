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

use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonFirstAidID = $_GET['gibbonFirstAidID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/firstAidRecord_edit.php&gibbonFirstAidID=$gibbonFirstAidID&gibbonFormGroupID=".$_GET['gibbonFormGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonFirstAidID specified
    if ($gibbonFirstAidID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonFirstAidID' => $gibbonFirstAidID);
            $sql = "SELECT gibbonFirstAid.*, patient.surname AS surnamePatient, patient.preferredName AS preferredNamePatient, firstAider.title, firstAider.surname AS surnameFirstAider, firstAider.preferredName AS preferredNameFirstAider
                FROM gibbonFirstAid
                    JOIN gibbonPerson AS patient ON (gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID)
                    JOIN gibbonPerson AS firstAider ON (gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID)
                    JOIN gibbonStudentEnrolment ON (patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFirstAidID=:gibbonFirstAidID";
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
            $gibbonPersonID = $row['gibbonPersonIDFirstAider'];
            $timeOut = (!empty($_POST['timeOut'])) ? $_POST['timeOut'] : null;
            $followUp = $_POST['followUp'] ?? '';

            $customRequireFail = false;
            $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('First Aid', [], $customRequireFail);

            if ($customRequireFail) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }

            try {
                $data = array('timeOut' => $timeOut, 'gibbonFirstAidID' => $gibbonFirstAidID, 'fields' => $fields);
                $sql = 'UPDATE gibbonFirstAid SET timeOut=:timeOut, fields=:fields WHERE gibbonFirstAidID=:gibbonFirstAidID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if (!empty($followUp)) {
                try {
                    $data = array('gibbonFirstAidID' => $gibbonFirstAidID, 'gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'followUp' => $followUp);
                    $sql = 'INSERT INTO gibbonFirstAidFollowUp SET gibbonFirstAidID=:gibbonFirstAidID, gibbonPersonID=:gibbonPersonID, followUp=:followUp';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }
            }

            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
