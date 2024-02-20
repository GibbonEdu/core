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
        $row->addLabel('type', __('Fill Option'));
        $row->addSelect('type')
            ->required()
            ->fromArray([
                'automatic' => __('Automatic'),
                'manual' => __('Manual')
            ])
            ->selected($values['type']);

    $form->toggleVisibilityByClass('automatic')->onSelect('type')->when('automatic');

    $row = $form->addRow()->addClass('automatic');
        $row->addLabel('gibbonLibraryTypeID', __('Catalog Type'))
            ->description(__('What type of item would you like to fill a list with?'));
        $row->addSelect('gibbonLibraryTypeID')
            ->fromArray($categories['types'])
            ->placeholder('Please select...')
            ->selected($values['field']);

    $form->toggleVisibilityByClass('autoFill')->onSelect('gibbonLibraryTypeID')->whenNot('Please select...');
    
    $row = $form->addRow()->addClass('autoFill');
        $row->addLabel('field', __('Category'));
        $row->addSelect('field')
            ->fromArray(array_keys($categories['categoryChained']))
            ->chainedTo('gibbonLibraryTypeID', $categories['categoryChained'])
            ->placeholder('Please select...')
            ->selected($values['field'])
            ->required();
    
    $row = $form->addRow()->addClass('autoFill');
        $row->addLabel('fieldKey', __('Possible Shelves'));
        $row->addSelect('fieldKey')
            ->fromArray($categories['subCategory'])
            ->chainedTo('field', $categories['subCategoryChained'])
            ->placeholder('Please select...')
            ->selected($values['fieldKey'])
            ->required();

    $form->toggleVisibilityByClass('manual')->onSelect('type')->when('manual');

    $row = $form->addRow()->addClass('manual');
        $row->addLabel('field', __('Category'));
        $row->addTextField('field')->setValue('Custom')->readOnly()
            ->required();

    $row = $form->addRow()->addClass('manual');
        $row->addLabel('fieldKey', __('Custom Tag'));
        $row->addTextField('fieldKey')
            ->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}