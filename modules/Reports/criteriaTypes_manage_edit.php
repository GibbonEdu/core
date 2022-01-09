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

use Gibbon\Domain\School\GradeScaleGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportingCriteriaTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/criteriaTypes_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Criteria'), 'reporting_criteria_manage.php')
        ->add(__('Manage Criteria Types'), 'criteriaTypes_manage.php')
        ->add(__('Edit Criteria Type'));

    $gibbonReportingCriteriaTypeID = $_GET['gibbonReportingCriteriaTypeID'] ?? '';
    $criteriaTypeGateway = $container->get(ReportingCriteriaTypeGateway::class);

    if (empty($gibbonReportingCriteriaTypeID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $criteriaTypeGateway->getByID($gibbonReportingCriteriaTypeID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('criteriaTypesManage', $gibbon->session->get('absoluteURL').'/modules/Reports/criteriaTypes_manage_editProcess.php');
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonReportingCriteriaTypeID', $gibbonReportingCriteriaTypeID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('valueTypeLabel', __('Value Type'));
        $row->addTextField('valueTypeLabel')->readOnly()->setValue(__($values['valueType']));

    if ($values['valueType'] == 'Comment' || $values['valueType'] == 'Remark') {
        $row = $form->addRow()->addClass('characterLimit');
            $row->addLabel('characterLimit', __('Character Limit'));
            $row->addNumber('characterLimit')->maxLength(6)->required();
    }

    if ($values['valueType'] == 'Grade Scale') {
        $gradeScale = $container->get(GradeScaleGateway::class)->getByID($values['gibbonScaleID']);
        $row = $form->addRow();
            $row->addLabel('gradeScale', __('Grade Scale'));
            $row->addTextField('gradeScale')->readonly()->setValue($gradeScale['name'] ?? '');
    }

    if ($values['valueType'] == 'Yes/No') {
        $row = $form->addRow();
            $row->addLabel('defaultValue', __('Default Value'));
            $row->addYesNo('defaultValue')
                ->placeholder()
                ->selected($values['defaultValue']);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
