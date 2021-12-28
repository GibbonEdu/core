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
use Gibbon\Forms\Builder\Processor\PreviewFormProcessor;
use Gibbon\Forms\Builder\FormData;

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

    // Setup the form data
    $formData = $container->get(FormData::class);
    $formData->populate($gibbonFormID, '');

    // Validate the form processes
    $formProcessor = $container->get(PreviewFormProcessor::class);

    $errors = $formProcessor->validate($formData);
    $processes = $formProcessor->getProcesses();

    $activeProcesses = array_filter($processes, function ($process) {
        return ($process['valid'] ?? false) == true;
    });
    $inactiveProcesses = array_diff_key($processes, $activeProcesses);
    

    if (!empty($activeProcesses)) {
        $form = Form::create('formsFunctionality', $session->get('absoluteURL').'/modules/System Admin/formBuilder_editFunctionalityProcess.php');

        $form->setTitle(__('Active Features'));
        $form->setDescription(__('Forms can have different functionality, depending on the type of form and the fields that have been added to the form. You can toggle and configure the available functionality below.'));
        
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonFormID', $gibbonFormID);

        foreach ($activeProcesses as $processDetails) {
            $view = $container->get($processDetails['view']);

            $view->configure($form);
        }

        $row = $form->addRow()->addClass('mt-4');
            $row->addSubmit();

        echo $form->getOutput();
    }


    if (!empty($inactiveProcesses)) {
        $form = Form::create('formsInactiveFunctionality', '');

        $form->setTitle(__('Inactive Features'));
        $form->setDescription(__('The following functionality is inactive because it depends on one or more fields that are not present in your form. You can add the required fields to your form to enable this functionality.'));

        foreach ($inactiveProcesses as $processName => $processDetails) {
            $process = $container->get($processDetails['process'] ?? '');
            $view = $container->get($processDetails['view']);

            $missingRequiredFields = array_filter($process->getRequiredFields(), function ($fieldName) use ($formData) {
                return !$formData->hasField($fieldName);
            });

            $row = $form->addRow()->addClass('bg-gray-300');
                $row->addLabel($processName, $view->getName())->description($view->getDescription());
                $row->addContent(__('Missing required fields: '). implode(', ', $missingRequiredFields));
        }

        echo $form->getOutput();
    }
}
