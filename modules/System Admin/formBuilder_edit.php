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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\Builder\FormData;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\FormBuilder;

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
    $formProcessorFactory = $container->get(FormProcessorFactory::class);
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

    // Check for existing submissions and warn about making changes
    $submissions = $formGateway->getSubmissionCountByForm($gibbonFormID);
    if ($submissions > 0) {
        $page->addAlert(Format::bold(__('Warning')).': '.__('This form is already in use. Changes to this form could affect the data for {count} existing submissions. Proceed with caution! If you are looking to make significant changes this form, it is safer to set it to inactive and create a new form, which will prevent changes that could affect your existing submissions.', ['count' => Format::bold($submissions)]), 'warning');
    }

    $form = Form::create('formsManage', $session->get('absoluteURL').'/modules/System Admin/formBuilder_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);

    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($formProcessorFactory->getFormTypes())->readonly();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(2);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'))->description(__('If yes, this form is open for new submissions'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('public', __('Public'))->description(__('If yes, members of the public can submit applications'));
        $row->addYesNo('public')->required();

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
    $pages = $formPageGateway->selectPagesByForm($gibbonFormID)->fetchGroupedUnique();

    // DATA TABLE
    $table = $container->get(DataTable::class);
    $table->setTitle(__('Pages'));
    $table->setDescription(__('A form can consist of one or more pages, which are saved at each step of the form submission. Add pages here and click design to add fields to the form.'));

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
            $actions->addAction('design', __('Design'))
                ->setIcon('markbook')
                ->setClass('mx-1')
                ->addParam('sidebar', 'false')
                ->setURL('/modules/System Admin/formBuilder_page_design.php');
                
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/System Admin/formBuilder_page_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/System Admin/formBuilder_page_delete.php')
                ->modalWindow(650, 350);
        });

    echo $table->render($pages);

    // FUNCTIONALITY

    // Setup the form builder
    $formBuilder = $container->get(FormBuilder::class);
    $formBuilder->populate($gibbonFormID);

    // Get the processor for this type of form
    $formProcessor = $formProcessorFactory->getProcessor($values['type']);

    // Verify the form processes
    $errors = $formProcessor->verifyForm($formBuilder, true);
    $processes = $formProcessor->getViewableProcesses();

    $activeProcesses = array_filter($processes, function ($process) {
        return $process->isVerified() == true && !empty($process->getRequiredFields());
    });
    $inactiveProcesses = array_filter(array_diff_key($processes, $activeProcesses), function ($process) {
        return !empty($process->getRequiredFields());
    });
    
    // Configure active functionality
    if (!empty($activeProcesses)) {
        $form = Form::create('formsFunctionality', $session->get('absoluteURL').'/modules/System Admin/formBuilder_editConfigProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        
        $form->setTitle(__('Active Features'));
        $form->setDescription(__('Forms can have different functionality, depending on the type of form and the fields that have been added to the form. You can toggle and configure the available functionality below.'));
        
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonFormID', $gibbonFormID);

        foreach ($activeProcesses as $process) {
            $viewClass = $process->getViewClass();
            if (empty($viewClass)) continue;

            $view = $container->get($viewClass);

            if (!empty($view->getHeading()) && !$form->hasHeading($view->getHeading())) {
                $form->addRow()->addHeading($view->getHeading(), __($view->getHeading()));
            }
            $view->configure($form);
        }

        $config = json_decode($values['config'], true);
        $form->loadAllValuesFrom($config);

        $row = $form->addRow()->addClass('mt-4');
            $row->addSubmit();

        echo $form->getOutput();
    }

    // Display requirements for inactive functionality
    if (!empty($inactiveProcesses)) {
        $form = Form::create('formsInactiveFunctionality', '');

        $form->setTitle(__('Inactive Features'));
        $form->setDescription(__('The following functionality is inactive because it depends on one or more fields that are not present in your form. You can add the required fields to your form to enable this functionality.'));

        foreach ($inactiveProcesses as $processName => $process) {
            $viewClass = $process->getViewClass();
            if (empty($viewClass)) continue;

            $view = $container->get($viewClass);

            $missingRequiredFields = array_filter($process->getRequiredFields(), function ($fieldName) use ($formBuilder) {
                return !$formBuilder->hasField($fieldName);
            });

            if (!$form->hasHeading($view->getHeading())) {
                $form->addRow()->addHeading($view->getHeading(), __($view->getHeading()))->addClass('bg-gray-400');
            }

            $row = $form->addRow()->addClass('bg-gray-300');
                $row->addLabel($processName, $view->getName())->description($view->getDescription());
                $col = $row->addColumn()->addClass('justify-start w-full sm:max-w-lg');
                $col->addContent(__('Required fields: '))->addClass('w-48');
                $col->addContent(implode(', ', $missingRequiredFields))->addClass('w-full');
        }

        echo $form->getOutput();
    }
}
