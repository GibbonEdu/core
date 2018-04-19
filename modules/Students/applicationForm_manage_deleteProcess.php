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

include '../../functions.php';
include '../../config.php';

$gibbonApplicationFormID = $_POST['gibbonApplicationFormID'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$search = $_GET['search'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage_delete.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonApplicationFormID == '' or $gibbonSchoolYearID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
            $sql = 'SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
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

            //Write to database
            try {
                $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
                $sql = 'DELETE FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Attempt to write logo
            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], getModuleIDFromName($connection2, 'Students'), $_SESSION[$guid]['gibbonPersonID'], 'Application Form - Delete', array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'applicationFormContents' => serialize($row)), $_SERVER['REMOTE_ADDR']);


            // Clean up the application form relationships
            try {
                $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
                $sql = 'DELETE FROM gibbonApplicationFormRelationship WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            // Clean up the links between this and other forms
            try {
                $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
                $sql = 'DELETE FROM gibbonApplicationFormLink WHERE gibbonApplicationFormID1=:gibbonApplicationFormID OR gibbonApplicationFormID2=:gibbonApplicationFormID';
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
