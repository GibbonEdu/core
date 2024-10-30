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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\System\CustomFieldGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/file_upload.php&step=3';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/file_upload.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $type = $_POST['type'] ?? '';
    $identifier = $_POST['identifier'] ?? '';
    $zipFile = $_POST['file'] ?? '';
    $fileSeparator = $_POST['fileSeparator'] ?? '';
    $fileSection = $_POST['fileSection'] ?? '';
    $gibbonPersonalDocumentTypeID = $_POST['gibbonPersonalDocumentTypeID'] ?? '';
    $gibbonCustomFieldID = $_POST['gibbonCustomFieldID'] ?? '';
    $overwrite = $_POST['overwrite'] ?? 'N';
    $deleteFiles = $_POST['deleteFiles'] ?? 'N';
    $zoom = $_POST['zoom'] ?? '100';
    $focalX = $_POST['focalX'] ?? '50';
    $focalY = $_POST['focalY'] ?? '50';

    if (empty($identifier) || empty($type) || empty($zipFile)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if (($type == 'customFields' && empty($gibbonCustomFieldID)) || ($type == 'personalDocuments' && empty($gibbonPersonalDocumentTypeID))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($identifier != 'username' && $identifier != 'studentID') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $absolutePath = $session->get('absolutePath');
    if (!is_file($absolutePath.'/'.$zipFile)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $userGateway = $container->get(UserGateway::class);
    $customFieldGateway = $container->get(CustomFieldGateway::class);
    $personalDocumentGateway = $container->get(PersonalDocumentGateway::class);
    
    $fileUploader = $container->get(FileUploader::class);
    $files = $fileUploader->uploadFromZIP($absolutePath.'/'.$zipFile);

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

        if (empty($identifierValue) || empty($userData) || empty($file['relativePath'])) {
            $partialFail = true;
            continue;
        }

        // Check if there is an existing value for this upload
        if ($type == 'customFields') {
            $fields = $customFieldGateway->getCustomFieldDataByUser($gibbonCustomFieldID, $userData['gibbonPersonID']);
            $existingFile = $fields[$gibbonCustomFieldID] ?? '';
        } elseif ($type == 'personalDocuments') {
            $document = $personalDocumentGateway->getPersonalDocumentDataByUser($gibbonPersonalDocumentTypeID, $userData['gibbonPersonID']);;
            $existingFile = $document['filePath'] ?? '';
        } else {
            $existingFile = $userData['image_240'];
        }

        // Optionally overwrite and delete exiting files
        if (!empty($existingFile) && $overwrite == 'Y' && $deleteFiles == 'Y') {
            unlink($absolutePath.'/'.$existingFile);
        }
        
        // Skip uploading files if the file exists and overwrite is not on
        if (!empty($existingFile) && is_file($absolutePath.'/'.$existingFile) && $overwrite == 'N') {
            unlink($file['absolutePath']);
            continue;
        }

        // Update the files for this user
        if ($type == 'customFields') {
            $fields[$gibbonCustomFieldID] = $file['relativePath'];
            $updated = $customFieldGateway->updateCustomFieldDataByUser($gibbonCustomFieldID, $userData['gibbonPersonID'], $fields);

        } elseif ($type == 'personalDocuments') {
            $documentData = $personalDocumentGateway->getPersonalDocumentDataByUser($gibbonPersonalDocumentTypeID, $userData['gibbonPersonID']);
            $updated = $personalDocumentGateway->update($documentData['gibbonPersonalDocumentID'], [
                'filePath'              => $file['relativePath'],
                'gibbonPersonIDUpdater' => $session->get('gibbonPersonID'),
                'timestamp'             => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Rename the file to match the identifier for this user, then resize & crop, and upload
            $renameFilename = $userData['username'].'.'.$file['extension'];
            $renameFilePath = str_replace($file['filename'], $renameFilename, $file['absolutePath']);

            $file['absolutePath'] = $fileUploader->resizeImage($file['absolutePath'], $renameFilePath, 480, 100, $zoom, $focalX, $focalY);
            $file['relativePath'] = str_replace($file['filename'], $renameFilename, $file['relativePath']);
            
            $updated = $userGateway->update($userData['gibbonPersonID'], [
                'image_240' => $file['relativePath'],
            ]);
        }

        if ($updated) {
            $count++;
        }
    }

    unlink($absolutePath.'/'.$zipFile);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}&imported={$count}");
}
