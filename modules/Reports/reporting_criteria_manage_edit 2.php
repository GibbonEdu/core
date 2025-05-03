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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaTypeGateway;
use Gibbon\Module\Reports\Domain\ReportingValueGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';
    $gibbonReportingScopeID = $_GET['gibbonReportingScopeID'] ?? '';
    $gibbonReportingCriteriaID = $_GET['gibbonReportingCriteriaID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Criteria'), 'reporting_criteria_manage.php', ['gibbonReportingCycleID' => $gibbonReportingCycleID, 'gibbonReportingScopeID' => $gibbonReportingScopeID])
        ->add(__('Edit Criteria'));

    if (empty($gibbonReportingCriteriaID) || empty($gibbonReportingScopeID) || empty($gibbonReportingCycleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $reportingCycle = $container->get(ReportingCycleGateway::class)->getByID($gibbonReportingCycleID);
    $reportingScope = $container->get(ReportingScopeGateway::class)->getByID($gibbonReportingScopeID);
    if (empty($reportingCycle) || empty($reportingScope)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
 
    $values = $reportingCriteriaGateway->getByID($gibbonReportingCriteriaID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if (!empty($values['groupID'])) {
        $groupCount = $reportingCriteriaGateway->selectBy(['gibbonReportingCycleID' => $gibbonReportingCycleID, 'groupID' => $values['groupID']])->rowCount();
        echo Format::alert(__('This is a grouped record created using Add Multiple.').' '.__('Editing this record will update all {count} records in the same group. Check the detach option to remove this record from the group and not update other records.', ['count' => '<b>'.$groupCount.'</b>']), 'error');
    }

    $criteriaInUse = $container->get(ReportingValueGateway::class)->selectBy(['gibbonReportingCriteriaID' => $gibbonReportingCriteriaID])->rowCount();
    if ($criteriaInUse > 0) {
        echo Format::alert(__('This criteria is already in use in {count} locations. It cannot be deleted or changed to a different criteria type.', ['count' => '<b>'.$criteriaInUse.'</b>']), 'warning');
    }

    $form = Form::create('reportCriteriaManage', $session->get('absoluteURL').'/modules/Reports/reporting_criteria_manage_editProcess.php');
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportingCriteriaID', $gibbonReportingCriteriaID);
    $form->addHiddenValue('gibbonReportingScopeID', $gibbonReportingScopeID);
    $form->addHiddenValue('gibbonReportingCycleID', $gibbonReportingCycleID);
    $form->addHiddenValue('gibbonYearGroupID', $values['gibbonYearGroupID']);
    $form->addHiddenValue('gibbonFormGroupID', $values['gibbonFormGroupID']);
    $form->addHiddenValue('gibbonCourseID', $values['gibbonCourseID']);
    $form->addHiddenValue('groupID', $values['groupID']);

    $row = $form->addRow();
        $row->addLabel('reportingCycle', __('Reporting Cycle'));
        $row->addTextField('reportingCycle')->readonly()->setValue($reportingCycle['name']);

    $row = $form->addRow();
        $row->addLabel('reportingScope', __('Scope'));
        $row->addTextField('reportingScope')->readonly()->setValue($reportingScope['name']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(255)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(2);

    $row = $form->addRow();
        $row->addLabel('category', __('Category'))->description(__('Optionally used to group criteria together.'));
        $row->addTextField('category')->maxLength(255);

    if ($criteriaInUse == 0) {
        $criteriaTypes = $container->get(ReportingCriteriaTypeGateway::class)->selectActiveCriteriaTypes();
        $row = $form->addRow();
            $row->addLabel('gibbonReportingCriteriaTypeID', __('Type'));
            $row->addSelect('gibbonReportingCriteriaTypeID')->fromResults($criteriaTypes)->required()->placeholder();

        $targets = ['Per Student' => __('Per Student'), 'Per Group' => __('Per Group')];
        $row = $form->addRow();
            $row->addLabel('target', __('Target'));
            $row->addSelect('target')->fromArray($targets)->required()->placeholder();
    } else {
        $form->addHiddenValue('gibbonReportingCriteriaTypeID', $values['gibbonReportingCriteriaTypeID']);
        $form->addHiddenValue('target', $values['target']);

        $criteriaType = $container->get(ReportingCriteriaTypeGateway::class)->getByID($values['gibbonReportingCriteriaTypeID']);
        $row = $form->addRow();
            $row->addLabel('criteriaType', __('Type'));
            $row->addTextField('criteriaType')->readonly()->setValue($criteriaType['name'] ?? '');

        $row = $form->addRow();
            $row->addLabel('target', __('Target'));
            $row->addTextField('target')->readonly()->setValue(__($values['target']));
    }

    if (!empty($values['groupID'])) {
        $row = $form->addRow();
            $row->addLabel('detach', __('Detach?'))->description(__('Removes this record from a grouped set.'));
            $row->addCheckbox('detach')->setValue('Y');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
