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

include '../../gibbon.php';

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonStudentEnrolmentID = $_POST['gibbonStudentEnrolmentID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonStudentEnrolmentID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage_delete.php&gibbonStudentEnrolmentID=$gibbonStudentEnrolmentID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";
    $URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Admissions/studentEnrolment_manage_delete.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonStudentEnrolmentID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                $sql = 'SELECT gibbonFormGroup.gibbonFormGroupID, gibbonYearGroup.gibbonYearGroupID,gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonFormGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID ORDER BY surname, preferredName';
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
                //Write to database
                try {
                    $data = array('gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                    $sql = 'DELETE FROM gibbonStudentEnrolment WHERE gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
