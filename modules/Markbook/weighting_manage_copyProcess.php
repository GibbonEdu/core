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


$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/weighting_manage.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $gibbonWeightingCopyClassID = (isset($_POST['gibbonWeightingCopyClassID']))? $_POST['gibbonWeightingCopyClassID'] : null;

    if (empty($_POST)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else if (empty($gibbonCourseClassID) || empty($gibbonWeightingCopyClassID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {

        try {
            $data2 = array('gibbonCourseClassID' => $gibbonWeightingCopyClassID);
            $sql2 = 'SELECT * FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result2 = $connection2->prepare($sql2);
            $result2->execute($data2);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result2->rowCount() <= 0) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
            exit();
        } else {

            $partialFail = false;
            while ($weighting = $result2->fetch() ) {

                //Write to database
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'description' => $weighting['description'], 'type' => $weighting['type'], 'weighting' => $weighting['weighting'], 'reportable' => $weighting['reportable'], 'calculate' => $weighting['calculate'] );

                    $sql = 'INSERT INTO gibbonMarkbookWeight SET gibbonCourseClassID=:gibbonCourseClassID, description=:description, type=:type, weighting=:weighting, reportable=:reportable, calculate=:calculate';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                if ($partialFail) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                    exit();
                } else {
                    $URL .= "&return=success0";
                    header("Location: {$URL}");
                }
            }
            
        }
    }
}

?>