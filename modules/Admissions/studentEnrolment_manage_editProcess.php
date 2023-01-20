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
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Data\Validator;
use Gibbon\Forms\CustomFieldHandler;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonStudentEnrolmentID = $_POST['gibbonStudentEnrolmentID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonStudentEnrolmentID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/studentEnrolment_manage_edit.php&gibbonStudentEnrolmentID=$gibbonStudentEnrolmentID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Admissions/studentEnrolment_manage_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonStudentEnrolmentID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        } else {

            $customRequireFail = false;
            $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Student Enrolment', [], $customRequireFail);

            if ($customRequireFail) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }
            
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                $sql = 'SELECT gibbonFormGroup.gibbonFormGroupID, gibbonYearGroup.gibbonYearGroupID,gibbonStudentEnrolmentID, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonFormGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID ORDER BY surname, preferredName';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            } else {
                $row = $result->fetch();

                $gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
                $gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
                $gibbonFormGroupIDOriginal = $_POST['gibbonFormGroupIDOriginal'] ?? 'N';
                $formGroupOriginalNameShort = $_POST['formGroupOriginalNameShort'] ?? '';
                $gibbonPersonID = $row['gibbonPersonID'];

                $formGroupTo = $container->get(FormGroupGateway::class)->getFormGroupByID($gibbonFormGroupID);
                $formGroupToName = $formGroupTo['nameShort'];

                $rollOrder = $_POST['rollOrder'] ?? '';
                if ($rollOrder == '') {
                    $rollOrder = null;
                }

                //Check unique inputs for uniquness
                try {
                    $data = array('gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'rollOrder' => $rollOrder, 'gibbonFormGroupID' => $gibbonFormGroupID);
                    $sql = "SELECT * FROM gibbonStudentEnrolment WHERE rollOrder=:rollOrder AND gibbonFormGroupID=:gibbonFormGroupID AND NOT gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID AND NOT rollOrder=''";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                    exit;
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonFormGroupID' => $gibbonFormGroupID, 'rollOrder' => $rollOrder, 'fields' => $fields, 'gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID);
                        $sql = 'UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonFormGroupID=:gibbonFormGroupID, rollOrder=:rollOrder, fields=:fields WHERE gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit;
                    }

                    $partialFail = false;

                    // Handle automatic course enrolment if enabled
                    $autoEnrolStudent = $_POST['autoEnrolStudent'] ?? 'N';
                    if ($autoEnrolStudent == 'Y') {
                        $courseEnrolmentGateway = $container->get(CourseEnrolmentGateway::class);

                        // Remove existing auto-enrolment: moving a student from one Form Group to another
                        $courseEnrolmentGateway->unenrolAutomaticCourseEnrolments($gibbonFormGroupIDOriginal, $gibbonStudentEnrolmentID);
                        
                        $partialFail &= !$pdo->getQuerySuccess();

                        // Update existing course enrolments for new Form Group
                        $courseEnrolmentGateway->updateAutomaticCourseEnrolments($gibbonFormGroupID, $gibbonStudentEnrolmentID);

                        $partialFail &= !$pdo->getQuerySuccess();

                        // Add course enrolments for new Form Group
                        $courseEnrolmentGateway->insertAutomaticCourseEnrolments($gibbonFormGroupID, $gibbonPersonID);

                        $partialFail &= !$pdo->getQuerySuccess();
                    }

                    // Add student note
                    if ($gibbonFormGroupID != $gibbonFormGroupIDOriginal) {
                        $data = array('title' => __('Change of Form Group'), 'note' => __('Student\'s form group was changed from {formGroupFrom} to {formGroupTo} on {date}', ['formGroupFrom' => $formGroupOriginalNameShort, 'formGroupTo' => $formGroupToName, 'date' => Format::date(date('Y-m-d'))]), 'gibbonPersonID' => $gibbonPersonID, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'timestamp' => date('Y-m-d H:i:s', time()));
                        $sql = 'INSERT INTO gibbonStudentNote SET title=:title, note=:note, gibbonPersonID=:gibbonPersonID, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);

                        if ($pdo->getQuerySuccess() == false) {
                            $partialFail = true;
                        }
                    }

                    $URL .= $partialFail
                        ? '&return=warning1'
                        : '&return=success0';
                    header("Location: {$URL}");
                    exit;
                }
            }
        }
    }
}
