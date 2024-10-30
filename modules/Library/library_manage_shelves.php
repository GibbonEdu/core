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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Library\LibraryShelfGateway;

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Get current filter values
    $name = $_REQUEST['name'] ?? '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Manage Library Shelves'));
        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');
        $form->setTitle(__('Search & Filter'));

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/library_manage_shelves.php");

        $row = $form->addRow();
            $row->addLabel('name', __('Shelf Name'));
            $row->addTextField('name')->setValue($name)->placeholder();

        $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

        echo $form->getOutput();
    }

    $gateway = $container->get(LibraryShelfGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber', 'name'])
        ->filterBy('name', $name)
        ->fromPOST();
    $shelves = $gateway->queryLibraryShelves($criteria);

    $table = DataTable::createPaginated('libraryShelvesManage', $criteria);
    $table->setTitle(__('Manage Shelves'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Library/library_manage_shelves_add.php')
        ->displayLabel();

    $table->addDraggableColumn('gibbonLibraryShelfID', $session->get('absoluteURL').'/modules/Library/library_shelves_editOrderAjax.php');

    $table->addColumn('name', __('Name'));
    $table->addColumn('type', __('Fill-Type'));
    $table->addColumn('field', __('Category'));
    $table->addColumn('fieldValue', __('Sub-Category'));
    $table->addColumn('shuffle', __('Auto-Shuffle'));
        
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonLibraryShelfID')
        ->addParam('name', $name)
        ->format(function ($shelf, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Library/library_manage_shelves_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Library/library_manage_shelves_delete.php');
        });
        

    echo $table->render($shelves);
}
