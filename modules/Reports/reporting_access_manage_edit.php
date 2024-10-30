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

use Gibbon\Domain\School\GradeScaleGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_access_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Access'), 'reporting_access_manage.php')
        ->add(__('Edit Access'));

    $gibbonReportingAccessID = $_GET['gibbonReportingAccessID'] ?? '';
    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);

    if (empty($gibbonReportingAccessID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $reportingAccessGateway->getByID($gibbonReportingAccessID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    $form = Form::create('accessManage', $session->get('absoluteURL').'/modules/Reports/reporting_access_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportingAccessID', $gibbonReportingAccessID);

    $values['gibbonRoleIDList'] = explode(',', $values['gibbonRoleIDList']);
    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDList', __('Roles'));
        $row->addSelectRole('gibbonRoleIDList')->required()->selectMultiple();

    $reportingCycle = $reportingCycleGateway->getByID($values['gibbonReportingCycleID']);
    $row = $form->addRow();
        $row->addLabel('reportingCycle', __('Reporting Cycle'));
        $row->addTextField('reportingCycle')->readonly()->setValue($reportingCycle['name']);

    $reportingScopes = $reportingScopeGateway->selectReportingScopesBySchoolYear($gibbonSchoolYearID)->fetchGrouped();
    $scopesOptions = $reportingScopes[$values['gibbonReportingCycleID']] ?? [];
    $scopesOptions = array_combine(array_column($scopesOptions, 'value'), array_column($scopesOptions, 'name'));

    $row = $form->addRow();
        $row->addLabel('gibbonReportingScopeID', __('Scope'));
        $row->addSelect('gibbonReportingScopeID')
            ->setSize(4)
            ->fromArray($scopesOptions)
            ->selectMultiple()
            ->required()
            ->selected(explode(',', $values['gibbonReportingScopeIDList']));

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

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
