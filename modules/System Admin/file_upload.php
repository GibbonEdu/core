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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\User\PersonalDocumentTypeGateway;
use Gibbon\Services\Format;

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

    $form = Form::create('fileUpload', '');
    $form->setMultiPartForm($steps, $step);

    if ($step == 1) {
        // STEP 1
        $form->setAction($session->get('absoluteURL').'/index.php?q=/modules/System Admin/file_uploadPreview.php');
        $form->setTitle(__('Step 1 - Select ZIP File'));
        $form->setDescription(__('This page allows you to bulk import files such as user photos, personal documents, and custom field files. The uploaded file needs to be in the form of a ZIP file containing files named with a unique identifier. See options below to configure how file names are handled.'));
        
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('step', 2);

        $form->addRow()->addHeading('File Import', __('File Import'));

        $row = $form->addRow();
            $row->addLabel('file', __('ZIP File'));
            $row->addFileUpload('file')->required()->accepts(['.zip']);

        $identifiers = [
            'username'  => __('Username'),
            'studentID' => __('Student ID'),
        ];

        $row = $form->addRow();
            $row->addLabel('identifier', __('Unique Identifier'))->description(__('The unique value that will be used to determine which files belong to specific users.'));
            $row->addSelect('identifier')->fromArray($identifiers)->required();

        $row = $form->addRow();
            $row->addLabel('useSeparator', __('Filename Separator?'))->description(__('Do the filenames inside the ZIP file use a separator character?'));
            $row->addYesNo('useSeparator')->required()->selected('N');

        $form->toggleVisibilityByClass('useSeparator')->onSelect('useSeparator')->when('Y');

        $row = $form->addRow()->addClass('useSeparator');
            $row->addLabel('fileSeparator', __('Separator Character'));
            $row->addTextField('fileSeparator')->required()->maxLength(1);

        $row = $form->addRow()->addClass('useSeparator');
            $row->addLabel('fileSection', __('Section Number'))->description(__('Once split, which section contains the username? Counting from 1.'));
            $row->addNumber('fileSection')->required()->maxLength(1);

        $form->addRow()->addHeading('Upload Photos & Files', __('Upload Photos & Files'));

        $types = [
            'userPhotos'        => __('User Photos'),
            'personalDocuments' => __('Personal Documents'),
            'customFields'      => __('Custom Fields'),
        ];

        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($types)->placeholder()->required();

        $form->toggleVisibilityByClass('personalDocuments')->onSelect('type')->when('personalDocuments');
        $personalDocumentTypes = $container->get(PersonalDocumentTypeGateway::class)->selectDocumentTypesWithFileUpload()->fetchKeyPair();
        $row = $form->addRow()->addClass('personalDocuments');
            $row->addLabel('gibbonPersonalDocumentTypeID', __('Personal Document'));
            $row->addSelect('gibbonPersonalDocumentTypeID')->fromArray($personalDocumentTypes)->required();

        $form->toggleVisibilityByClass('customFields')->onSelect('type')->when('customFields');
        $customFields = $container->get(CustomFieldGateway::class)->selectCustomFieldsWithFileUpload();
        $row = $form->addRow()->addClass('customFields');
            $row->addLabel('gibbonCustomFieldID', __('Personal Document'));
            $row->addSelect('gibbonCustomFieldID')->fromResults($customFields, 'groupBy')->required();

        $form->toggleVisibilityByClass('userPhotos')->onSelect('type')->when('userPhotos');

        $form->addRow()->addClass('userPhotos')->addContent(Format::alert(__('User photos will automatically be scaled down to a maximum of 360px by 480px and cropped to an aspect ratio of 1.2. You can optionally adjust the zoom and focal point of the cropped images below.'), 'message'));

        $row = $form->addRow()->addClass('userPhotos');
            $row->addLabel('zoom', __('Zoom Rate'));
            $row->addRange('zoom', 100, 150, 1)->setValue(100);

        $row = $form->addRow()->addClass('userPhotos');
            $row->addLabel('focalX', __('Horizontal Focal Point'));
            $row->addRange('focalX', 0, 100, 5)->setValue(50);

        $row = $form->addRow()->addClass('userPhotos');
            $row->addLabel('focalY', __('Vertical Focal Point'));
            $row->addRange('focalY', 0, 100, 5)->setValue(50);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

    } else if ($step == 3) {
        if (!empty($_GET['imported'])) {
            $form->addRow()->addContent(Format::alert(__('Import successful. {count} records were imported.', ['count' => '<b>'.($_GET['imported'] ?? '0').'</b>']), 'success'));
        }
    }

    echo $form->getOutput();
}
