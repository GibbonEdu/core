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
use Gibbon\Services\Format;
use Gibbon\Domain\Forms\FormFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $urlParams = [
        'gibbonFormID'      => $_REQUEST['gibbonFormID'] ?? '',
        'gibbonFormPageID'  => $_REQUEST['gibbonFormPageID'] ?? '',
        'gibbonFormFieldID' => $_REQUEST['gibbonFormFieldID'] ?? '',
        'fieldGroup'        => $_REQUEST['fieldGroup'] ?? '',
    ];

    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Edit Form'), 'formBuilder_edit.php', $urlParams)
        ->add(__('Edit Page'), 'formBuilder_edit_page.php', $urlParams)
        ->add(__('Edit Field'));

    $formFieldGateway = $container->get(FormFieldGateway::class);

    if (empty($urlParams['gibbonFormID']) || empty($urlParams['gibbonFormPageID']) || empty($urlParams['gibbonFormFieldID'])) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $formFieldGateway->getByID($urlParams['gibbonFormFieldID']);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('formsManage', $session->get('absoluteURL').'/modules/System Admin/formBuilder_page_edit_field_editProcess.php');
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValues($urlParams);

    // $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
    $row->addLabel('fieldName', __('Field'));
    $row->addTextField('fieldName')->readonly();

    $row = $form->addRow();
        $row->addLabel('label', __('Label'));
        $row->addTextField('label')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->maxLength(255)->setRows(2);

    if ($values['fieldType'] != 'heading' && $values['fieldType'] != 'subheading') {
        $row = $form->addRow();
            $row->addLabel('required', __('Required'));

        if ($values['required'] == 'X') {
            $form->addHiddenValue('required', 'X');
            $row->addTextField('required')->readonly()->setValue('Yes');
        } else {
            $row->addYesNo('required')->required();
        }
    }

    if ($values['fieldGroup'] == 'GenericFields' && in_array($values['fieldType'], ['varchar', 'number'])) {
        $row = $form->addRow();
            $row->addLabel('options', __('Max Length'))->description(__('Number of characters, up to 255.'));
            $row->addNumber('options')->setName('options')->minimum(1)->maximum(255)->onlyInteger(true);
    }

    if ($values['fieldGroup'] == 'GenericFields' && in_array($values['fieldType'], ['text', 'editor'])) {
        $row = $form->addRow();
            $row->addLabel('options', __('Rows'))->description(__('Number of rows for field.'));
            $row->addNumber('options')->setName('options')->minimum(1)->maximum(20)->onlyInteger(true);
    }

    if (in_array($values['fieldType'], ['select', 'checkboxes', 'radio'])) {
        $row = $form->addRow();
            $row->addLabel('options', __('Options'))
                ->description(__('Comma separated list of options.'))
                ->description(__('Dropdown: use [] to create option groups.'));
            $row->addTextArea('options')->setName('options')->required()->setRows(3);
    }

    if ($values['fieldGroup'] == 'GenericFields' && in_array($values['fieldType'], ['file'])) {
        $row = $form->addRow();
            $row->addLabel('options', __('File Type'))->description(__('Comma separated list of acceptable file extensions (with dot). Leave blank to accept any file type.'));
            $row->addTextField('options')->setName('options');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
