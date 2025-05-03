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
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_uploadPreview.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Upload Reports'), 'archive_manage_upload.php')
        ->add(__('Step {number}', ['number' => 2]));

    $gibbonReportArchiveID = $_POST['gibbonReportArchiveID'] ?? '';
    $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
    $reportIdentifier = $_POST['reportIdentifier'] ?? '';
    $reportDate = $_POST['reportDate'] ?? '';
    $tempFile = $_FILES['file'] ?? '';
    $fileSeparator = $_POST['fileSeparator'] ?? '';
    $fileSection = $_POST['fileSection'] ?? '';

    if (empty($gibbonReportArchiveID) || empty($gibbonSchoolYearID) || empty($reportIdentifier) || empty($tempFile)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);
    $archive = $reportArchiveGateway->getByID($gibbonReportArchiveID);
    if (empty($archive)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Open the zip archive
    $zip = new ZipArchive();
    if ($zip->open($tempFile['tmp_name']) === false) {
        $page->addError(__('The import file could not be decompressed.'));
        return;
    }

    // Grab any existing reports so we can check if they will be overwritten
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    $existingReports = $reportArchiveEntryGateway->selectArchiveEntriesByReportIdentifier($gibbonSchoolYearID, $reportIdentifier)->fetchGroupedUnique();

    // Peek into the zip file and check the contents
    $studentGateway = $container->get(StudentGateway::class);
    $reports = [];
    for ($i = 0; $i < $zip->numFiles; ++$i) {
        if (substr($zip->getNameIndex($i), 0, 8) == '__MACOSX') continue;
        
        $filename = $zip->getNameIndex($i);
        $extension = mb_substr(mb_strrchr(strtolower($filename), '.'), 1);
        
        if ($extension != 'pdf') continue;

        // Optionally split the filenames by a separator character
        if (!empty($fileSeparator) && !empty($fileSection)) {
            $fileParts = explode($fileSeparator, mb_strstr($filename, '.', true));
            $username = $fileParts[$fileSection-1] ?? '';
        } else {
            $username = mb_strstr($filename, '.', true);
        }

        // Ensure the file info matches a student enrolment in the selected year
        $studentEnrolment = $studentGateway->getStudentByUsername($gibbonSchoolYearID, $username);
        if (!empty($username) && !empty($studentEnrolment)) {
            $studentEnrolment['report'] = $existingReports[$studentEnrolment['gibbonPersonID']] ?? [];
            $studentEnrolment['filename'] = $filename;
            $reports[] = $studentEnrolment;
        }
    }

    // Cancel out if there's nothing to do
    if (empty($reports)) {
        $page->addError(__('Import cannot proceed. The uploaded ZIP archive did not contain any valid {filetype} files.', ['filetype' => 'pdf']));
        return;
    }

    // Upload the temporary file to the archive
    if (!empty($tempFile['tmp_name'])) {
        $fileUploader = new FileUploader($pdo, $session);
        $file = $fileUploader->upload($tempFile['name'], $tempFile['tmp_name'], $archive['path'].'/temp');
    } else {
        $page->addError(__('Failed to write file to disk.'));
        return;
    }

    $form = Form::create('archiveImport', $session->get('absoluteURL').'/modules/Reports/archive_manage_uploadProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Step 2 - Data Check & Confirm'));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportArchiveID', $gibbonReportArchiveID);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('reportIdentifier', $reportIdentifier);
    $form->addHiddenValue('reportDate', Format::dateConvert($reportDate));
    $form->addHiddenValue('fileSeparator', $fileSeparator);
    $form->addHiddenValue('fileSection', $fileSection);
    $form->addHiddenValue('file', $file);

    $form->addRow()->addHeading('Report Info', __('Report Info'));

    $row = $form->addRow();
        $row->addLabel('archiveName', __('Archive'));
        $row->addTextField('archiveName')->readonly()->setValue($archive['name']);

    $schoolYear = $container->get(SchoolYearGateway::class)->getSchoolYearByID($gibbonSchoolYearID);
    $row = $form->addRow();
        $row->addLabel('schoolYearName', __('School Year'));
        $row->addTextField('schoolYearName')->readonly()->setValue($schoolYear['name']);

    $row = $form->addRow();
        $row->addLabel('reportName', __('Report Name'));
        $row->addTextField('reportName')->readonly()->setValue($reportIdentifier);

    $row = $form->addRow();
        $row->addLabel('reportDateName', __('Report Date'));
        $row->addTextField('reportDateName')->readonly()->setValue($reportDate);

    if (!empty($existingReports)) {
        $row = $form->addRow();
            $row->addLabel('overwrite', __('Overwrite'))->description(__('Should uploaded files overwrite any existing files?'));
            $row->addYesNo('overwrite')->selected('N');
    }

    // DATA TABLE
    $table = $form->addRow()->addDataTable('reportFiles')->withData(new DataSet($reports));

    $table->modifyRows($studentGateway->getSharedUserRowHighlighter());

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->width('25%')
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true)
                   .'<br/>'.Format::small(Format::userStatusInfo($person));
        });
    $table->addColumn('formGroup', __('Form Group'));
    $table->addColumn('username', __('Username'));
    $table->addColumn('filename', __('File Name'));
    $table->addColumn('status', __('Status'))
        ->format(function ($values) {
            return !empty($values['report'])
                ? '<span class="tag warning">'.__('Exists').'</span>'
                : '<span class="tag success">'.__('New').'</span>';
        });

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
