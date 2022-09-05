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
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_access_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Access'), 'reporting_access_manage.php')
        ->add(__('Add Access'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_access_manage_edit.php&gibbonReportingAccessID='.$_GET['editID'];
    }

    $page->return->setEditLink($editLink);

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $gibbon->session->get('gibbonSchoolYearID');
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    $form = Form::create('accessManage', $gibbon->session->get('absoluteURL').'/modules/Reports/reporting_access_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDList', __('Roles'));
        $row->addSelectRole('gibbonRoleIDList')->required()->selectMultiple();

    $reportingCycles = $reportingCycleGateway->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();
    $row = $form->addRow()->addClass('reportingCycleContext');
        $row->addLabel('gibbonReportingCycleID', __('Reporting Cycle'));
        $row->addSelect('gibbonReportingCycleID')->fromArray($reportingCycles)->required()->placeholder();

    $reportingScopes = $reportingScopeGateway->selectReportingScopesBySchoolYear($gibbonSchoolYearID)->fetchAll();
    $scopesChained = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'chained'));
    $scopesOptions = array_combine(array_column($reportingScopes, 'value'), array_column($reportingScopes, 'name'));
    
    $row = $form->addRow();
        $row->addLabel('gibbonReportingScopeID', __('Scope'));
        $row->addSelect('gibbonReportingScopeID')
            ->fromArray($scopesOptions)
            ->chainedTo('gibbonReportingCycleID', $scopesChained)
            ->setSize(4)
            ->required()
            ->selectMultiple();

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->chainedTo('dateEnd')->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->chainedFrom('dateStart')->required();

    $row = $form->addRow();
        $row->addLabel('canWrite', __('Can Write'));
        $row->addYesNo('canWrite')->required();

    $row = $form->addRow();
        $row->addLabel('canProofRead', __('Can Proof Read'));
        $row->addYesNo('canProofRead')->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
