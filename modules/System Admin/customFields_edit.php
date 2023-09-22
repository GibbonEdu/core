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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/customFields_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Custom Fields'), 'customFields.php')
        ->add(__('Edit Custom Field'));  

    $customFieldGateway = $container->get(CustomFieldGateway::class);

    $gibbonCustomFieldID = $_GET['gibbonCustomFieldID'] ?? '';
    if (empty($gibbonCustomFieldID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $customFieldGateway->getByID($gibbonCustomFieldID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $customFieldHandler = $container->get(CustomFieldHandler::class);
        
    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/customFields_editProcess.php?gibbonCustomFieldID='.$gibbonCustomFieldID);

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Basic Details', __('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('context', __('Context'));
        $row->addTextField('context')->readonly();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this context.'));
        $row->addTextField('name')->maxLength(50)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Displayed as smaller text next to the field name.'));
        $row->addTextField('description')->maxLength(255);

    $headings = $customFieldHandler->getHeadings();
    $headings = $headings[$values['context']] ?? [];
    $isHeadingCustom = !empty($values['heading']) && !array_key_exists($values['heading'], $headings);
    $row = $form->addRow();
        $row->addLabel('heading', __('Heading'))->description(__('Optionally list this field under a heading.'));
        $row->addSelect('heading')
            ->fromArray($headings)
            ->fromArray(['Custom' => '['.__('Custom').']'])
            ->placeholder()
            ->selected($isHeadingCustom ? 'Custom' : $values['heading']);
    
    $form->toggleVisibilityByClass('headingCustom')->onSelect('heading')->when('Custom');
    
    $row = $form->addRow()->addClass('headingCustom');
        $row->addLabel('headingCustom', __('Custom Heading'));
        $row->addTextField('headingCustom')->maxLength(90)->setValue($isHeadingCustom ? $values['heading'] : '');
        unset($values['heading']);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $form->addRow()->addHeading('Configure', __('Configure'));

    $types = $customFieldHandler->getTypes();
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->readOnly()->required()->placeholder();

    if ($values['type'] == 'varchar' || $values['type'] == 'number') {
        $row = $form->addRow()->addClass('optionsLength');
            $row->addLabel('options', __('Max Length'))->description(__('Number of characters, up to 255.'));
            $row->addNumber('options')->minimum(1)->maximum(255)->onlyInteger(true);
    }

    if ($values['type'] == 'text' || $values['type'] == 'editor') {
        $row = $form->addRow()->addClass('optionsRows');
            $row->addLabel('options', __('Rows'))->description(__('Number of rows for field.'));
            $row->addNumber('options')->minimum(1)->maximum(20)->onlyInteger(true);
    }

    if ($values['type'] == 'select' || $values['type'] == 'checkboxes' || $values['type'] == 'radio') {
        $row = $form->addRow()->addClass('optionsOptions');
            $row->addLabel('options', __('Options'))
                ->description(__('Comma separated list of options.'))
                ->description(__('Dropdown: use [] to create option groups.'));
            $row->addTextArea('options')->setRows(3)->required();
    }

    if ($values['type'] == 'file') {
        $row = $form->addRow()->addClass('optionsFile');
            $row->addLabel('options', __('File Type'))->description(__('Comma separated list of acceptable file extensions (with dot). Leave blank to accept any file type.'));
            $row->addTextField('options');
    }

    $row = $form->addRow();
        $row->addLabel('required', __('Required'))->description(__('Is this field compulsory?'));
        $row->addYesNo('required')->required();

    $row = $form->addRow();
        $row->addLabel('hidden', __('Hidden'))->description(__('Is this field hidden from profiles and user-facing pages?'));
        $row->addYesNo('hidden')->required();

    $isSecureFieldType = $values['type'] == 'editor' || $values['type'] == 'code';

    if ($values['context'] == 'User') {
        $form->addRow()->addHeading('Visibility', __('Visibility'));

        $activePersonOptions = array(
            'activePersonStudent' => __('Student'),
            'activePersonStaff'   => __('Staff'),
            'activePersonParent'  => __('Parent'),
            'activePersonOther'   => __('Other'),
        );
        $checked = array_intersect_key($values, $activePersonOptions);
        $checked = array_filter($checked);

        $row = $form->addRow();
            $row->addLabel('roleCategories', __('Role Categories'));
            $row->addCheckbox('roleCategories')->fromArray($activePersonOptions)->checked(array_keys($checked));

        if (!$isSecureFieldType) {
            $row = $form->addRow();
                $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
                $row->addSelect('activeDataUpdater')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();

            $row = $form->addRow();
                $row->addLabel('activeApplicationForm', __('Include In Application Form?'));
                $row->addSelect('activeApplicationForm')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();

            $enablePublicRegistration = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'enablePublicRegistration');
            if ($enablePublicRegistration == 'Y') {
                $row = $form->addRow();
                    $row->addLabel('activePublicRegistration', __('Include In Public Registration Form?'));
                    $row->addSelect('activePublicRegistration')->fromArray(array('1' => __('Yes'), '0' => __('No')))->selected('0')->required();
            }
        }
    } elseif ($values['context'] == 'Medical Form' && !$isSecureFieldType) {
        $row = $form->addRow();
            $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
            $row->addSelect('activeDataUpdater')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();
    } elseif ($values['context'] == 'Staff' && !$isSecureFieldType) {
        $row = $form->addRow();
            $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
            $row->addSelect('activeDataUpdater')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();
            
        $row = $form->addRow();
            $row->addLabel('activeApplicationForm', __('Include In Application Form?'));
            $row->addSelect('activeApplicationForm')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();
    }

    if ($isSecureFieldType && ($values['activeDataUpdater'] == 1 || $values['activeApplicationForm'] == 1 || $values['activePublicRegistration'] == 1)) {
        $row = $form->addRow()->addContent(Format::alert(__('For security reasons, Rich Text and Code fields cannot be used on public facing forms. These fields have been disabled.'), 'warning'));
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
