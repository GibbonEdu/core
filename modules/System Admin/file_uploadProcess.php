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

use Gibbon\FileUploader;
use Gibbon\Data\Validator;
use Gibbon\Domain\User\UserGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/System Admin/file_upload.php&step=3';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/import_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $type = $_POST['type'] ?? '';
    $identifier = $_POST['identifier'] ?? '';
    $file = $_POST['file'] ?? '';
    $fileSeparator = $_POST['fileSeparator'] ?? '';
    $fileSection = $_POST['fileSection'] ?? '';
    $gibbonPersonalDocumentTypeID = $_POST['gibbonPersonalDocumentTypeID'] ?? '';
    $gibbonCustomFieldID = $_POST['gibbonCustomFieldID'] ?? '';
    $overwrite = $_POST['overwrite'] ?? 'N';
    $zoom = $_POST['zoom'] ?? '100';
    $focalX = $_POST['focalX'] ?? '50';
    $focalY = $_POST['focalY'] ?? '50';

    if (empty($identifier) || empty($type) || empty($file)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($identifier != 'username' && $identifier != 'studentID') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $absolutePath = $gibbon->session->get('absolutePath');
    if (!is_file($absolutePath.'/'.$file)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $userGateway = $container->get(UserGateway::class);
    $fileUploader = $container->get(FileUploader::class);
    $files = $fileUploader->uploadFromZIP($absolutePath.'/'.$file);

    $partialFail = false;
    $count = 0;

    foreach ($files as $file) {
        // Optionally split the filenames by a separator character
        if (!empty($fileSeparator) && !empty($fileSection)) {
            $fileParts = explode($fileSeparator, mb_strrchr($file['originalName'], '.', true));
            $identifierValue = $fileParts[$fileSection-1] ?? '';
        } else {
            $identifierValue = mb_strrchr($file['originalName'], '.', true);
        }

        // Get the user data by identifier
        $userData = $userGateway->selectBy([$identifier => $identifierValue], [
            'gibbonPersonID',
            'username',
            'image_240',
        ])->fetch();
        if (empty($identifierValue) || empty($userData)) {
            $partialFail = true;
            continue;
        }

        // Optionally overwrite exiting files or skip them
        if (!empty($userData) && $overwrite == 'Y') {
            unlink($absolutePath.'/'.$userData['image_240']);
        } elseif (!empty($userData) && is_file($absolutePath.'/'.$userData['image_240']) && $overwrite == 'N') {
            unlink($file['absolutePath']);
            continue;
        }

        // Rename the file to match the identifier for this user, then resize & crop
        $renameFilename = $userData['username'].'.'.$file['extension'];
        $renameFilePath = str_replace($file['filename'], $renameFilename, $file['absolutePath']);

        $file['absolutePath'] = $fileUploader->resizeImage($file['absolutePath'], $renameFilePath, 480, 100, $zoom, $focalX, $focalY);
        $file['relativePath'] = str_replace($file['filename'], $userData['username'].'.'.$file['extension'], $file['relativePath']);

        // Update the files for this user
        $updated = $userGateway->update($userData['gibbonPersonID'], [
            'image_240' => $file['relativePath'],
        ]);
        if ($updated) {
            $count++;
        }
    }

    unlink($absolutePath.'/'.$file);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}&imported={$count}");
}
