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

use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Forms\Form;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Archives'), 'archive_manage.php')
        ->add(__('Upload'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('archiveImport', $gibbon->session->get('absoluteURL').'/modules/Reports/archive_manage_uploadProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Select ZIP File'));
    $form->setDescription(__('This page allows you to bulk import reports, in the form of a ZIP file containing PDFs named with individual usernames.'));
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('file', __('ZIP File'))->description(__('See Notes below for specification.'));
        $row->addFileUpload('file')->required();

    $archives = $container->get(ReportArchiveGateway::class)->selectWriteableArchives()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonReportArchiveID', __('Archive'))->description(__('The selected archive determines where files are saved and who can access them.'));
        $row->addSelect('gibbonReportArchiveID')->fromArray($archives)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearID', __('School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearID')->required()->selected($gibbon->session->get('gibbonSchoolYearID'));

    $row = $form->addRow();
        $row->addLabel('reportIdentifier', __('Report Name'));
        $row->addTextField('reportIdentifier')->required()->maxLength(255);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
