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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Domain\Library\LibraryShelfGateway;
use Gibbon\Domain\Library\LibraryShelfItemGateway;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Browse The Library'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_browse.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Get display settings
    $settingGateway = $container->get(SettingGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    //Get current filter values
    $name = trim($_REQUEST['name'] ?? '');
    $producer = trim($_REQUEST['producer'] ?? '');
    $type = trim($_REQUEST['type'] ?? '');
    $collection = trim($_REQUEST['collection'] ?? '');
    $location = trim($_REQUEST['location'] ?? '');
    $locationToggle = trim($_REQUEST['locationToggle'] ?? '');
    $everything = trim($_REQUEST['everything'] ?? '');
    $readerAge = trim($_REQUEST['readerAge'] ?? 0);

    $gibbonLibraryItemID = trim($_GET['gibbonLibraryItemID'] ?? '');

    // Build the type/collection arrays
    $sql = "SELECT gibbonLibraryTypeID as value, name, fields FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $result = $pdo->select($sql);

    $typeList = ($result->rowCount() > 0) ? $result->fetchAll() : array();
    $collections = $collectionsChained = array();
    $types = array_reduce($typeList, function ($group, $item) use (&$collections, &$collectionsChained) {
        $group[$item['value']] = __($item['name']);
        foreach (json_decode($item['fields'], true) as $field) {
            if ($field['name'] == 'Collection' and $field['type'] == 'Select') {
                foreach (explode(',', $field['options']) as $collectionItem) {
                    $collectionItem = trim($collectionItem);
                    $collections[$collectionItem] = __($collectionItem);
                    $collectionsChained[$collectionItem] = $item['value'];
                }
            }
        }
        return $group;
    }, array());


    $form = Form::create('searchForm', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('fullWidth blank border-transparent mb-6');
    $form->addHiddenValue('q', '/modules/Library/library_browse.php');

    $row = $form->addRow()->addLabel('Browse the Library', __('Browse the Library'))->addClass('text-2xl pb-2');

    $row = $form->addRow()->addClass('grid sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-4');
        $row->addTextField('everything')->setClass('fullWidth sm:col-span-2 md:col-span-4 lg:col-span-6')->setValue($everything)->placeholder('Search for a Book!');
        $row->addSearchSubmit($session, __('Clear Search'))->addClass('sm:col-start-3 md:col-start-5 lg:col-start-7');

    $row = $form->addRow();
        $row->setClass('advancedOptions hidden grid grid-cols-7 gap-4');

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('name', __('Title'));
        $col->setClass('');
        $col->addTextField('name')->setClass('fullWidth')->setValue($name);

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('producer', __('Author/Producer'));
        $col->addTextField('producer')->setClass('fullWidth')->setValue($producer);

    $form->toggleVisibilityByClass('allLocations')->onCheckbox('locationToggle')->when('on');

    $col = $row->addColumn()->setClass('allLocations quarterWidth');
        $col->addLabel('location', __('Location'));
        $col->addSelectSpace('location')->setClass('fullWidth')->setValue($location)->placeHolder()->selected($location);

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('type', __('Type'));
        $col->addSelect('type')
        ->fromArray($types)
        ->setClass('fullWidth')
        ->selected($type)
        ->placeholder();

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addLabel('collection', __('Collection'));
        $col->addSelect('collection')
        ->fromArray($collections)
        ->chainedTo('type', $collectionsChained)
        ->setClass('fullWidth')
        ->selected($collection)
        ->placeholder();
        
    $col = $row->addColumn()->setClass('fullWidth');
        $col->addLabel('readerAge', __('Readers Age'));
        $ageArray=range(2,21);
        $col->addSelect('readerAge')->fromArray($ageArray)->setClass('fullWidth')->selected($readerAge)->placeholder();

    $col = $row->addColumn()->setClass('quarterWidth');
        $col->addCheckBox('locationToggle')->description('Include Books Outside of Library?')->checked(($locationToggle == 'on'))->setValue('on');



    $row = $form->addRow();
        $row->addAdvancedOptionsToggle()->addClass('pt-2');

    echo $form->getOutput();

    if(empty($everything) && empty($collection) && empty($producer) && empty($name) && empty($location) && empty($readerAge)){
        // Display a collection of books on visual library shelves
        $libraryShelves = [];
        $shelfNames = [];

        $shelfGateway = $container->get(LibraryShelfGateway::class);
        $itemGateway = $container->get(LibraryShelfItemGateway::class);

        // Add a default shelf with Top 20
        $topItems = $itemGateway->selectDefaultShelfTopBorrowed()->fetchAll();
        if (!empty($topItems)) {
            $libraryShelves['top'] = $topItems;
            $shelfNames['top'] = __('Monthly Top 20');
        }

        // Add a default shelf with New Titles
        $newItems = $itemGateway->selectDefaultShelfNewItems()->fetchAll();
        if (!empty($newItems)) {
            $libraryShelves['new'] = $newItems;
            $shelfNames['new'] = __('New Titles');
        }

        // Add all other shelves
        $criteria = $shelfGateway->newQueryCriteria()
            ->sortBy(['sequenceNumber', 'name'])
            ->filterBy('active', 'Y')
            ->fromPOST();

        $activeShelves = $shelfGateway->queryLibraryShelves($criteria)->toArray();
        $criteria = $itemGateway->newQueryCriteria()
            ->sortBy('name')
            ->pageSize(30)
            ->fromPOST();

        foreach($activeShelves as $shelf) {
            if($shelf['type'] == 'Automatic') {
                $itemGateway->updateShelfContents($shelf['gibbonLibraryShelfID'], $shelf['field'], $shelf['fieldValue']);
            }
            $libraryShelves[$shelf['gibbonLibraryShelfID']] = $itemGateway->queryItemsByShelf($shelf['gibbonLibraryShelfID'], $criteria)->toArray();
            $shelfNames[$shelf['gibbonLibraryShelfID']] = $shelf['name'];

            //Shuffle shelf items on load if necessary
            if($shelf['shuffle'] == 'Y') {
                shuffle($libraryShelves[$shelf['gibbonLibraryShelfID']]);
            }
        }

        echo $page->fetchFromTemplate('libraryShelves.twig.html', [
            'libraryShelves' => $libraryShelves,
            'shelfNames' => $shelfNames,
        ]);
    } else {
        // Otherwise display the search results
        $gateway = $container->get(LibraryGateway::class);

        $sql = "SELECT gibbonLibraryTypeID as groupBy, gibbonLibraryType.* FROM gibbonLibraryType";
        $typeFields = $pdo->select($sql)->fetchGroupedUnique();
        $locationName = ['name' => ''];
        
        if(!empty($location)) {
            $locationSql = "SELECT gibbonSpace.name FROM gibbonSpace WHERE gibbonSpaceID = ".$location;
            $locationName = $pdo->select($locationSql)->fetch();
        }

        

        $criteria = $gateway->newQueryCriteria()
            ->sortBy('id')
            ->filterBy('agecheck',$readerAge)
            ->filterBy('name', $name)
            ->filterBy('producer', $producer)
            ->filterBy('type', $type)
            ->filterBy('collection', $collection)
            ->filterBy('location', ($locationToggle == 'on') ? $locationName['name'] : 'Library')
            ->filterBy('everything', $everything)
            ->pageSize(100)
            ->fromPOST();
        
        $searchItems = $gateway->queryBrowseItems($criteria)->toArray();
        $searchTerms = ['Everything' => $everything, 'Name' => $name, 'Producer' => $producer, 'Collection' => $collection, 'Location' => $locationName['name']];
        
        echo $page->fetchFromTemplate('librarySearch.twig.html', [
            'searchItems' => $searchItems,
            'searchTerms' => $searchTerms,
            ]);
    }

}
