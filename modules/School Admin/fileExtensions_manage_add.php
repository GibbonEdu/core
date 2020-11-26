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
use Gibbon\FileUploader;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/fileExtensions_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage File Extensions'), 'fileExtensions_manage.php')
        ->add(__('Add File Extension'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/fileExtensions_manage_edit.php&gibbonFileExtensionID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('fileExtensions', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/fileExtensions_manage_addProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $illegalTypes = FileUploader::getIllegalFileExtensions();

    $categories = array(
        'Document'        => __('Document'),
        'Spreadsheet'     => __('Spreadsheet'),
        'Presentation'    => __('Presentation'),
        'Graphics/Design' => __('Graphics/Design'),
        'Video'           => __('Video'),
        'Audio'           => __('Audio'),
        'Other'           => __('Other'),
    );

    $row = $form->addRow();
        $row->addLabel('extension', __('Extension'))->description(__('Must be unique.'));
        $ext = $row->addTextField('extension')->required()->maxLength(7);

        $within = implode(',', array_map(function ($str) { return sprintf("'%s'", $str); }, $illegalTypes));
        $ext->addValidation('Validate.Exclusion', 'within: ['.$within.'], failureMessage: "'.__('Illegal file type!').'", partialMatch: true, caseSensitive: false');

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxLength(50);

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($categories)->required()->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
