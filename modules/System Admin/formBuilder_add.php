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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Add Form'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder_edit.php&gibbonFormID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('formsManage', $session->get('absoluteURL').'/modules/System Admin/formBuilder_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading(__('Basic Information'));

    $types = [
        'Application'      => __('Application'),
    
        // These aren't unavailable until v25
        // 'Post-application' => __('Post-application'),
        // 'Student'          => __('Student'),
        // 'Parent'           => __('Parent'),
        // 'Family'           => __('Family'),
        // 'Staff'            => __('Staff'),
    ];
    
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(2);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->checkAll()->addCheckAllNone();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
