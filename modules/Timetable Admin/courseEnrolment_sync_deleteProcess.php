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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonYearGroupID = (isset($_POST['gibbonYearGroupID']))? $_POST['gibbonYearGroupID'] : null;

    if (empty($gibbonYearGroupID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $data = array('gibbonYearGroupID' => $gibbonYearGroupID);
        $sql = "DELETE FROM gibbonCourseClassMap WHERE gibbonCourseClassMap.gibbonYearGroupID=:gibbonYearGroupID";

        $pdo->executeQuery($data, $sql);

        if ($pdo->getQuerySuccess() == false) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= "&return=success0";
            header("Location: {$URL}");
            exit;
        }
    }
}
