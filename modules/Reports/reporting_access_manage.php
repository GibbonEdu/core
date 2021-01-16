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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_access_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Access'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);

    $reportingCycles = $container->get(ReportingCycleGateway::class)->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }

    // FORM
    $form = Form::create('archiveByReport', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Reports/reporting_access_manage.php');

    $row = $form->addRow();
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')
            ->fromArray($reportingCycles)
            ->selected($gibbonReportingCycleID)
            ->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Filters'));

    echo $form->getOutput();

    // QUERY
    $criteria = $reportingAccessGateway->newQueryCriteria(true)
        ->sortBy(['roleName'])
        ->filterBy('reportingCycle', $gibbonReportingCycleID)
        ->fromArray($_POST);

    $access = $reportingAccessGateway->queryReportingAccessBySchoolYear($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = DataTable::createPaginated('accessManage', $criteria);
    $table->setTitle(__('View'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Reports/reporting_access_manage_add.php')
        ->displayLabel();

    $table->addMetaData('filterOptions', [
        'reportingCycle' => __('Reporting Cycle')
    ]);

    $table->addColumn('reportingCycle', __('Reporting Cycle'))
        ->format(function ($values) {
            $output = $values['reportingCycle'];
            if (date('Y-m-d') < $values['cycleDateStart'] || date('Y-m-d') > $values['cycleDateEnd']) {
                $output .= Format::tag(__('Closed'), 'dull ml-2');
            }
            return $output;
        });
    $table->addColumn('roleName', __('Role'))->translatable();
    $table->addColumn('scopeName', __('Scope'));
    $table->addColumn('dateStart', __('Start Date'))->format(Format::using('dateReadable', 'dateStart'));
    $table->addColumn('dateEnd', __('End Date'))->format(Format::using('dateReadable', 'dateEnd'))
        ->format(function ($values) {
            $output = Format::dateReadable($values['dateEnd']);
            if (date('Y-m-d') > $values['dateEnd']) {
                $output .= Format::tag(__('Ended'), 'dull ml-2');
            }
            return $output;
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonReportingAccessID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Reports/reporting_access_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Reports/reporting_access_manage_delete.php');
        });

    echo $table->render($access);
}
