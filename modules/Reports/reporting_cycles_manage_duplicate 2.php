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
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Reporting Cycles'), 'reporting_cycles_manage.php')
        ->add(__('Duplicate Reporting Cycle'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_cycles_manage_edit.php&gibbonReportingCycleID='.$_GET['editID'];
    }

    $page->return->setEditLink($editLink);
    $page->return->addReturns([
        'warning3' => __('Duplication was successful, however {count} criteria did not match form groups or courses in the target school year and could not be copied.', ['count' => $_GET['failedCriteria'] ?? 0]),
    ]);

    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    if (empty($gibbonReportingCycleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $reportingCycleGateway->getByID($gibbonReportingCycleID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $page->addMessage(__('Duplicating this reporting cycle will copy all milestones and criteria to the new reporting cycle. Reporting access will not be copied.'));

    $form = Form::create('reportingCycles', $session->get('absoluteURL').'/modules/Reports/reporting_cycles_manage_duplicateProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportingCycleID', $gibbonReportingCycleID);

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearID', __('School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearID')->required()->selected($session->get('gibbonSchoolYearID'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('name')->maxLength(90)->required()->setValue($values['name'].' '.__('Copy'));

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'));
        $row->addTextField('nameShort')->maxLength(20)->required()->setValue($values['nameShort'].' '.__('Copy'));

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->required();

    $row = $form->addRow();
        $row->addLabel('cycleNumber', __('Cycle Number'));
        $row->addNumber('cycleNumber')->onlyInteger(true)->required()->setValue($values['cycleNumber'] + 1);

    $row = $form->addRow();
        $row->addLabel('cycleTotal', __('Total Cycles'));
        $row->addNumber('cycleTotal')->onlyInteger(true)->required()->setValue($values['cycleTotal']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
