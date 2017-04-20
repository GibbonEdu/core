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
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/weighting_manage.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else if (empty($gibbonCourseClassID)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {

        $description = (isset($_POST['description']))? $_POST['description'] : null;
        $type = (isset($_POST['type']))? $_POST['type'] : null;
        $weighting = (isset($_POST['weighting']))? floatval($_POST['weighting']) : 0;
        $weighting = max(0, min(100, $weighting) );
        $reportable = (isset($_POST['reportable']))? $_POST['reportable'] : null;
        $calculate = (isset($_POST['calculate']))? $_POST['calculate'] : null;

        if ( empty($description) || empty($type) || empty($reportable) || empty($calculate) || $weighting === ''  ) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'description' => $description, 'type' => $type, 'weighting' => $weighting, 'reportable' => $reportable, 'calculate' => $calculate );
                $sql = 'INSERT INTO gibbonMarkbookWeight SET gibbonCourseClassID=:gibbonCourseClassID, description=:description, type=:type, weighting=:weighting, reportable=:reportable, calculate=:calculate';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URL .= "&return=success0";
            header("Location: {$URL}");
        }
    }
}

?>