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
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/customFields_edit.php&gibbonCustomFieldID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/customFields_addProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $contexts = [
        __('User Admin') => [
            'Person' => __('Person'),
        ],
    ];

    $row = $form->addRow();
        $row->addLabel('context', __('Context'));
        $row->addSelect('context')->fromArray($contexts)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this context.'));
        $row->addTextField('name')->maxLength(50)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $types = array(
        'varchar' => __('Short Text (max 255 characters)'),
        'text'    => __('Long Text'),
        'date'    => __('Date'),
        'url'     => __('Link'),
        'select'  => __('Dropdown')
    );
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->required()->placeholder();

    $form->toggleVisibilityByClass('optionsRow')->onSelect('type')->when(array('varchar', 'text', 'select'));

    $row = $form->addRow()->addClass('optionsRow');
        $row->addLabel('options', __('Options'))
            ->description(__('Short Text: number of characters, up to 255.'))
            ->description(__('Long Text: number of rows for field.'))
            ->description(__('Dropdown: comma separated list of options.'));
        $row->addTextArea('options')->setRows(3)->required();

    $row = $form->addRow();
        $row->addLabel('required', __('Required'))->description(__('Is this field compulsory?'));
        $row->addYesNo('required')->required();

    $form->toggleVisibilityByClass('contextPerson')->onSelect('context')->when('Person');
    $activePersonOptions = array(
        'activePersonStudent' => __('Student'),
        'activePersonStaff'   => __('Staff'),
        'activePersonParent'  => __('Parent'),
        'activePersonOther'   => __('Other'),
    );
    $row = $form->addRow()->addClass('contextPerson');
        $row->addLabel('roleCategories', __('Role Categories'));
        $row->addCheckbox('roleCategories')->fromArray($activePersonOptions)->checked('activePersonStudent');

    $row = $form->addRow()->addClass('contextPerson');
        $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
        $row->addSelect('activeDataUpdater')->fromArray(array('1' => __('Yes'), '0' => __('No')))->selected('1')->required();

    $row = $form->addRow()->addClass('contextPerson');
        $row->addLabel('activeApplicationForm', __('Include In Application Form?'));
        $row->addSelect('activeApplicationForm')->fromArray(array('1' => __('Yes'), '0' => __('No')))->selected('0')->required();
    
    $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $row = $form->addRow()->addClass('contextPerson');
            $row->addLabel('activePublicRegistration', __('Include In Public Registration Form?'));
            $row->addSelect('activePublicRegistration')->fromArray(array('1' => __('Yes'), '0' => __('No')))->selected('0')->required();
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
