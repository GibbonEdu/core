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

$img = $_POST['img'] ?? null;
$imgPath = $_POST['path'] ?? null;
$gibbonPersonID = !empty($_POST['gibbonPersonID']) ? str_pad($_POST['gibbonPersonID'], 10, '0', STR_PAD_LEFT) : null;
$absolutePath = $gibbon->session->get('absolutePath');

if (empty($img) || empty($gibbonPersonID) || empty($absolutePath)) {
    return;
}

// Decode raw image data
list($type, $img) = explode(';', $img);
list(, $img)      = explode(',', $img);
$img = base64_decode($img);

// Create an uploads path if one isn't supplied
if (empty($imgPath)) {
    $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
    $imgPath = $fileUploader->getUploadsFolderByDate().'/rubric_visualisation_'.$gibbonPersonID.'.png';
}

// Ensure destination folder exists
$destinationFolder = $absolutePath.'/'.dirname($imgPath);
if (is_dir($destinationFolder) == false) {
    mkdir($destinationFolder, 0755, true);
}

// Write image data
$fp = fopen($absolutePath.'/'.$imgPath, 'w');
fwrite($fp, $img);
fclose($fp);

// Return image path to AJAX
echo $imgPath;
