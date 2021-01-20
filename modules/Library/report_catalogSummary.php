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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Domain\Library\LibraryReportGateway;
use Gibbon\Tables\Prefab\ReportTable;

$_SESSION[$guid]['report_student_emergencySummary.php_choices'] = '';

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/report_catalogSummary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $ownershipType = $_REQUEST['ownershipType'] ?? '';
    $gibbonLibraryTypeID = $_REQUEST['gibbonLibraryTypeID'] ?? '';
    $gibbonSpaceID = $_REQUEST['gibbonSpaceID'] ?? '';
    $status = $_REQUEST['status'] ?? '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Catalog Summary'));

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Search & Filter'));

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_catalogSummary.php");

        $row = $form->addRow();
            $row->addLabel('ownershipType', __('Ownership Type'));
            $row->addSelect('ownershipType')->fromArray(array('School' => __('School'), 'Individual' => __('Individual')))->selected($ownershipType)->placeholder();

        $sql = "SELECT gibbonLibraryTypeID as value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('gibbonLibraryTypeID', __('Item Type'));
            $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, array())->selected($gibbonLibraryTypeID)->placeholder();

        $sql = "SELECT gibbonSpaceID as value, name FROM gibbonSpace ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('gibbonSpaceID', __('Location'));
            $row->addSelect('gibbonSpaceID')->fromQuery($pdo, $sql, array())->selected($gibbonSpaceID)->placeholder();

        $options = array("Available" => __("Available"), "Decommissioned" => __("Decommissioned"), "In Use" => __("In Use"), "Lost" => __("Lost"), "On Loan" => __("On Loan"), "Repair" => __("Repair"), "Reserved" => __("Reserved"));
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray($options)->selected($status)->placeholder();

        $row = $form->addRow();
            $row->addFooter(false);
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    $reportGateway = $container->get(LibraryReportGateway::class);
    $criteria = $reportGateway->newQueryCriteria(true)
        ->filterBy('id', $gibbonLibraryTypeID)
        ->filterBy('ownershipType', $ownershipType)
        ->filterBy('space', $gibbonSpaceID)
        ->filterBy('status', $status)
        ->fromPOST();

    $catalog = $reportGateway->queryCatalogSummary($criteria);

    // DATA TABLE
    $table = ReportTable::createPaginated('catalogSummary', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Catalog Summary'));

    $table->addColumn('id', __('School ID'))->description(__('Type'))
        ->format(function ($item) {
            return '<b>'.$item['id'].'</b><br/>'.Format::small(__($item['type']));
        });

    $table->addColumn('name', __('Name'))->description(__('Producer'))
        ->format(function ($item) {
            return '<b>'.$item['name'].'</b><br/>'.Format::small($item['producer']);
        });

    $table->addColumn('space', __('Location'))
        ->sortable(['space', 'locationDetail'])
        ->width('15%')
        ->format(function ($item) {
            return $item['space'].'<br/>'.Format::small($item['locationDetail']);
        });

    $table->addColumn('ownership', __('Ownership'))->description(__('User/Owner'))
        ->sortable(['ownershipType', 'surname'])
        ->format(function ($item) use ($gibbon) {
            $output = '';
            if ($item['ownershipType'] == 'School') {
                $output = $gibbon->session->get('organisationNameShort');
            } elseif ($item['ownershipType'] == 'Individual') {
                $output = __('Individual');
            }

            if (!empty($item['gibbonPersonIDOwnership'])) {
                $output .= '<br/>'.Format::small(Format::name($item['title'], $item['preferredName'], $item['surname'], 'Staff', false, true));
            }
            return $output;
        });

    $table->addColumn('status', __('Status'))->description(__('Borrowable'))
        ->format(function ($item) {
            return __($item['status']).'<br/>'.Format::small(Format::yesNo($item['borrowable']));
        });

    $table->addColumn('purchaseDate', __('Purchase Date'))->description(__('Vendor'))
        ->format(function ($item) {
            $output = !empty($item['purchaseDate']) 
                ? Format::date($item['purchaseDate']) 
                : Format::small(__('Unknown'));
            return $output.'<br/>'.Format::small($item['vendor']);
        });

    echo $table->render($catalog);
}
