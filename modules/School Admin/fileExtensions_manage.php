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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\FileExtensionGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/fileExtensions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage File Extensions'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $fileExtensionGateway = $container->get(FileExtensionGateway::class);

    // QUERY
    $criteria = $fileExtensionGateway->newQueryCriteria(true)
        ->sortBy('extension')
        ->fromPOST();

    $fileExtensions = $fileExtensionGateway->queryFileExtensions($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('fileExtensionManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/fileExtensions_manage_add.php')
        ->displayLabel();

    $table->addColumn('extension', __('Extension'));
    $table->addColumn('name', __('Name'))->translatable();
    $table->addColumn('type', __('Type'))->translatable();
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonFileExtensionID')
        ->format(function ($fileExtension, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/School Admin/fileExtensions_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/School Admin/fileExtensions_manage_delete.php');
        });

    echo $table->render($fileExtensions);
}
