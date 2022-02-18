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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Forms\CustomFieldHandler;
use League\Container\Exception\NotFoundException;

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
    $gibbonFormPageID = $_REQUEST['gibbonFormPageID'] ?? '';
    $fieldGroup = $_REQUEST['fieldGroup'] ?? '';

    if (empty($fieldGroup)) {
        return;
    }

    // Get the field group class for the selected option
    $formBuilder = $container->get(FormBuilder::class);
    $fieldGroupClass = $formBuilder->getFieldGroup($fieldGroup);
    
    if (empty($fieldGroupClass)) {
        echo Format::alert(__('The specified record cannot be found.'));
        return;
    }

    $formFieldGateway = $container->get(FormFieldGateway::class);

    $form = Form::create('formFieldAdd', $session->get('absoluteURL').'/modules/System Admin/formBuilder_page_edit_field_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', '/modules/System Admin/formBuilder_page_edit.php');
    $form->addHiddenValue('fieldGroup', $fieldGroup);
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);
    $form->addHiddenValue('gibbonFormPageID', $gibbonFormPageID);
    
    $col = $form->addRow()->addColumn()->addClass('flex flex-col');

    if ($description = $fieldGroupClass->getDescription()) {
        $col->addContent('<p>'.$description.'</p>');
    }

    if ($fieldGroup == 'LayoutHeadings') {
        $col->addLabel('labelLabel', __('Heading Name'))->setClass('text-xs');
        $col->addTextField('label')->maxLength(90)->required();

        $col->addLabel('typeLabel', __('Type'))->setClass('text-xs');
        $col->addSelect('fields[0]')->fromArray($fieldGroupClass->getFieldOptions());

    } elseif ($fieldGroup == 'LayoutText') {
        $col->addLabel('labelDescription', __('Text'))->setClass('text-xs');
        $col->addTextArea('description')->setRows(4)->maxLength(255)->required();

        $form->addHiddenValue('fields[0]', 'text');

    } elseif ($fieldGroup == 'PersonalDocuments') {

        $col->addLabel('labelLabel', __('Label'))->setClass('text-xs');
        $col->addTextField('label')->maxLength(90)->required()->setValue(__('Personal Documents'));

        $col->addLabel('typeLabel', __('Role Category'))->setClass('text-xs');
        $col->addSelect('fields[0]')->fromArray($fieldGroupClass->getFieldOptions());
    
    } elseif ($fieldGroup == 'GenericFields') {
        $form->addHiddenValue('fields[0]', 'generic');

        $col->addLabel('label', __('Label'))->setClass('text-xs');
        $col->addTextField('label')->maxLength(90)->required();

        $col->addLabel('type', __('Type'))->setClass('text-xs');
        $col->addSelect('type')->fromArray($container->get(CustomFieldHandler::class)->getTypes())->placeholder()->required();

        $form->toggleVisibilityByClass('optionsLength')->onSelect('type')->when(['varchar', 'number']);

        $col->addLabel('optionsLength', __('Max Length'))->description(__('Number of characters, up to 255.'))->setClass('optionsLength text-xs');
        $col->addNumber('optionsLength')->setName('options')->minimum(1)->maximum(255)->onlyInteger(true)->addClass('optionsLength');

        $form->toggleVisibilityByClass('optionsRows')->onSelect('type')->when(['text', 'editor']);

        $col->addLabel('optionsRows', __('Rows'))->description(__('Number of rows for field.'))->setClass('optionsRows text-xs');
        $col->addNumber('optionsRows')->setName('options')->minimum(1)->maximum(20)->onlyInteger(true)->addClass('optionsRows');

        $form->toggleVisibilityByClass('optionsOptions')->onSelect('type')->when(['select', 'checkboxes', 'radio']);

        $col->addLabel('optionsOptions', __('Options'))->setClass('optionsOptions text-xs')
            ->description(__('Comma separated list of options.'))
            ->description(__('Dropdown: use [] to create option groups.'));
        $col->addTextArea('optionsOptions')->setName('options')->required()->setRows(3)->addClass('optionsOptions');

        $form->toggleVisibilityByClass('optionsFile')->onSelect('type')->when(['file']);

        $col->addLabel('optionsFile', __('File Type'))->description(__('Comma separated list of acceptable file extensions (with dot). Leave blank to accept any file type.'))->setClass('optionsFile text-xs');
        $col->addTextField('optionsFile')->setName('options')->addClass('optionsFile');

        $form->toggleVisibilityByClass('optionsRequired')->onSelect('type')->whenNot(__('Please select...'));

        $col->addLabel('required', __('Required'))->description(__('Is this field compulsory?'))->setClass('optionsRequired text-xs');
        $col->addYesNo('required')->required()->selected('N')->addClass('optionsRequired');

    } else {
        $fields = $fieldGroupClass->getFieldOptions();

        $col->addLabel('fields', __('Fields to add').':')->setClass('text-xs');
        $col->addCheckbox('fields')->fromArray($fields)->selectableGroups();
    }

    $heading = '';
    $fieldOrder = $formFieldGateway->selectFieldOrderByPage($gibbonFormPageID)->fetchAll();
    $fieldOrder = array_reduce($fieldOrder, function ($group, $item) use (&$heading) {
        if ($item['fieldType'] == 'heading') {
            $heading = $item['label'];
            $group[$heading][$item['sequenceNumber']] = __('start of {heading}', ['heading' => $item['label']]);
            return $group;
        }

        if (!empty($heading)) {
            $group[$heading][$item['sequenceNumber']] = __('after {field}', ['field' => $item['label']]);
        } else {
            $group[$item['sequenceNumber']] = __('after {field}', ['field' => $item['label']]);
        }

        return $group;
    }, ['0' => __('Start of form'), '-1' => __('End of form')]);

    $col->addLabel('sequenceNumberLabel', __('Where').':')->setClass('text-xs');
    $col->addSelect('sequenceNumber')->fromArray($fieldOrder)->selected(-1);

    $col->addSubmit(__('Add'))->addClass('mt-4');

    echo $form->getOutput();
}
