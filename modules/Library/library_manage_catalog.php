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
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    //Get current filter values
    $viewMode = $_REQUEST['format'] ?? '';
    $name = $_REQUEST['name'] ?? '';
    $gibbonLibraryTypeID = $_REQUEST['gibbonLibraryTypeID'] ?? '';
    $gibbonSpaceID = $_REQUEST['gibbonSpaceID'] ?? '';
    $status = $_REQUEST['status'] ?? '';
    $gibbonPersonIDOwnership = $_REQUEST['gibbonPersonIDOwnership'] ?? '';
    $typeSpecificFields = $_REQUEST['typeSpecificFields'] ?? '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Manage Catalog'));

        $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');
        $form->setTitle(__('Search & Filter'));

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/library_manage_catalog.php");

        $row = $form->addRow();
            $row->addLabel('name', __('ID/Name/Producer'));
            $row->addTextField('name')->setValue($name);

        $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
        $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')
            ->fromQuery($pdo, $sql, array())
            ->selected($gibbonLibraryTypeID)
            ->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonSpaceID', __('Location'));
            $row->addSelectSpace('gibbonSpaceID')->selected($gibbonSpaceID)->placeholder();

        $statuses = array(
            'Available' => __('Available'),
            'Decommissioned' => __('Decommissioned'),
            'In Use' => __('In Use'),
            'Lost' => __('Lost'),
            'On Loan' => __('On Loan'),
            'Repair' => __('Repair'),
            'Reserved' => __('Reserved')
        );
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray($statuses)->selected($status)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDOwnership', __('Owner/User'));
            $row->addSelectUsers('gibbonPersonIDOwnership')->selected($gibbonPersonIDOwnership)->placeholder();

        $row = $form->addRow();
        $row->addLabel('typeSpecificFields', __('Type-Specific Fields'))
            ->description(__('For example, a computer\'s MAC address or a book\'s ISBN.'));
        $row->addTextField('typeSpecificFields')
            ->setValue($typeSpecificFields);

        $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

        echo $form->getOutput();
    }

    $gateway = $container->get(LibraryGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy('id')
        ->filterBy('name', $name)
        ->filterBy('type', $gibbonLibraryTypeID)
        ->filterBy('location', $gibbonSpaceID)
        ->filterBy('status', $status)
        ->filterBy('owner', $gibbonPersonIDOwnership)
        ->filterBy('typeSpecificFields', $typeSpecificFields)
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();
    $items = $gateway->queryCatalog($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    $table = ReportTable::createPaginated('items', $criteria)->setViewMode($viewMode, $gibbon->session);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Library/library_manage_catalog_add.php')
        ->addParam('gibbonLibraryTypeID', $gibbonLibraryTypeID)
        ->addParam('name', $name)
        ->addParam('gibbonSpaceID', $gibbonSpaceID)
        ->addParam('status', $status)
        ->addParam('gibbonPersonIDOwnership', $gibbonPersonIDOwnership)
        ->addParam('typeSpecificFields', $typeSpecificFields)
        ->displayLabel();

    $table->addColumn('id', __('School ID'))
        ->description(__('Type'))
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/>%2$s', $item['id'], Format::small(__($item['itemType'])));
        });
    $table->addColumn('name', __('Name'))
        ->description(__('Producer'))
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/>%2$s', $item['name'], Format::small($item['producer']));
        });
    $table->addColumn('spaceName', __('Location'))
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/>%2$s', $item['spaceName'], Format::small($item['locationDetail']));
        });
    $table->addColumn('ownershipType', __('Ownership'))
        ->description(__('User/Owner'))
        ->format(function ($item) use ($gibbon) {
            $ownership = '';
            if ($item['ownershipType'] == 'School') {
                $ownership .= sprintf('<b>%1$s</b><br/>', $gibbon->session->get('organisationNameShort'));
            } elseif ($item['ownershipType'] == 'Individual') {
                $ownership .= sprintf('<b>%1$s</b><br/>', __('Individual'));
            }
            return $ownership . Format::small(Format::name($item['title'], $item['preferredName'], $item['surname'], "Student"));
        });
    $table->addColumn('status', __('Status'))
        ->description(__('Responsible User'))
        ->format(function ($item) {
            $responsible = !empty($item['surnameResponsible'])
                ? Format::name($item['titleResponsible'], $item['preferredNameResponsible'], $item['surnameResponsible'], 'Student')
                : '';
            $responsible .= !empty($item['rollGroup'])
                ? ' ('.$item['rollGroup'].')'
                : '';
            return '<b>' . __($item['status']) . '</b><br/>' . Format::small($responsible);
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
              $actions->addAction('duplicate', __('Duplicate'))
                      ->setURL('/modules/Library/library_manage_catalog_duplicate.php')
                      ->setIcon('copy');
          });

    echo $table->render($items);
}
