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

use Gibbon\Services\Format;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\FileUploader;
use Gibbon\Domain\System\FileGateway;
use Gibbon\Domain\User\UserGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/uploadedFiles_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    if (empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $userGateway = $container->get(UserGateway::class);
    $person = $userGateway->getByID($gibbonPersonID, ['title', 'preferredName', 'surname']);

    if (empty($person)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $personName = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true);

    $page->breadcrumbs
        ->add(__('View Uploaded Files'), 'uploadedFiles_view.php')
        ->add($personName);

    $fileGateway = $container->get(FileGateway::class);
    $fileUploader = $container->get(FileUploader::class);
    $imageTypes = array_map(function ($extension) {
        return ltrim(strtolower(trim((string) $extension)), '.');
    }, $fileUploader->getFileExtensions('Graphics/Design'));

    // CRITERIA
    $criteria = $fileGateway->newQueryCriteria()
        ->pageSize(50)
        ->sortBy('uploadedAt', 'DESC')
        ->fromPOST();

    $files = $fileGateway->queryUploadedFilesByPerson($criteria, $gibbonPersonID);

    // BULK ACTION FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/System Admin/uploadedFiles_manageProcessBulk.php');
    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

    $bulkActions = ['Delete' => __('Delete')];
    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('uploadedFilesByUser', $criteria)->withData($files);
    $table->setTitle(sprintf(__('Files Uploaded by %1$s'), $personName));

    $table->addMetaData('bulkActions', $col);

    $table->modifyRows(function ($file, $row) {
        if ($file['isUsed'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addColumn('preview', __('Preview'))
        ->width('28%')
        ->format(function ($file) use ($imageTypes) {
            $extension = ltrim(strtolower(trim((string) ($file['fileExtension'] ?? ''))), '.');

            if (empty($file['filePath'])) {
                return '';
            }

            if (!empty($extension) && !in_array($extension, $imageTypes, true)) {
                return Format::link('./'.$file['filePath'], __('File'), ['target' => '_blank', 'rel' => 'noopener noreferrer', 'style' => 'font-weight: bold']);
            }

            return Format::link('./'.$file['filePath'], Format::photo($file['filePath'], 'md'), ['target' => '_blank', 'rel' => 'noopener noreferrer']);
        });

    $table->addColumn('fileExtension', __('Type'));

    $table->addColumn('fileSize', __('Size'))
        ->format(function ($file) {
            return Format::fileSize($file['fileSize']);
        });

    $table->addColumn('uploadedAt', __('Upload Date'))
        ->format(Format::using('dateTime', 'uploadedAt'));

    $table->addColumn('isUsed', __('Status'))
        ->format(function ($file) {
            return ($file['isUsed'] == 'N')
                ? Format::tag(__('Unused'), 'dull')
                : Format::tag(__('In Use'), 'success');
        });

    $table->addCheckboxColumn('gibbonFileID')
        ->format(function ($file) {
            return ($file['isUsed'] == 'Y') ? '&nbsp;' : '';
        });

    echo $form->getOutput();
}
