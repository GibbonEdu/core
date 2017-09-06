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

$gibbonYearGroupID = (isset($_REQUEST['gibbonYearGroupID']))? $_REQUEST['gibbonYearGroupID'] : null;

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/courseEnrolment_sync_edit.php&gibbonYearGroupID='.$gibbonYearGroupID;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $syncEnabled = (isset($_POST['syncEnabled']))? $_POST['syncEnabled'] : null;
    $syncTo = (isset($_POST['syncTo']))? $_POST['syncTo'] : null;

    if (empty($gibbonYearGroupID) || empty($syncTo) || empty($syncEnabled)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $partialFail = false;

        foreach ($syncTo as $gibbonCourseClassID => $gibbonRollGroupID) {
            if (!empty($syncEnabled[$gibbonCourseClassID]) && !empty($gibbonRollGroupID)) {
                // Enabled and Set: insert or update
                $data = array(
                    'gibbonCourseClassID' => $gibbonCourseClassID,
                    'gibbonRollGroupID' => $gibbonRollGroupID,
                    'gibbonYearGroupID' => $gibbonYearGroupID,
                );

                $sql = "INSERT INTO gibbonCourseClassMap SET gibbonCourseClassID=:gibbonCourseClassID, gibbonRollGroupID=:gibbonRollGroupID, gibbonYearGroupID=:gibbonYearGroupID ON DUPLICATE KEY UPDATE gibbonRollGroupID=:gibbonRollGroupID, gibbonYearGroupID=:gibbonYearGroupID";
                $pdo->executeQuery($data, $sql);

                if (!$pdo->getQuerySuccess()) $partialFail = true;
            } else {
                // Not enabled or not set: delete record (if one exists)
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sql = "DELETE FROM gibbonCourseClassMap WHERE gibbonCourseClassID=:gibbonCourseClassID";
                $pdo->executeQuery($data, $sql);

                if (!$pdo->getQuerySuccess()) $partialFail = true;
            }
        }

        if ($partialFail) {
            $URL .= '&return=warning3';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
            exit;
        }
    }
}
