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

$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
$gibbonCourseID = $_POST['gibbonCourseID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$search = $_POST['search'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/courseException_manage_class_edit.php&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseClassID=$gibbonCourseClassID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_exception_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else if ($gibbonCourseClassID == '' or $gibbonCourseID == '' or $gibbonSchoolYearID == '' ) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {
    $choices = $_POST['Members'] ?? [];
    $slot = $_POST['slot'] ?? 0;

    if($slot == 0) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    }
    //Proceed!
    //Check if person specified
    if (count($choices) < 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        foreach ($choices as $t) {
            //Check to see if person is already exempted from this class
            try {
                $data = array('gibbonPersonID' => $t, 'gibbonCourseClassSlotID' => $slot);
                $sql = 'SELECT * FROM gibbonCourseClassSlotException WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassSlotID=:gibbonCourseClassSlotID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            //If student not in course, add them
            if ($result->rowCount() == 0) {
                try {
                    $data = array('gibbonPersonID' => $t, 'gibbonCourseClassSlotID' => $slot);
                    $sql = 'INSERT INTO gibbonCourseClassSlotException SET gibbonPersonID=:gibbonPersonID, gibbonCourseClassSlotID=:gibbonCourseClassSlotID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }else{
                $partialFail = true;
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }

}
