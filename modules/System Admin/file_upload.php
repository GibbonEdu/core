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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/import_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $step = $_REQUEST['step'] ?? 1;
    $steps = [
        1 => __('Select ZIP File'),
        2 => __('Check & Confirm'),
        3 => __('Upload Complete'),
    ];

    $page->breadcrumbs
        ->add(__('Upload Photos & Files'), 'file_upload.php')
        ->add(__('Step {number}', ['number' => $step]));

    $page->return->addReturns(['success1' => __('Import successful. {count} records were imported.', ['count' => '<b>'.($_GET['imported'] ?? '0').'</b>'])]);

    $form = Form::create('fileUpload', $gibbon->session->get('absoluteURL').'/modules/System Admin/file_uploadPreview.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Step 1 - Select ZIP File'));
    $form->setDescription(__('This page allows you to bulk import files such as user photos, personal documents, and custom field files. The uploaded file needs to be in the form of a ZIP file containing files named with a unique identifier. See options below to configure how file names are handled.'));
    $form->setMultiPartForm($steps, 1);
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $form->addRow()->addHeading('File Import', __('File Import'));

    $row = $form->addRow();
        $row->addLabel('file', __('ZIP File'));
        $row->addFileUpload('file')->required()->accepts(['.zip']);

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

    $identifiers = [
        'username'  => __('Username'),
        'studentID' => __('Student ID'),
    ];

    $row = $form->addRow();
        $row->addLabel('identifier', __('Unique Identifier'));
        $row->addSelect('identifier')->fromArray($identifiers)->required();
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
