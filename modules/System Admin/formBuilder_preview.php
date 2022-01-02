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

use Gibbon\Services\Format;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\FormSessionStorage;
use Gibbon\Forms\Builder\Storage\FormDatabaseStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Domain\Forms\FormSubmissionGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Preview'));

    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
    $identifier = $_REQUEST['identifier'] ?? null;
    $pageNumber = $_REQUEST['page'] ?? 1;

    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    if (empty($identifier) && $pageNumber <= 1) {
        $identifier = $container->get(FormSubmissionGateway::class)->getNewUniqueIdentifier($gibbonFormID);
    }

    // Setup the form builder & data
    $formBuilder = $container->get(FormBuilder::class)->populate($gibbonFormID, $pageNumber, $identifier);
    // $formData = $container->get(FormSessionStorage::class);
    $formData = $container->get(FormDatabaseStorage::class)->setContext($formBuilder, 'preview', 1);
    $formData->load($identifier);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $errors = $formProcessor->verifyForm($formBuilder);

    // Display any validation errors
    foreach ($errors as $errorMessage) {
        echo Format::alert($errorMessage);
    }

    // Build the form
    $form = $formBuilder->build($session->get('absoluteURL').'/modules/System Admin/formBuilder_previewProcess.php');
    $form->setMaxPage($formData->get('maxPage') ?? $formBuilder->getPageNumber());
    
    // Load values from the form data storage
    $values = $formData->getData();
    $form->loadAllValuesFrom($values);

    // Display results?
    if ($formBuilder->getPageNumber() > $formBuilder->getFinalPageNumber()) {
        $processes = $formProcessor->getViewableProcesses();
        foreach ($processes as $process) {
            $viewClass = $process->getViewClass();
            if (empty($viewClass)) continue;

            $view = $container->get($viewClass);
            $view->display($form, $formData);
        }
    }

    echo $form->getOutput();
}
