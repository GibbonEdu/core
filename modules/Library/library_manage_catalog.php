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
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Library\LibraryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Get current filter values
    $viewMode = $_REQUEST['format'] ?? '';
    $name = $_REQUEST['name'] ?? '';
    $parentID = $_REQUEST['parentID'] ?? '';
    $gibbonLibraryTypeID = $_REQUEST['gibbonLibraryTypeID'] ?? '';
    $gibbonSpaceID = $_REQUEST['gibbonSpaceID'] ?? '';
    $status = $_REQUEST['status'] ?? '';
    $gibbonPersonIDOwnership = $_REQUEST['gibbonPersonIDOwnership'] ?? '';
    $typeSpecificFields = $_REQUEST['typeSpecificFields'] ?? '';
    $locationDetail = trim($_REQUEST['locationDetail'] ?? '');
    $everything = trim($_REQUEST['everything'] ?? '');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Manage Catalog'));

        $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');
        $form->setTitle(__('Search & Filter'));

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/library_manage_catalog.php");

        $row = $form->addRow();
            $row->addLabel('name', __('ID/Name/Producer'));
            $row->addScanner('name')->setValue($name);

        $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
        $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')
            ->fromQuery($pdo, $sql, array())
            ->selected($gibbonLibraryTypeID)
            ->placeholder();

        $row = $form->addRow()->addClass('advancedOptions hidden');
            $row->addLabel('gibbonSpaceID', __('Location'));
            $row->addSelectSpace('gibbonSpaceID')->selected($gibbonSpaceID)->placeholder();

        $row = $form->addRow()->addClass('advancedOptions hidden');
            $row->addLabel('locationDetail', __('Location Detail'));
            $row->addTextField('locationDetail')->setValue($locationDetail)->placeholder();

        $statuses = array(
            'Available' => __('Available'),
            'On Order' => __('On Order'),
            'Decommissioned' => __('Decommissioned'),
            'In Use' => __('In Use'),
            'Lost' => __('Lost'),
            'On Loan' => __('On Loan'),
            'Repair' => __('Repair'),
            'Reserved' => __('Reserved')
        );
        $row = $form->addRow()->addClass('advancedOptions hidden');
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray($statuses)->selected($status)->placeholder();

        $row = $form->addRow()->addClass('advancedOptions hidden');
            $row->addLabel('gibbonPersonIDOwnership', __('Owner/User'));
            $row->addSelectUsers('gibbonPersonIDOwnership')->selected($gibbonPersonIDOwnership)->placeholder();

        $row = $form->addRow()->addClass('advancedOptions hidden');
        $row->addLabel('typeSpecificFields', __('Type-Specific Fields'))
            ->description(__('For example, a computer\'s MAC address or a book\'s ISBN.'));
        $row->addScanner('typeSpecificFields')
            ->setValue($typeSpecificFields);

        $row = $form->addRow()->addClass(empty($parentID) ? 'advancedOptions hidden' : 'advancedOptions');
            $row->addLabel('parentID', __('Copies Of'));
            $row->addTextField('parentID')->setValue($parentID);

        $col = $form->addRow()->setClass('advancedOptions hidden')->addColumn();
            $col->addLabel('everything', __('All Fields'));
            $col->addTextField('everything')->setClass('fullWidth')->setValue($everything);

        $row = $form->addRow();
        $row->addAdvancedOptionsToggle();
        $row->addSearchSubmit($session, __('Clear Search'));

        echo $form->getOutput();
    }

    $gateway = $container->get(LibraryGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy('id')
        ->filterBy('name', $name)
        ->filterBy('parent', $parentID)
        ->filterBy('type', $gibbonLibraryTypeID)
        ->filterBy('location', $gibbonSpaceID)
        ->filterBy('locationDetail', $locationDetail)
        ->filterBy('status', $status)
        ->filterBy('owner', $gibbonPersonIDOwnership)
        ->filterBy('typeSpecificFields', $typeSpecificFields)
        ->filterBy('everything', $everything)
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();
    $items = $gateway->queryCatalog($criteria, $session->get('gibbonSchoolYearID'));

    $table = ReportTable::createPaginated('items', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Manage Catalog'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Library/library_manage_catalog_add.php')
        ->addParam('gibbonLibraryTypeID', $gibbonLibraryTypeID)
        ->addParam('name', $name)
        ->addParam('gibbonSpaceID', $gibbonSpaceID)
        ->addParam('status', $status)
        ->addParam('gibbonPersonIDOwnership', $gibbonPersonIDOwnership)
        ->addParam('typeSpecificFields', $typeSpecificFields)
        ->displayLabel()
        ->prepend(' | ');

    $table->addColumn('id', __('School ID'))
        ->description(__('Type'))
        ->format(function ($item) {
            return Format::bold(__($item['id']));
        })
        ->formatDetails(function ($item) {
            return Format::small(__($item['itemType']));
        });

    $table->addColumn('name', __('Name'))
        ->description(__('Producer'))
        ->format(function ($item) {
            return Format::bold(__($item['name']));
        })
        ->formatDetails(function ($item) {
            return Format::small(__($item['producer']));
        });
    $table->addColumn('spaceName', __('Location'))
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/>%2$s', $item['spaceName'], Format::small($item['locationDetail']));
        });
    $table->addColumn('ownershipType', __('Ownership'))
        ->description(__('User/Owner'))
        ->format(function ($item) use ($session) {
            if (!empty($item['gibbonLibraryItemIDParent'])) return Format::tag(__('Copy'), 'dull text-xxs');
            if ($item['ownershipType'] == 'School') {
                return sprintf('<b>%1$s</b><br/>', $session->get('organisationNameShort'));
            } elseif ($item['ownershipType'] == 'Individual') {
                return sprintf('<b>%1$s</b><br/>', __('Individual'));
            }
        })
        ->formatDetails(function ($item) {
            return Format::small(Format::name($item['title'], $item['preferredName'], $item['surname'], "Student"));
        });
    $table->addColumn('status', __('Status'))
        ->description(__('Responsible User'))
        ->format(function ($item) {
            return Format::bold(__($item['status']));
        })
        ->formatDetails(function ($item) {
            $responsible = !empty($item['surnameResponsible'])
                ? Format::name($item['titleResponsible'], $item['preferredNameResponsible'], $item['surnameResponsible'], 'Student')
                : '';
            $responsible .= !empty($item['formGroup'])
                ? ' ('.$item['formGroup'].')'
                : '';

            return Format::small($responsible);
        });
    $actions = $table->addActionColumn()
          ->addParam('gibbonLibraryItemID')
          ->addParam('name', $name)
          ->addParam('gibbonLibraryTypeID', $gibbonLibraryTypeID)
          ->addParam('gibbonSpaceID', $gibbonSpaceID)
          ->addParam('status', $status)
          ->addParam('gibbonPersonIDOwnership', $gibbonPersonIDOwnership)
          ->addParam('typeSpecificFields', $typeSpecificFields)
          ->format(function ($item, $actions) {
              $actions->addAction('edit', __('Edit'))
                      ->setURL('/modules/Library/library_manage_catalog_edit.php');
              $actions->addAction('lending', __('Lending'))
                      ->setURL('/modules/Library/library_lending_item.php')
                      ->setIcon('attendance');
              $actions->addAction('delete', __('Delete'))
                      ->setURL('/modules/Library/library_manage_catalog_delete.php');
              if (empty($item['gibbonLibraryItemIDParent'])) {
                $actions->addAction('duplicate', __('Duplicate'))
                        ->setURL('/modules/Library/library_manage_catalog_duplicate.php')
                        ->setIcon('copy');
              }
          });

    echo $table->render($items);
}
