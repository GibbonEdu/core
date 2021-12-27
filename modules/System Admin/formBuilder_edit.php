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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormPageGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Edit Form'));

    $formGateway = $container->get(FormGateway::class);
    $formPageGateway = $container->get(FormPageGateway::class);
    $gibbonFormID = $_GET['gibbonFormID'] ?? '';

    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $formGateway->getByID($gibbonFormID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('formsManage', $session->get('absoluteURL').'/modules/System Admin/formBuilder_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);

    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $types = [
        'Application'      => __('Application'),
        'Post-application' => __('Post-application'),
        'Student'          => __('Student'),
        'Parent'           => __('Parent'),
        'Family'           => __('Family'),
        'Staff'            => __('Staff'),
    ];
    
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->required()->placeholder();
    
    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $values['gibbonYearGroupIDList'] = explode(',', $values['gibbonYearGroupIDList']);
    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

    // QUERY
    $criteria = $formPageGateway->newQueryCriteria()
        ->sortBy('sequenceNumber', 'ASC')
        ->fromPOST();

    $pages = $formPageGateway->queryPagesByForm($criteria, $gibbonFormID);

    // DATA TABLE
    $table = $container->get(DataTable::class);
    $table->setTitle(__('Pages'));
    $table->setDescription(__('A form can consist of one or more pages, which are saved at each step of the form submission. Add pages here and edit them to add fields to the form.'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/System Admin/formBuilder_page_add.php')
        ->addParam('gibbonFormID', $gibbonFormID)
        ->displayLabel();

    $draggableAJAX = $session->get('absoluteURL').'/modules/System Admin/formBuilder_editOrderAjax.php';
    $table->addDraggableColumn('gibbonFormPageID', $draggableAJAX, [
        'gibbonFormID' => $gibbonFormID,
    ]);

    $table->addColumn('name', __('Name'));
    $table->addColumn('count', __('Fields'));

    $table->addActionColumn()
        ->addParam('gibbonFormID', $gibbonFormID)
        ->addParam('gibbonFormPageID')
        ->format(function ($form, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->addParam('sidebar', 'false')
                ->setURL('/modules/System Admin/formBuilder_page_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/System Admin/formBuilder_page_delete.php');
        });

    echo $table->render($pages);

    // FUNCTIONALITY

    $form = Form::create('formsFunctionality', $session->get('absoluteURL').'/modules/System Admin/formBuilder_editFunctionalityProcess.php');
    // $form->setClass('blank mt-8');
    $form->setTitle(__('Functionality'));
    $form->setDescription(__('Forms can have different functionality, depending on the type of form and the fields that have been added to the form. You can toggle and configure the available functionality below.'));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);

    // $subform = Form::create('subform', '')->addClass('mt-4');

    $form->addRow()->addHeading(__('Application Form'));

    $row = $form->addRow();
        $row->addLabel('name1', __('Create New User'))->description(__('Must be unique'));
        $row->addYesNo('name1');

    $row = $form->addRow();
        $row->addLabel('name2', __('Create New Family'))->description(__('Must be unique'));
        $row->addYesNo('name2');

    $form->addRow()->addSubheading(__('Application Form'));

    $row = $form->addRow()->addClass('bg-gray-300');
        $row->addLabel('name3', __('Name'))->description(__('Must be unique'));
        $row->addContent(Format::small(__('Requires fields: Username, Surname, First Name')));

    $row = $form->addRow();
        $row->addLabel('name4', __('Name'))->description(__('Must be unique'));
        $row->addYesNo('name4');

    // $form->addRow()->addContent($subform->getOutput());



    $row = $form->addRow()->addClass('mt-4');
        $row->addSubmit();

    echo $form->getOutput();
}
