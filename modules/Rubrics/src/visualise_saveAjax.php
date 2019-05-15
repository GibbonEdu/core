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

require_once "../../../gibbon.php";

$fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
$uploadsFolder = $fileUploader->getUploadsFolderByDate();

$img = (!empty($_POST['img'])) ? $_POST['img'] : null;
$gibbonPersonID = (!empty($_POST['gibbonPersonID'])) ? str_pad($_POST['gibbonPersonID'], 10, '0', STR_PAD_LEFT) : null;
$uploadsFolder = (!empty($_POST['path'])) ? $_POST['path'] : $uploadsFolder;

list($type, $img) = explode(';', $img);
list(, $img)      = explode(',', $img);
$img = base64_decode($img);

$destinationFolder = $gibbon->session->get('absolutePath').'/'.$uploadsFolder;

if (is_dir($destinationFolder) == false) {
    mkdir($destinationFolder, 0755, true);
}

$fp = fopen($destinationFolder.'/rubric_visualisation_'.$gibbonPersonID.'.png', 'w');
fwrite($fp, $img);
fclose($fp);
