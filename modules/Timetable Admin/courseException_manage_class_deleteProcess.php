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

$gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseClassSlotExceptionID = $_GET['gibbonCourseClassSlotExceptionID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '' or $gibbonCourseClassSlotExceptionID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseException_manage_class_edit.php&gibbonCourseID=$gibbonCourseID&gibbonCourseClassSlotExceptionID=$gibbonCourseClassSlotExceptionID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseClassID=$gibbonCourseClassID";
    $URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseException_manage_class_edit.php&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit_delete.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if gibbonCourseClassSlotExceptionID specified
        if ($gibbonCourseClassSlotExceptionID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonCourseClassSlotExceptionID' => $gibbonCourseClassSlotExceptionID);
                $sql = 'SELECT * 
                FROM gibbonCourseClassSlotException 
                WHERE gibbonCourseClassSlotException.gibbonCourseClassSlotExceptionID=:gibbonCourseClassSlotExceptionID';
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
                    $data = array('gibbonCourseClassSlotExceptionID' => $gibbonCourseClassSlotExceptionID);
                    $sql = 'DELETE FROM gibbonCourseClassSlotException WHERE gibbonCourseClassSlotExceptionID=:gibbonCourseClassSlotExceptionID';
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
