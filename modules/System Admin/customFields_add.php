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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/customFields_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Custom Fields'), 'customFields.php')
        ->add(__('Add Custom Field'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/customFields_edit.php&gibbonCustomFieldID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $customFieldHandler = $container->get(CustomFieldHandler::class);
    $context = $_GET['context'] ?? '';

    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/customFields_addProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Basic Details', __('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('context', __('Context'));
        $row->addSelect('context')->fromArray($customFieldHandler->getContexts())->required()->placeholder()->selected($context);

    $form->toggleVisibilityByClass('contextCustom')->onSelect('context')->when('Custom');

    $row = $form->addRow()->addClass('contextCustom');
        $row->addLabel('contextName', __('Custom Context Name'))->description(__('Generally the same as the module name.'));
        $row->addTextField('contextName')->maxLength(60)->required()->setValue($_GET['contextName'] ?? '');

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this context.'));
        $row->addTextField('name')->maxLength(50)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'))->description(__('Displayed as smaller text next to the field name.'));
        $row->addTextField('description')->maxLength(255);

    $headings = $customFieldHandler->getHeadings();
    $contextHeadings = $contextCustom = [];
    $contextChained = array_reduce(array_keys($headings), function($group, $context) use (&$headings, &$contextHeadings, &$contextCustom) {
        foreach ($headings[$context] as $key => $value) {
            $contextHeadings[$key.'_'.$context] = $value;
            $group[$key.'_'.$context] = $context;
        }
        $contextHeadings['Custom_'.$context] = '['.__('Custom').']';
        $contextCustom[] = 'Custom_'.$context;
        $group['Custom_'.$context] = $context;
        return $group;
    }, []);
    
    $row = $form->addRow();
        $row->addLabel('heading', __('Heading'))->description(__('Optionally list this field under a heading.'));
        $row->addSelect('heading')
            ->fromArray($contextHeadings)
            ->chainedTo('context', $contextChained)
            ->placeholder();

    $form->toggleVisibilityByClass('headingCustom')->onSelect('heading')->when($contextCustom);
    
    $row = $form->addRow()->addClass('headingCustom');
        $row->addLabel('headingCustom', __('Custom Heading'));
        $row->addTextField('headingCustom')->maxLength(90);


    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $form->addRow()->addHeading('Configure', __('Configure'));

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($customFieldHandler->getTypes())->required()->placeholder();

    $form->toggleVisibilityByClass('optionsLength')->onSelect('type')->when(['varchar', 'number']);
    $row = $form->addRow()->addClass('optionsLength');
        $row->addLabel('options', __('Max Length'))->description(__('Number of characters, up to 255.'));
        $row->addNumber('options')->setID('optionsLength')->minimum(1)->maximum(255)->onlyInteger(true);

    $form->toggleVisibilityByClass('optionsRows')->onSelect('type')->when(['text', 'editor']);
    $row = $form->addRow()->addClass('optionsRows');
        $row->addLabel('options', __('Rows'))->description(__('Number of rows for field.'));
        $row->addNumber('options')->setID('optionsRows')->minimum(1)->maximum(20)->onlyInteger(true);

    $form->toggleVisibilityByClass('optionsOptions')->onSelect('type')->when(['select', 'checkboxes', 'radio']);
    $row = $form->addRow()->addClass('optionsOptions');
        $row->addLabel('options', __('Options'))
            ->description(__('Comma separated list of options.'))
            ->description(__('Dropdown: use [] to create option groups.'));
        $row->addTextArea('options')->setID('optionsOptions')->required()->setRows(3);

    $form->toggleVisibilityByClass('optionsFile')->onSelect('type')->when(['file']);
    $row = $form->addRow()->addClass('optionsFile');
        $row->addLabel('options', __('File Type'))->description(__('Comma separated list of acceptable file extensions (with dot). Leave blank to accept any file type.'));
        $row->addTextField('options');

    $row = $form->addRow();
        $row->addLabel('required', __('Required'))->description(__('Is this field compulsory?'));
        $row->addYesNo('required')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('hidden', __('Hidden'))->description(__('Is this field hidden from profiles and user-facing pages?'));
        $row->addYesNo('hidden')->required()->selected('N');

    $form->addRow()->addClass('contextPerson')->addHeading('Visibility', __('Visibility'));

    $form->toggleVisibilityByClass('contextPerson')->onSelect('context')->when('User');
    $form->toggleVisibilityByClass('contextDataUpdate')->onSelect('context')->when(['User', 'Medical Form', 'Staff']);
    $form->toggleVisibilityByClass('contextApplication')->onSelect('context')->when(['User', 'Staff']);

    $activePersonOptions = [
        'activePersonStudent' => __('Student'),
        'activePersonStaff'   => __('Staff'),
        'activePersonParent'  => __('Parent'),
        'activePersonOther'   => __('Other'),
    ];
    $row = $form->addRow()->addClass('contextPerson');
        $row->addLabel('roleCategories', __('Role Categories'));
        $row->addCheckbox('roleCategories')->fromArray($activePersonOptions)->checked('activePersonStudent');

    $row = $form->addRow()->addClass('contextDataUpdate');
        $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
        $row->addSelect('activeDataUpdater')->fromArray(['1' => __('Yes'), '0' => __('No')])->selected('1')->required();

    $row = $form->addRow()->addClass('contextApplication');
        $row->addLabel('activeApplicationForm', __('Include In Application Form?'));
        $row->addSelect('activeApplicationForm')->fromArray(['1' => __('Yes'), '0' => __('No')])->selected('0')->required();
    
    $enablePublicRegistration = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $row = $form->addRow()->addClass('contextPerson');
            $row->addLabel('activePublicRegistration', __('Include In Public Registration Form?'));
            $row->addSelect('activePublicRegistration')->fromArray(['1' => __('Yes'), '0' => __('No')])->selected('0')->required();
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
