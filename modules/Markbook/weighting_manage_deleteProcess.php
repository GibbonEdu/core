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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$gibbonMarkbookWeightID = (isset($_POST['gibbonMarkbookWeightID']))? $_POST['gibbonMarkbookWeightID'] : null;
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/weighting_manage_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=$gibbonMarkbookWeightID";
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/weighting_manage.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else if (empty($gibbonCourseClassID) || empty($gibbonMarkbookWeightID)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {

        try {
            $data2 = array('gibbonMarkbookWeightID' => $gibbonMarkbookWeightID);
            $sql2 = 'SELECT type FROM gibbonMarkbookWeight WHERE gibbonMarkbookWeightID=:gibbonMarkbookWeightID';
            $result2 = $connection2->prepare($sql2);
            $result2->execute($data2);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result2->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonMarkbookWeightID' => $gibbonMarkbookWeightID);
                $sql = 'DELETE FROM gibbonMarkbookWeight WHERE gibbonMarkbookWeightID=:gibbonMarkbookWeightID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URLDelete .= "&return=success0";
            header("Location: {$URLDelete}");
        }
    }
}

?>