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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Library\LibraryShelfGateway;
use Gibbon\Domain\Library\LibraryShelfItemGateway;
use Gibbon\Forms\Prefab\BulkActionForm;

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!

    $page->breadcrumbs
        ->add(__('Manage Library Shelves'), 'library_manage_shelves.php')
        ->add(__('Edit Shelf'));

    $gibbonLibraryShelfID = $_GET['gibbonLibraryShelfID'] ?? '';
    $shelfGateway = $container->get(LibraryShelfGateway::class);
    $itemGateway = $container->get(LibraryShelfItemGateway::class);
    $categories = $shelfGateway->selectDisplayableCategories();

    if (empty($gibbonLibraryShelfID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $shelfGateway->getShelfByID($gibbonLibraryShelfID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('libraryShelf', $session->get('absoluteURL').'/modules/Library/library_manage_shelves_editProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->addRow()->addHeading('Shelf Details', __('Shelf Details'));
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonLibraryShelfID', $gibbonLibraryShelfID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')
            ->placeholder()
            ->required()
            ->setValue($values['name']);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')
            ->required()
            ->setValue($values['active']);

    $row = $form->addRow();
        $row->addLabel('shuffle', __('Automatically Shuffle'));
        $row->addYesNo('shuffle')
            ->required()
            ->setValue($values['shuffle']);

    $row = $form->addRow();
        $row->addLabel('type', __('Fill Option'));
        $row->addTextField('type')->setValue($values['type'])->readOnly();

    $row = $form->addRow();
        $row->addLabel('field', __('Category'));
        $row->addTextField('field')->setValue($values['field'])->readOnly();

    if($values['type'] == 'Automatic') {
        $row = $form->addRow();
        $row->addLabel('fieldValue', __('Sub-Category'));
        $row->addTextField('fieldValue')->setValue($values['fieldValue'])->readOnly()
            ->required();

    } elseif($values['type'] == 'Manual') {
        $row = $form->addRow();
        $row->addLabel('fieldValue', __('Custom Sub-Category'));
        $row->addTextField('fieldValue')->setValue($values['fieldValue'])->readOnly();
    }
    
    $row = $form->addRow();
        $row->addLabel('addItems', __('Add More Items'));
        $row->addFinder('addItems')
            ->fromAjax($session->get('absoluteURL').'/modules/Library/library_searchAjax.php')
            ->setParameter('resultsLimit', 10)
            ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 ml-2 bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.imageLocation + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.producer + "</span></div></li>"; }');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

    // QUERY
    $criteria = $itemGateway->newQueryCriteria(true)
    ->pageSize(10)
    ->sortBy('name')
    ->filterBy('imageType',)
    ->fromPOST();

    $items = $itemGateway->queryItemsByShelf($gibbonLibraryShelfID, $criteria);//->toDataSet();

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Library/library_shelves_editProcessBulk.php');
    $form->addHiddenValue('gibbonLibraryShelfID', $gibbonLibraryShelfID);
    $col = $form->createBulkActionColumn([
        'Delete' => __('Delete'),
    ]);
    $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('items', $criteria)->withData($items);
    $table->setTitle(__('Current Items'));
    $table->addMetaData('bulkActions', $col);
    $table->addColumn('name', __('Name'));
    $table->addColumn('producer', __('Producer'));
    
    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonLibraryShelfID', $gibbonLibraryShelfID)
        ->format(function ($item, $actions) {
            $actions->addAction('delete', __('Delete'))
                        ->addParam('gibbonLibraryShelfItemID', $item['gibbonLibraryShelfItemID'])
                        ->setURL('/modules/Library/library_manage_shelves_edit_items_delete.php');
        });

    $table->addCheckboxColumn('gibbonLibraryShelfItemID');

    echo $form->getOutput();
}