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
use Gibbon\FileUploader;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

// Check that the current user is logged in
if (!$session->has('gibbonPersonID')) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// Check origin of the upload request
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $serverPath = substr($session->get('absoluteURL'), 0, strlen($_SERVER['HTTP_ORIGIN']));
    if (strtolower($serverPath) == strtolower($_SERVER['HTTP_ORIGIN'])) {
      header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
      header("HTTP/1.1 403 Origin Denied" .$_SERVER['HTTP_ORIGIN'] );
      exit;
    }
}

// Check that a file has been passed
if (empty($_FILES)) {
    header("HTTP/1.1 400 Invalid inputs.");
    exit;
}

// Don't attempt to process the upload on an OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    exit;
}

reset($_FILES);
$file = current($_FILES);
if (is_uploaded_file($file['tmp_name'])) {

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $file['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        exit;
    }

    // Verify extension
    $fileUploader = $container->get(FileUploader::class);
    $fileTypes = $fileUploader->getFileExtensions('Document');
    $imageTypes = $fileUploader->getFileExtensions('Graphics/Design');
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($fileExtension, $imageTypes)) {
        $fileUploader->setFileExtensions($imageTypes);
        $attachment = $fileUploader->uploadAndResizeImage($file, '', 2048, 85);
    } elseif (in_array($fileExtension, $fileTypes)) {
        $fileUploader->setFileExtensions($fileTypes);
        $attachment = $fileUploader->uploadFromPost($file);
    } else {
        header("HTTP/1.1 400 Invalid extension.");
        exit;
    }

    if (!empty($attachment)) {
        echo json_encode(['location' => $session->get('absoluteURL') . '/' . $attachment], JSON_FORCE_OBJECT);
        exit;
    }
}

if (empty($attachment)) {
    // Notify editor that the upload failed
    header("HTTP/1.1 500 Server Error");
    exit;
}
