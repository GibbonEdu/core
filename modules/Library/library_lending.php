<?php
/**
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
**/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Services\Format;

$page->breadcrumbs->add(__('Lending & Activity Log'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    echo '<h3>';
    echo __('Search & Filter');
    echo '</h3>';

    //Get current filter values
    $name = $_REQUEST['name'] ?? '';
    $gibbonLibraryTypeID = $_REQUEST['gibbonLibraryTypeID'] ?? '';
    $gibbonSpaceID = $_REQUEST['gibbonSpaceID'] ?? '';
    $status = $_REQUEST['status'] ?? '';

    $form = Form::create('action', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/library_lending.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$session->get('module')."/library_lending.php");

    $row = $form->addRow();
        $row->addLabel('name', __('ID/Name/Producer'));
        $row->addTextField('name')->setValue($name)->maxLength(50);

    $data = array();
    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, $data)->placeholder()->selected($gibbonLibraryTypeID);

    $row = $form->addRow();
        $row->addLabel('gibbonSpaceID', __('Space'));
        $row->addSelectSpace('gibbonSpaceID')->placeholder()->selected($gibbonSpaceID);

    $statuses = array(
        'Available' => __('Available'),
        'On Loan' => __('On Loan'),
        'Repair' => __('Repair'),
        'Reserved' => __('Reserved')
    );
    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($statuses)->selected($status)->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    $gateway = $container->get(LibraryGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
                        ->sortBy(['timestampStatus'], 'DESC')
                        ->filterBy('name', $name)
                        ->filterBy('gibbonLibraryTypeID', $gibbonLibraryTypeID)
                        ->filterBy('gibbonSpaceID', $gibbonSpaceID)
                        ->filterBy('status', $status)
                        ->fromPOST();
    $items = $gateway->queryLending($criteria);

    $table = DataTable::createPaginated('lending', $criteria);

    $table->setTitle(__('Lending & Activity Log'));

    $table->addColumn('id', __('ID'));
    $table->addColumn('name', __('Name'))->format(function ($item) {
        return sprintf('<b>%1$s</b><br/>%2$s', $item['name'], Format::small($item['producer']));
    });
    $table->addColumn('typeName', __('Type'))->translatable();
    $table->addColumn('location', __('Location'))
        ->sortable(['spaceName', 'locationDetail'])
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/>%2$s', $item['spaceName'], Format::small($item['locationDetail']));
        });
    $table->addColumn('timestampStatus', __('Status'))
        ->description(__('Return'))
        ->format(function ($item) {
            $statusDetail = "";
            if ($item['returnExpected'] != null) {
                $statusDetail .= sprintf(
                    '<br/>%1$s<br/>%2$s',
                    Format::small(Format::date($item['returnExpected'])),
                    Format::small(Format::name($item['title'], $item['preferredName'], $item['surname'], 'Student', false, true))
                );
            }
            return sprintf(
                '<b>%1$s</b>%2$s',
                __($item['status']),
                $statusDetail
            );
        });

    $table->addActionColumn()
          ->addParam('gibbonLibraryItemID')
          ->addParam('name', $name)
          ->addParam('gibbonLibraryTypeID', $gibbonLibraryTypeID)
          ->addParam('gibbonSpaceID', $gibbonSpaceID)
          ->addParam('status', $status)
          ->format(function ($item, $actions) {
            $actions->addAction('edit', __('Edit'))
              ->setURL('/modules/Library/library_lending_item.php');
          });

    $table->modifyRows(function ($item, $row) {
        switch ($item['status']) {
            case 'On Loan':
                if ($item['pastDue'] == "Y") {
                    $row->addClass('error');
                } else {
                    $row->addClass('warning');
                }
                break;

            case 'Expired':
                $row->addClass('error');
                break;
        }
        return $row;
    });
    echo $table->render($items);
}
