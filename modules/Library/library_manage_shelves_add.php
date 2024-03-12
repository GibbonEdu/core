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

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $shelfGateway =  $container->get(LibraryShelfGateway::class);

    $page->breadcrumbs
        ->add(__('Manage Library Shelves'), 'library_manage_shelves.php')
        ->add(__('Add Shelf'));
    $urlParamKeys = array('shelfName' => '', 'active' => '', 'type' => '', 'gibbonLibraryTypeID' => '', 'field' => '', 'fieldValue' => '', 'addItems' => '', 'shuffle' => '');
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Library/library_manage_shelves_edit.php&gibbonLibraryShelfID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);
    $urlParams = array_intersect_key($_GET, $urlParamKeys);
    $urlParams = array_merge($urlParamKeys, $urlParams);
    $categories = $shelfGateway->selectDisplayableCategories();

    if (empty($viewMode)) {
        $form = Form::create('libraryShelf', $session->get('absoluteURL').'/modules/Library/library_manage_shelves_addProcess.php?'.http_build_query($urlParams));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->addRow()
            ->addHeading('Shelf Details', __('Shelf Details'));

        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
            $row->addLabel('shelfName', __('Shelf Name'));
            $row->addTextField('shelfName')
                ->required();

        $row = $form->addRow();
            $row->addLabel('active', __('Active'));
            $row->addYesNo('active')
                ->required();

        $row = $form->addRow();
            $row->addLabel('shuffle', __('Automatically Shuffle'));
            $row->addYesNo('shuffle')
                ->selected('N')
                ->required();

        $row = $form->addRow();
            $row->addLabel('type', __('Fill Option'));
            $row->addSelect('type')
                ->required()
                ->fromArray([
                    'Automatic' => __('Automatic'),
                    'Manual' => __('Manual')
                ])
                ->placeholder('Please select...')
                ->selected($urlParams['type']);

        $form->toggleVisibilityByClass('automatic')->onSelect('type')->when('Automatic');

        $row = $form->addRow()->addClass('automatic');
            $row->addLabel('gibbonLibraryTypeID', __('Catalog Type'))
                ->description(__('What type of item would you like to fill a list with?'));
            $row->addSelect('gibbonLibraryTypeID')
                ->fromArray($categories['types'])
                ->placeholder('Please select...')
                ->required();

        $form->toggleVisibilityByClass('autoFill')->onSelect('gibbonLibraryTypeID')->whenNot('Please select...');
        
        $row = $form->addRow()->addClass('autoFill');
            $row->addLabel('field', __('Category'));
            $row->addSelect('field')
                ->fromArray(array_keys($categories['categoryChained']))
                ->chainedTo('gibbonLibraryTypeID', $categories['categoryChained'])
                ->placeholder('Please select...')
                ->selected($urlParams['field'])
                ->required();
        
        $form->toggleVisibilityByClass('searchTerm')->onSelect('field')->when('Search Terms');

        $form->toggleVisibilityByClass('autoFillSelect')->onSelect('field')->whenNot('Search Terms');

        $row = $form->addRow()->addClass('autoFillSelect');
            $row->addLabel('fieldValue', __('Sub-Category'));
            $row->addSelect('fieldValue')
                ->fromArray($categories['subCategory'])
                ->chainedTo('field', $categories['subCategoryChained'])
                ->placeholder('Please select...')
                ->selected($urlParams['fieldValue']);

        $row = $form->addRow()->addClass('searchTerm');
            $row->addLabel('fieldValue', __('Search Term'));
            $row->addTextField('fieldValue')
                ->placeholder('Enter Term Name...');

        $form->toggleVisibilityByClass('manual')->onSelect('type')->when('Manual');

        $row = $form->addRow()->addClass('manual');
            $row->addLabel('field', __('Category'));
            $row->addTextField('field')->setValue('Custom')->readOnly()
                ->required();

        $row = $form->addRow()->addClass('manual');
            $row->addLabel('fieldValue', __('Custom Sub-Category'));
            $row->addTextField('fieldValue')->setValue('Custom')->readOnly();

        $row = $form->addRow();
            $row->addLabel('addItems', __('Add More Items'));
            $row->addClass('manual');
            $row->addFinder('addItems')
                ->fromAjax($session->get('absoluteURL').'/modules/Library/library_searchAjax.php')
                ->setParameter('resultsLimit', 10)
                ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 ml-2 bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.imageLocation + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.producer + "</span></div></li>"; }');        
        
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
?>