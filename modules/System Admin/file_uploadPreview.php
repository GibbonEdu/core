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
use Gibbon\Forms\Form;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\User\PersonalDocumentTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/file_upload.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $step = $_REQUEST['step'] ?? 1;
    $steps = [
        1 => __('Select File'),
        2 => __('Confirm Data'),
        3 => __('Upload Complete'),
    ];

    $page->breadcrumbs
        ->add(__('Upload Photos & Files'), 'file_upload.php')
        ->add(__('Step {number}', ['number' => $step]));

    $type = $_POST['type'] ?? '';
    $identifier = $_POST['identifier'] ?? '';
    $tempFile = $_FILES['file'] ?? '';
    $fileSeparator = $_POST['fileSeparator'] ?? '';
    $fileSection = $_POST['fileSection'] ?? '';
    $gibbonPersonalDocumentTypeID = $_POST['gibbonPersonalDocumentTypeID'] ?? '';
    $gibbonCustomFieldID = $_POST['gibbonCustomFieldID'] ?? '';

    if (empty($identifier) || empty($type) || empty($tempFile)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    if ($identifier != 'username' && $identifier != 'studentID') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Open the zip archive
    $zip = new ZipArchive();
    if ($zip->open($tempFile['tmp_name']) === false) {
        $page->addError(__('The import file could not be decompressed.'));
        return;
    }

    $tempDirectoryPath = $session->get('absolutePath').'/uploads/temp';
    if (!is_dir($tempDirectoryPath)) {
        mkdir($tempDirectoryPath, 0755);
    }

    $userGateway = $container->get(UserGateway::class);
    $customFieldGateway = $container->get(CustomFieldGateway::class);
    $personalDocumentGateway = $container->get(PersonalDocumentGateway::class);
    $personalDocumentTypeGateway = $container->get(PersonalDocumentTypeGateway::class);

    $fileUploader = $container->get(FileUploader::class);
    $allowedExtensions = $fileUploader->getFileExtensions();

    // Peek into the zip file and check the contents
    $files = $existingFiles = [];
    $validFiles = 0;

    for ($i = 0; $i < $zip->numFiles; ++$i) {
        if (substr($zip->getNameIndex($i), 0, 8) == '__MACOSX') continue;
        
        $filename = basename($zip->getNameIndex($i));
        $extension = mb_substr(mb_strrchr(strtolower($filename), '.'), 1);
        
        if (!in_array($extension, $allowedExtensions)) continue;

        // Optionally split the filenames by a separator character
        if (!empty($fileSeparator) && !empty($fileSection)) {
            $fileParts = explode($fileSeparator, mb_strrchr($filename, '.', true));
            $identifierValue = $fileParts[$fileSection-1] ?? '';
        } else {
            $identifierValue = mb_strrchr($filename, '.', true);
        }

        // Ensure the file info matches an existing user
        $userData = $userGateway->selectBy([$identifier => $identifierValue], [
            'gibbonPersonID',
            'username',
            'image_240',
            'title',
            'preferredName',
            'surname',
            'status',
        ])->fetch();

        if (!empty($identifierValue) && !empty($userData)) {
            $userData['filename'] = $filename;
            
            if ($type == 'customFields') {
                $fields = $customFieldGateway->getCustomFieldDataByUser($gibbonCustomFieldID, $userData['gibbonPersonID']);
                $userData['exists'] = !empty($fields[$gibbonCustomFieldID]);
            } elseif ($type == 'personalDocuments') {
                $document = $personalDocumentGateway->getPersonalDocumentDataByUser($gibbonPersonalDocumentTypeID, $userData['gibbonPersonID']);
                $userData['exists'] = !empty($document['filePath']);
            } else {
                $userData['exists'] = !empty($userData['image_240']);
            }

            if ($type == 'userPhotos' && ($extension != 'jpg' && $extension != 'png')) {
                $userData['statusText'] = __('Invalid File Type');
            } else {
                $validFiles++;
            }
            
            if ($userData['exists']) {
                $existingFiles[] = $filename;
            }
        } else {
            $userData = [
                'filename' => $filename,
                'status' => 'Left',
            ];
        }

        $files[] = $userData;
    }

    // Upload the temporary file to the archive
    if ($validFiles > 0 && !empty($tempFile['tmp_name'])) {
        $file = $fileUploader->upload($tempFile['name'], $tempFile['tmp_name'], '/uploads/temp');
    } elseif (empty($tempFile['tmp_name'])) {
        $page->addError(__('Failed to write file to disk.'));
        return;
    }

    $form = Form::create('fileUpload', $session->get('absoluteURL').'/modules/System Admin/file_uploadProcess.php');
    $form->setTitle(__('Step 2 - Data Check & Confirm'));
    $form->setMultiPartForm($steps, $step);
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('type', $type);
    $form->addHiddenValue('gibbonPersonalDocumentTypeID', $gibbonPersonalDocumentTypeID);
    $form->addHiddenValue('gibbonCustomFieldID', $gibbonCustomFieldID);
    $form->addHiddenValue('identifier', $identifier);
    $form->addHiddenValue('fileSeparator', $fileSeparator);
    $form->addHiddenValue('fileSection', $fileSection);
    $form->addHiddenValue('file', $file ?? '');
    $form->addHiddenValue('zoom', $_POST['zoom'] ?? '');
    $form->addHiddenValue('focalX', $_POST['focalX'] ?? '');
    $form->addHiddenValue('focalY', $_POST['focalY'] ?? '');

    $types = [
        'userPhotos'        => __('User Photos'),
        'personalDocuments' => __('Personal Documents'),
        'customFields'      => __('Custom Fields'),
    ];
    $row = $form->addRow();
        $row->addLabel('typeLabel', __('Type'));
        $row->addTextField('typeLabel')->readonly()->setValue($types[$type] ?? '');

    if ($type == 'personalDocuments') {
        $personalDocument = $personalDocumentTypeGateway->getByID($gibbonPersonalDocumentTypeID);
        $row = $form->addRow();
        $row->addLabel('personalDocuments', __('Personal Document'));
        $row->addTextField('personalDocuments')->readonly()->setValue($personalDocument['name'] ?? $gibbonPersonalDocumentTypeID);
    } elseif ($type == 'customFields') {
        $customField = $customFieldGateway->getByID($gibbonCustomFieldID);
        $row = $form->addRow();
        $row->addLabel('customFields', __('Custom Fields'));
        $row->addTextField('customFields')->readonly()->setValue($customField['name'] ?? $gibbonCustomFieldID);
    }

    if (!empty($existingFiles)) {
        $row = $form->addRow();
            $row->addLabel('overwrite', __('Overwrite'))->description(__('Should uploaded files overwrite any existing files?'));
            $row->addYesNo('overwrite')->selected('Y');

            $row = $form->addRow();
            $row->addLabel('deleteFiles', __('Delete'))->description(__('Should original files be deleted from the server when overwriting files?'));
            $row->addYesNo('deleteFiles')->selected('N');
    }

    // DATA TABLE
    if ($validFiles == 0) {
        $form->addRow()->addContent(Format::alert(__('Import cannot proceed. The uploaded ZIP archive did not contain any valid {filetype} files.', ['filetype' => $type])));
    }

        $table = $form->addRow()->addDataTable('fileUploadPreview')->withData(new DataSet($files));

        $table->modifyRows($userGateway->getSharedUserRowHighlighter());

        $table->addColumn('user', __('User'))
            ->width('25%')
            ->format(function ($person) {
                if (empty($person['surname']) || empty($person['username'])) {
                    return __('No match found');
                }
                return Format::name('', $person['preferredName'], $person['surname'], 'Student', true)
                    .'<br/>'.Format::small(Format::userStatusInfo($person));
            });
        $table->addColumn('username', __('Username'));
        if ($identifier == 'studentID') {
            $table->addColumn('studentID', __('Student ID'));
        }
        $table->addColumn('filename', __('File Name'));
        $table->addColumn('status', __('Status'))
            ->format(function ($values)  {
                if (empty($values['surname']) || !empty($values['statusText'])) {
                    return !empty($values['statusText']) 
                        ? Format::tag($values['statusText'], 'error')
                        : Format::tag(__('Unknown'), 'dull');
                }

                return $values['exists']
                    ? Format::tag(__('Exists'), 'warning')
                    : Format::tag(__('New'), 'success');
            });

    if ($validFiles > 0) {
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();
    }

    echo $form->getOutput();
}
