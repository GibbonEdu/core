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

if (isActionAccessible($guid, $connection2, '/modules/Reports/criteriaTypes_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Criteria'), 'reporting_criteria_manage.php')
        ->add(__('Manage Criteria Types'), 'criteriaTypes_manage.php')
        ->add(__('Add Criteria Type'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/criteriaTypes_manage_edit.php&gibbonReportingCriteriaTypeID='.$_GET['editID'];
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('criteriaTypesManage', $gibbon->session->get('absoluteURL').'/modules/Reports/criteriaTypes_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $valueTypes = [
        'Grade Scale' => __('Grade Scale'),
        'Comment'     => __('Comment'),
        'Remark'      => __('Remark'),
        'Yes/No'      => __('Yes/No'),
        'Text'        => __('Text'),
        'Number'      => __('Number'),
    ];
    $row = $form->addRow();
        $row->addLabel('valueType', __('Value Type'));
        $row->addSelect('valueType')->fromArray($valueTypes)->required()->placeholder();

    $form->toggleVisibilityByClass('characterLimit')->onSelect('valueType')->when(['Comment', 'Remark']);
    $row = $form->addRow()->addClass('characterLimit');
        $row->addLabel('characterLimit', __('Character Limit'));
        $row->addNumber('characterLimit')->maxLength(6)->required()->setValue(1000);

    $form->toggleVisibilityByClass('gradeScale')->onSelect('valueType')->when('Grade Scale');
    $row = $form->addRow()->addClass('gradeScale');
        $row->addLabel('gibbonScaleID', __('Grade Scale'));
        $row->addSelectGradeScale('gibbonScaleID');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
