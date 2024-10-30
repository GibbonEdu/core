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
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Library\LibraryReportGateway;
use Gibbon\Domain\DataSet;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/report_viewOverdueItems.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $ignoreStatus = $_GET['ignoreStatus'] ?? '';
    $gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
    $gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
    $today = date('Y-m-d');

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('View Overdue Items'));

        $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_viewOverdueItems.php");

        $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
        $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')
            ->fromQuery($pdo, $sql)
            ->selected($gibbonLibraryTypeID)
            ->placeholder();

        $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('gibbonDepartmentID', __('Department'));
            $row->addSelect('gibbonDepartmentID')
                ->fromQuery($pdo, $sql)
                ->selected($gibbonDepartmentID)
                ->placeholder();

        $row = $form->addRow();
            $row->addLabel('ignoreStatus', __('Ignore Status'))->description(__('Include all users, regardless of status and current enrolment.'));
            $row->addCheckbox('ignoreStatus')->checked($ignoreStatus);

        $row = $form->addRow();
            $row->addFooter(false);
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    $reportGateway = $container->get(LibraryReportGateway::class);
    $criteria = $reportGateway->newQueryCriteria(true)
        ->filterBy('type', $gibbonLibraryTypeID)
        ->filterBy('department', $gibbonDepartmentID)
        ->fromPOST();

    $items = $reportGateway->queryOverdueItems($criteria, $session->get('gibbonSchoolYearID'), $ignoreStatus);

    // DATA TABLE
    $table = ReportTable::createPaginated('overdueItems', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('View Overdue Items'));

    $table->addColumn('preferredName', __('Borrowing User'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true);
        });
    $table->addColumn('formGroup', __('Form Group'));
    $table->addColumn('email', __('Email'));
    $table->addColumn('name', __('Item'))
        ->description(__('Author/Producer'))
        ->format(function ($item) {
            return '<b>'.$item['name'].'</b><br/>'.Format::small($item['producer']);
        });
    $table->addColumn('id', __('ID'));
    $table->addColumn('returnExpected', __('Due Date'))->format(Format::using('date', 'returnExpected'));
    $table->addColumn('dueDate', __('Days Overdue'))
        ->sortable('returnExpected')
        ->format(function ($item) use ($today) {
            return (strtotime($today) - strtotime($item['returnExpected'])) / (60 * 60 * 24);
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonLibraryItemID')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($person, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Library/library_lending_item.php');
        });

    echo $table->render($items);
}
