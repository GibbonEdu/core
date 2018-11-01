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

$gibbonActivityID = $_GET['gibbonActivityID'];
$gibbonPersonID = $_GET['gibbonPersonID'];

if ($gibbonActivityID == '' or $gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_manage_enrolment_edit.php&gibbonPersonID=$gibbonPersonID&gibbonActivityID=$gibbonActivityID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID'];

    if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if school year specified
        $status = $_POST['status'];
        if ($status == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT gibbonActivity.*, gibbonActivityStudent.*, surname, preferredName FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID';
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
                $statusOld = $row['status'];

                //Write to database
                try {
                    $data = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID, 'status' => $status);
                    $sql = 'UPDATE gibbonActivityStudent SET status=:status WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Set log
                if ($statusOld != $status) {
                    $gibbonModuleID = getModuleIDFromName($connection2, 'Activities') ;
                    setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], $gibbonModuleID, $_SESSION[$guid]['gibbonPersonID'], 'Activities - Student Status Changed', array('gibbonPersonIDStudent' => $gibbonPersonID, 'statusOld' => $statusOld, 'statusNew' => $status));
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
