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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_upload.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Upload Reports'), 'archive_manage_upload.php')
        ->add(__('Step {number}', ['number' => 1]));

    $page->return->addReturns(['success1' => __('Import successful. {count} records were imported.', ['count' => '<b>'.($_GET['imported'] ?? '0').'</b>'])]);

    $form = Form::create('archiveImport', $session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_manage_uploadPreview.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Step 1 - Select ZIP File'));
    $form->setDescription(__('This page allows you to bulk import reports, in the form of a ZIP file containing PDFs named with individual usernames.'));
    
    $form->addHiddenValue('address', $session->get('address'));

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

    $form->addRow()->addHeading('Report Info', __('Report Info'));

    $archives = $container->get(ReportArchiveGateway::class)->selectWriteableArchives()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonReportArchiveID', __('Archive'))->description(__('The selected archive determines where files are saved and who can access them.'));
        $row->addSelect('gibbonReportArchiveID')->fromArray($archives)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearID', __('School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearID')->required()->selected($session->get('gibbonSchoolYearID'));

    $row = $form->addRow();
        $row->addLabel('reportIdentifier', __('Report Name'));
        $row->addTextField('reportIdentifier')->required()->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('reportDate', __('Report Date'));
        $row->addDate('reportDate')->required();

    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
