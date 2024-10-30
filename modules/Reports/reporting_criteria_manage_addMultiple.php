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

use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Form;
use Gibbon\Module\Reports\Domain\ReportingCriteriaTypeGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $referer = $_REQUEST['referer'] ?? '';
    $gibbonReportingCycleID = $_REQUEST['gibbonReportingCycleID'] ?? '';
    $gibbonReportingScopeID = $_REQUEST['gibbonReportingScopeID'] ?? '';
    $scopeTypeIDs = $_REQUEST['scopeTypeID'] ?? [];
    if (!is_array($scopeTypeIDs)) $scopeTypeIDs = explode(',', $scopeTypeIDs);

    $urlParams = compact('gibbonReportingCycleID', 'gibbonReportingScopeID');
    if ($referer == 'scopes') {
        $page->breadcrumbs
            ->add(__('Manage Reporting Cycles'), 'reporting_cycles_manage.php')
            ->add(__('Reporting Scopes'), 'reporting_scopes_manage.php', $urlParams)
            ->add(__('Edit Scope'), 'reporting_scopes_manage_edit.php', $urlParams)
            ->add(__('Add Multiple Criteria'));
    } else {
        $page->breadcrumbs
            ->add(__('Manage Criteria'), 'reporting_criteria_manage.php', $urlParams)
            ->add(__('Add Multiple Criteria'));
    }   

    if (empty($gibbonReportingCycleID) || empty($gibbonReportingScopeID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $reportingScope = $container->get(ReportingScopeGateway::class)->getByID($gibbonReportingScopeID);
    $reportingCycle = $container->get(ReportingCycleGateway::class)->getByID($reportingScope['gibbonReportingCycleID']);
    if (empty($reportingScope) || empty($reportingCycle)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('reportCriteriaManage', $session->get('absoluteURL').'/modules/Reports/reporting_criteria_manage_addMultipleProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportingCycleID', $gibbonReportingCycleID);
    $form->addHiddenValue('gibbonReportingScopeID', $gibbonReportingScopeID);
    $form->addHiddenValue('scopeType', $reportingScope['scopeType']);
    $form->addHiddenValue('referer', $referer);

    $row = $form->addRow();
        $row->addLabel('reportingCycle', __('Reporting Cycle'));
        $row->addTextField('reportingCycle')->readonly()->setValue($reportingCycle['name']);

    $row = $form->addRow();
        $row->addLabel('reportingScope', __('Scope'));
        $row->addTextField('reportingScope')->readonly()->setValue($reportingScope['name']);

    if ($reportingScope['scopeType'] == 'Year Group') {
        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Groups'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selectMultiple()->selected($scopeTypeIDs);
    } elseif ($reportingScope['scopeType'] == 'Form Group') {
        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Groups'));
            $row->addSelectFormGroup('gibbonFormGroupID', $reportingCycle['gibbonSchoolYearID'])->selectMultiple()->selected($scopeTypeIDs);
    } elseif ($reportingScope['scopeType'] == 'Course') {
        $row = $form->addRow();
            $row->addLabel('gibbonCourseID', __('Course'));
            $row->addSelectCourseByYearGroup('gibbonCourseID', $reportingCycle['gibbonSchoolYearID'], $reportingCycle['gibbonYearGroupIDList'])
            ->selectMultiple()->selected($scopeTypeIDs);
    }

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(255)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(2);

    $row = $form->addRow();
        $row->addLabel('category', __('Category'))->description(__('Optionally used to group criteria together.'));
        $row->addTextField('category')->maxLength(255);
        
    $criteriaTypes = $container->get(ReportingCriteriaTypeGateway::class)->selectActiveCriteriaTypes();
    $row = $form->addRow();
        $row->addLabel('gibbonReportingCriteriaTypeID', __('Type'));
        $row->addSelect('gibbonReportingCriteriaTypeID')->fromResults($criteriaTypes)->required()->placeholder();

    $targets = ['Per Student' => __('Per Student'), 'Per Group' => __('Per Group')];
    $row = $form->addRow();
        $row->addLabel('target', __('Target'));
        $row->addSelect('target')->fromArray($targets)->required()->placeholder();
        
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
