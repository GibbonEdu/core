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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Forms\CustomFieldHandler;
use League\Container\Exception\NotFoundException;

// require_once '../../gibbon.php';

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

    if ($fieldGroup != 'AllFields' && $fieldGroup != 'CustomFields') {
        $fieldGroupClass = $formBuilder->getFieldGroup($fieldGroup);
        
        if (empty($fieldGroupClass)) {
            echo Format::alert(__('The specified record cannot be found.'));
            return;
        }
    }

    $formFieldGateway = $container->get(FormFieldGateway::class);

    $form = Form::create('formFieldAdd', $session->get('absoluteURL').'/modules/System Admin/formBuilder_page_edit_field_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', '/modules/System Admin/formBuilder_page_edit.php');
    $form->addHiddenValue('fieldGroup', $fieldGroup);
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);
    $form->addHiddenValue('gibbonFormPageID', $gibbonFormPageID);
    
    
    if (!empty($fieldGroupClass) && $description = $fieldGroupClass->getDescription()) {
        $form->addRow()->addHeading(__($fieldGroupClass->getName()))->append($fieldGroupClass->getDescription());
    }

    if ($fieldGroup == 'LayoutHeadings') {
        $row = $form->addRow();
        $row->addLabel('labelLabel', __('Heading Name'));
        $row->addTextField('label')->maxLength(90)->required();

        $row = $form->addRow();
        $row->addLabel('typeLabel', __('Type'));
        $row->addSelect('fields[LayoutHeadings][0]')->fromArray($fieldGroupClass->getFieldOptions());

    } elseif ($fieldGroup == 'LayoutText') {
        $row = $form->addRow();
        $row->addLabel('labelDescription', __('Text'));
        $row->addTextArea('description')->setRows(4)->maxLength(255)->required();

        $form->addHiddenValue('fields[LayoutText][0]', 'text');

    } elseif ($fieldGroup == 'LayoutImage') {
        $form->addHiddenValue('fields[LayoutImage][0]', 'text');

    } elseif ($fieldGroup == 'PersonalDocuments') {
        $row = $form->addRow();
        $row->addLabel('labelLabel', __('Label'));
        $row->addTextField('label')->maxLength(90)->required()->setValue(__('Personal Documents'));

        $row = $form->addRow();
        $row->addLabel('typeLabel', __('Role Category'));
        $row->addSelect('fields[PersonalDocuments][0]')->fromArray($fieldGroupClass->getFieldOptions());
    
    } elseif ($fieldGroup == 'RequiredDocuments') {
        $row = $form->addRow();
        $row->addLabel('label', __('Label'));
        $row->addTextField('label')->maxLength(90)->required()->setValue(__('Required Documents'));

        $row = $form->addRow();
        $row->addLabel('options', __('Required Documents'))
            ->description(__('Comma-separated list of documents which must be submitted electronically with the application form.'));
        $row->addTextArea('options')->required()->setRows(3);

        $row = $form->addRow();
            $row->addLabel('required', __('Required Documents Compulsory?'))->description(__('Are the required documents compulsory?'));
            $row->addYesNo('required')->required()->selected('N');

        $row = $form->addRow();
            $row->addLabel('hidden', __('Office Only'))->description(__('Is this field for office use only?'));
            $row->addYesNo('hidden')->required()->selected('N');

        $form->addHiddenValue('fields[RequiredDocuments][0]', 'generic');
        $form->addHiddenValue('type', 'files');
    
    } elseif ($fieldGroup == 'GenericFields') {
        $form->addHiddenValue('fields[GenericFields][0]', 'generic');

        $row = $form->addRow();
        $row->addLabel('label', __('Label'));
        $row->addTextField('label')->maxLength(90)->required();

        // Prevent files in generic types: these should be handled as documents
        $types = $container->get(CustomFieldHandler::class)->getTypes();
        unset($types[__('File')], $types['File']);
        unset($types[__('Text')]['editor'], $types[__('Text')]['code']);

        $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->placeholder()->required();

        $form->toggleVisibilityByClass('optionsLength')->onSelect('type')->when(['varchar', 'number']);

        $row = $form->addRow()->addClass('optionsLength');
        $row->addLabel('optionsLength', __('Max Length'))->description(__('Number of characters, up to 255.'));
        $row->addNumber('optionsLength')->setName('options')->minimum(1)->maximum(255)->onlyInteger(true);

        $form->toggleVisibilityByClass('optionsRows')->onSelect('type')->when(['text', 'editor']);

        $row = $form->addRow()->addClass('optionsRows');
        $row->addLabel('optionsRows', __('Rows'))->description(__('Number of rows for field.'));
        $row->addNumber('optionsRows')->setName('options')->minimum(1)->maximum(20)->onlyInteger(true);

        $form->toggleVisibilityByClass('optionsOptions')->onSelect('type')->when(['select', 'checkboxes', 'radio']);

        $row = $form->addRow()->addClass('optionsOptions');
        $row->addLabel('optionsOptions', __('Options'))
            ->description(__('Comma separated list of options.'))
            ->description(__('Dropdown: use [] to create option groups.'));
        $row->addTextArea('optionsOptions')->setName('options')->required()->setRows(3);

        $form->toggleVisibilityByClass('optionsFile')->onSelect('type')->when(['file']);

        $row = $form->addRow()->addClass('optionsFile');
        $row->addLabel('optionsFile', __('File Type'))->description(__('Comma separated list of acceptable file extensions (with dot). Leave blank to accept any file type.'));
        $row->addTextField('optionsFile')->setName('options');

        $form->toggleVisibilityByClass('optionsRequired')->onSelect('type')->whenNot('Please select...');

        $row = $form->addRow()->addClass('optionsRequired');
        $row->addLabel('required', __('Required'))->description(__('Is this field compulsory?'));
        $row->addYesNo('required')->required()->selected('N');

    } elseif ($fieldGroup == 'AllFields' || $fieldGroup == 'CustomFields') {

        $fieldGroups = $fieldGroup == 'CustomFields' ? [
            'CustomFields' => __('Custom Fields'),
        ] : [
            'StudentFields'     => __('Student'),
            'AdmissionsFields'  => __('Admissions'),
            'FamilyFields'      => __('Family'),
            'Parent1Fields'      => __('Parent 1'),
            'Parent2Fields'      => __('Parent 2'),
            'MedicalFields'     => __('Medical'),
            'INFields'          => __('Individual Needs'),
            'FinanceFields'     => __('Finance'),
            'LanguageFields'    => __('Language'),
            'ScholarshipFields' => __('Scholarships'),
            'PrivacyFields'     => __('Privacy'),
            'AgreementFields'   => __('Agreement'),
        ];

        foreach ($fieldGroups as $fieldGroupName => $fieldGroupLabel) {
            $fieldGroupClass = $formBuilder->getFieldGroup($fieldGroupName);
            $fields = $fieldGroupClass->getFieldOptions();

            $form->addRow()->addHeading($fieldGroupLabel)->append($fieldGroupClass->getDescription());

            $col = $form->addRow()->addColumn()->addClass('');

            foreach ($fields as $heading => $headingFields) {
                if (empty($headingFields)) continue;

                if (!is_array($headingFields)) {
                    $headingFields = [$heading => $headingFields];
                    $heading = '';
                }

                if ($fieldGroup == 'CustomFields') {
                    $col->addSubheading($heading);
                } elseif (!empty($heading)) {
                    $groupName = 'heading'.preg_replace('/[^a-zA-Z0-9]/', '', $heading);
                    $field = $fieldGroupClass->getField($groupName);

                    $description = '<div class="flex-1 text-left"><span class="text-sm font-bold uppercase text-gray-800 -ml-2">'.__($heading).'</span></div><div>'.($field['type'] ?? '').'</div>';
                        $col->addCheckbox("fields[$fieldGroupName][{$groupName}]")
                            ->setValue($groupName)
                            ->description($description)
                            ->alignLeft()
                            ->setLabelClass('w-full p-4 flex justify-between')
                            ->addClass('border rounded items-center pl-4 my-2');
                }

                foreach ($headingFields as $fieldName => $label) {
                    $description = '<div class="flex-1 text-left"><span class="text-sm -ml-2">'.$label.'</span></div>';
                    $col->addCheckbox("fields[$fieldGroupName][{$fieldName}]")
                        ->setValue($fieldName)
                        ->description($description)
                        ->alignLeft()
                        ->setLabelClass('w-full p-4')
                        ->addClass('items-center border rounded pl-4 my-2 bg-blue-100');
                }
            }
        }

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

    $row = $form->addRow();
    $row->addLabel('sequenceNumberLabel', __('Where').':');
    $row->addSelect('sequenceNumber')->fromArray($fieldOrder)->selected(-1);

    $row = $form->addRow();
    $row->addSubmit(__('Add'))->addClass('mt-4');

    echo $form->getOutput();
}
