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

$time = time();

if (empty($_POST) or empty($_FILES)) {
    echo "<span style='font-weight: bold; color: #ff0000'>";
    echo 'Your request failed due to an attachment error.';
    echo '</span>';
} else {
    //Proceed!
    $id = $_POST['id'];
    $imagesAsLinks = false;
    if ($_POST['imagesAsLinks'] == 'Y') {
        $imagesAsLinks = true;
    }

    if ($id == '') {
        echo "<span style='font-weight: bold; color: #ff0000'>";
        echo __($guid, 'Your request failed because your inputs were invalid.');
        echo '</span>';
    } else {
        //Check if multiple files
        $multiple = false;
        $multipleCount = 0;
        for ($i = 1; $i < 5; ++$i) {
            if (isset($_FILES[$id.'file'.$i])) {
                ++$multipleCount;
            }
        }
        if ($multipleCount > 1) {
            $multiple = true;
        }

        //Insert files
        for ($i = 1; $i < 5; ++$i) {
            $html = '';
            if (isset($_FILES[$id.'file'.$i])) {
                $name = substr($_FILES[$id.'file'.$i]['name'], 0, strrpos($_FILES[$id.'file'.$i]['name'], '.'));
                if ($_FILES[$id.'file'.$i]['tmp_name'] != '') {
                    //Check for folder in uploads based on today's date
                    $path = $_SESSION[$guid]['absolutePath'];
                    if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                        mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                    }
                    $unique = false;
                    $count = 0;
                    while ($unique == false and $count < 100) {
                        $suffix = randomPassword(16);
                        $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_$suffix".strrchr($_FILES[$id.'file'.$i]['name'], '.');
                        if (!(file_exists($path.'/'.$attachment))) {
                            $unique = true;
                        }
                        ++$count;
                    }
                    if (!(move_uploaded_file($_FILES[$id.'file'.$i]['tmp_name'], $path.'/'.$attachment))) {
                        echo "<span style='font-weight: bold; color: #ff0000'>";
                        echo 'Your request failed due to an attachment error.';
                        echo '</span>';
                    }
                }

                $extension = strrchr($attachment, '.');
                if ((strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) and $imagesAsLinks == false) {
                    $html = "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/'.$attachment."'><img class='resource' style='max-width: 500px' src='".$_SESSION[$guid]['absoluteURL'].'/'.$attachment."'></a>";
                } else {
                    $html = "<a target='_blank' style='font-weight: bold' href='".$_SESSION[$guid]['absoluteURL'].'/'.$attachment."'>".$name.'</a>';
                }
            }
            if ($multiple) {
                echo '<br/>';
            }
            echo $html;
        }
    }
}
