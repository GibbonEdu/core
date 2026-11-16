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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;
use Gibbon\Domain\System\FileGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/uploadedFiles_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('View Uploaded Files'));

    $return = $_GET['return'] ?? '';

    // Show the confirmation form until the admin has run the status check
    if (empty($return)) {
        $form = Form::create('uploadedFilesSearch', $session->get('absoluteURL').'/modules/System Admin/uploadedFiles_searchProcess.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->setTitle(__('Search Uploaded Files'));
        $form->setDescription(__('Please click the button below to run the updater to search for all uploaded files.'));

        $form->addRow()->addConfirmSubmit('UPDATE');

        echo $form->getOutput();
        return;
    }

    // Status check has been run — show the results table
    $fileGateway = $container->get(FileGateway::class);

    // CRITERIA
    $criteria = $fileGateway->newQueryCriteria()
        ->sortBy('totalSize', 'DESC')
        ->fromPOST();

    $fileOwners = $fileGateway->queryUploadedFiles($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('uploadedFiles', $criteria);
    $table->setTitle(__('Uploaded Files by User'));
    $table->setDescription(__('Staff who have uploaded files via the editor.'));

    $table->addColumn('name', __('Person'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($row) {
            return Format::nameLinked($row['gibbonPersonID'], '', $row['preferredName'], $row['surname'], 'Staff', false, true);
        });

    $table->addColumn('fileCount', __('Files'));

    $table->addColumn('totalSize', __('Total Size'))
        ->format(function ($row) {
            return Format::fileSize($row['totalSize']);
        });

    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->format(function ($values, $actions) {
            $actions->addAction('manage', __('Manage Files'))
                ->setIcon('folder')
                ->setURL('/modules/System Admin/uploadedFiles_manage.php');
        });

    echo $table->render($fileOwners);
}
