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

//Gibbon system-wide includes
include './functions.php';
include './config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php';

//Proceed!
//Check if planner specified
if ($gibbonPersonID == '' or $gibbonPersonID != $_SESSION[$guid]['gibbonPersonID'] or $_FILES['file1']['tmp_name'] == '') {
    $URL .= '?return=error1';
    header("Location: {$URL}");
} else {
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '?return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount() != 1) {
        $URL .= '?return=error2';
        header("Location: {$URL}");
    } else {
        $attachment1 = null;
        if ($_FILES['file1']['tmp_name'] != '') {
            $time = time();
            //Check for folder in uploads based on today's date
            $path = $_SESSION[$guid]['absolutePath'];
            if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
            }

            $unique = false;
            $count = 0;
            while ($unique == false and $count < 100) {
                if ($count == 0) {
                    $attachment1 = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.$_SESSION[$guid]['username'].'_240'.strrchr($_FILES['file1']['name'], '.');
                } else {
                    $attachment1 = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.$_SESSION[$guid]['username'].'_240'."_$count".strrchr($_FILES['file1']['name'], '.');
                }

                if (!(file_exists($path.'/'.$attachment1))) {
                    $unique = true;
                }
                ++$count;
            }

            if (!(move_uploaded_file($_FILES['file1']['tmp_name'], $path.'/'.$attachment1))) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
                exit();
            }
        }

        //Check for reasonable image
        $size = getimagesize($path.'/'.$attachment1);
        $width = $size[0];
        $height = $size[1];
        if ($width < 240 or $height < 320) {
            $URL .= '?return=error6';
            header("Location: {$URL}");
        } elseif ($width > 480 or $height > 640) {
            $URL .= '?return=error6';
            header("Location: {$URL}");
        } elseif (($width / $height) < 0.60 or ($width / $height) > 0.8) {
            $URL .= '?return=error6';
            header("Location: {$URL}");
        } else {
            //UPDATE
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'attachment1' => $attachment1);
                $sql = 'UPDATE gibbonPerson SET image_240=:attachment1 WHERE gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '?return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Update session variables
            $_SESSION[$guid]['image_240'] = $attachment1;

            //Clear cusotm sidebar
            unset($_SESSION[$guid]['index_customSidebar.php']);

            $URL .= '?return=success0';
            header("Location: {$URL}");
        }
    }
}
