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
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Reports/criteriaTypes_manage_edit.php&gibbonReportingCriteriaTypeID='.$_GET['editID'];
    }

    $page->return->setEditLink($editLink);

    $form = Form::create('criteriaTypesManage', $session->get('absoluteURL').'/modules/Reports/criteriaTypes_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

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
        'Image'       => __('Image'),
    ];
    $row = $form->addRow();
        $row->addLabel('valueType', __('Value Type'));
        $row->addSelect('valueType')->fromArray($valueTypes)->required()->placeholder();

    $form->toggleVisibilityByClass('characterLimit')->onSelect('valueType')->when(['Comment', 'Remark']);
    $row = $form->addRow()->addClass('characterLimit');
        $row->addLabel('characterLimit', __('Character Limit'));
        $row->addNumber('characterLimit')->maxLength(6)->required()->setValue(1000);

    $form->toggleVisibilityByClass('imageOptions')->onSelect('valueType')->when('Image');
    $row = $form->addRow()->addClass('imageOptions');
        $row->addLabel('imageSize', __('Maximum Size'))->description(__('In Pixels'));
        $row->addRange('imageSize', 40, 2048, 1)->required()->setValue(1024);

    $row = $form->addRow()->addClass('imageOptions');
        $row->addLabel('imageQuality', __('Image Quality'))->description(__('Percentage'));
        $row->addRange('imageQuality', 40, 100, 5)->required()->setValue(80);

    $form->toggleVisibilityByClass('gradeScale')->onSelect('valueType')->when('Grade Scale');
    $row = $form->addRow()->addClass('gradeScale');
        $row->addLabel('gibbonScaleID', __('Grade Scale'));
        $row->addSelectGradeScale('gibbonScaleID');

    $form->toggleVisibilityByClass('yesNoDefault')->onSelect('valueType')->when('Yes/No');
    $row = $form->addRow()->addClass('yesNoDefault');
        $row->addLabel('defaultValue', __('Default Value'));
        $row->addYesNo('defaultValue')
            ->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
