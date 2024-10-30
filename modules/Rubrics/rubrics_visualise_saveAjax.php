<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Data\Validator;

require_once "../../gibbon.php";

$_POST = $container->get(Validator::class)->sanitize($_POST);

$img = $_POST['img'] ?? null;
$gibbonPersonID = !empty($_POST['gibbonPersonID']) ? str_pad($_POST['gibbonPersonID'], 10, '0', STR_PAD_LEFT) : null;
$gibbonPersonID = preg_replace('/[^a-zA-Z0-9]/', '', $gibbonPersonID);

$absolutePath = $session->get('absolutePath');

if (!$session->has('gibbonPersonID')) {
    return;
}

if (empty($img) || empty($gibbonPersonID) || empty($absolutePath)) {
    return;
}

// Decode raw image data
list($type, $img) = explode(';', $img);
list(, $img)      = explode(',', $img);
$img = base64_decode($img);

if ($img === false || mb_stripos($type, 'image/png') === false) {
    return;
}

// Strip directory off of the path, only use sanitized filename
$imgPath = !empty($_POST['path']) ? basename($_POST['path']) : '';
$imgPath = mb_substr($imgPath, 0, mb_strrpos($imgPath, '.'));
$imgPath = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $imgPath);

// Create an uploads path if one isn't supplied
if (empty($imgPath)) {
    $imgPath = 'rubric_visualisation_'.$gibbonPersonID.'.png';
} else {
    $imgPath .= '.png';
}

$fileUploader = new Gibbon\FileUploader($pdo, $session);
$imgPath = $fileUploader->getUploadsFolderByDate().'/'.$imgPath;

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
