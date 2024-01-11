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
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $page->breadcrumbs
        ->add(__('Manage Reports'), 'reports_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
        ->add(__('Edit Report'));

    $gibbonReportID = $_GET['gibbonReportID'] ?? '';
    $reportGateway = $container->get(ReportGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    if (empty($gibbonReportID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $reportGateway->getByID($gibbonReportID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('templatesManage', $session->get('absoluteURL').'/modules/Reports/reports_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportID', $gibbonReportID);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $form->addRow()->addHeading('Report Details', __('Report Details'));

    $schoolYear = $container->get(SchoolYearGateway::class)->getSchoolYearByID($values['gibbonSchoolYearID']);
    $row = $form->addRow();
        $row->addLabel('schoolYear', __('School Year'));
        $row->addTextField('schoolYear')->required()->readonly()->setValue($schoolYear['name']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $template = $container->get(ReportTemplateGateway::class)->getByID($values['gibbonReportTemplateID']);
    $row = $form->addRow();
        $row->addLabel('template', __('Template'));
        $row->addTextField('template')->required()->readonly()->setValue($template['name']);

    // Reporting Cycle Context
    if ($template['context'] == 'Reporting Cycle') {
        $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($values['gibbonSchoolYearID'])->fetchKeyPair();
        $row = $form->addRow()->addClass('reportingCycleContext');
            $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
            $row->addSelect('gibbonReportingCycleID')->fromArray($reportingCycles)->required()->placeholder();
    }

    // Custom Query Context
    if ($template['context'] == 'Custom Query') {
        $sql = "SELECT queryBuilderQueryID as value, name, category FROM queryBuilderQuery WHERE active='Y' ORDER BY category, name";
        $row = $form->addRow()->addClass('queryContext');
            $row->addLabel('queryBuilderQueryID', __('Query'));
            $row->addSelect('queryBuilderQueryID')->fromQuery($pdo, $sql, [], 'category')->required()->placeholder();
    }

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFromCSV($values);

    $form->addRow()->addHeading('Access', __('Access'));

    $archives = $container->get(ReportArchiveGateway::class)->selectWriteableArchives()->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonReportArchiveID', __('Archive'))->description(__('The selected archive determines where files are saved and who can access them.'));
        $row->addSelect('gibbonReportArchiveID')->fromArray($archives)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('accessDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Reports are hidden until date is reached.'));
        $col = $row->addColumn('accessDate')->setClass('inline');
        $col->addDate('accessDate')->setValue(substr($values['accessDate'], 0, 11))->addClass('mr-2');
        $col->addTime('accessTime')->setValue(substr($values['accessDate'], 11, 5));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
