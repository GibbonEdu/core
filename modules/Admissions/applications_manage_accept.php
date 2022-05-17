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
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php')
        ->add(__('Accept Application'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $gibbonAdmissionsApplicationID = $_GET['gibbonAdmissionsApplicationID'] ?? '';
    $search = $_GET['search'] ?? '';
    $viewMode = $_GET['format'] ?? '';
    $processed = $_GET['return'] ?? false;

    $page->return->addReturns([
        'error3'    => __('There was an error accepting the application form. The acceptance process was rolled back to a previous state. You can review the results below.'),
    ]);
    
    // Get the application form data and admissions account
    $application = $container->get(AdmissionsApplicationGateway::class)->getByID($gibbonAdmissionsApplicationID);
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($application['foreignTableID'] ?? '');
    if (empty($application) || empty($account)) {
        $page->addError(__('The selected application does not exist or has already been processed.'));
        return;
    }

    // Setup the form builder
    $formBuilder = $container->get(FormBuilder::class);
    $formBuilder->populate($application['gibbonFormID']);

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);

    // Use the processor to get a list of active functionality
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $formProcessor->acceptForm($formBuilder, $formData, true);
    $processes = $formProcessor->getViewableProcesses();
    $processList = [];

    $errors = $formProcessor->getErrors();

    // Display any validation errors
    foreach ($errors as $errorMessage) {
        echo Format::alert($errorMessage);
    }

    foreach ($processes as $process) {
        if (!$process->isEnabled($formBuilder)) continue;

        $viewClass = $process->getViewClass();
        if (empty($viewClass)) continue;

        $view = $container->get($viewClass);
        $processList[] = $view->getDescription();
    }

    // Load values from the form data storage
    $values = $formData->getData();
    $results = $formData->getResults();

    if ($processed) {
        // Display the results
        $form = Form::create('formBuilder', '');
        $form->setTitle(__('Results'));

        // Display any validation errors
        if (!empty($results['errors'])) {
            $col = $form->addRow()->addColumn();
            foreach ($results['errors']  as $errorMessage) {
                $col->addContent(Format::alert($errorMessage));
            }
        }
                        
        foreach ($processes as $process) {
            if ($viewClass = $process->getViewClass()) {
                $view = $container->get($viewClass);
                $view->display($form, $formData);
            }
        }

        echo $form->getOutput();
        return;
    }

    if ($application['status'] != 'Pending') {
        $page->addError(__('The selected application does not exist or has already been processed.'));
        return;
    }

    // FORM
    $form = Form::create('application', $session->get('absoluteURL').'/modules/Admissions/applications_manage_acceptProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);
    $form->addHiddenValue('search', $search);

    $applicantName = Format::name('', $values['preferredName'], $values['surname'], 'Student');

    $col = $form->addRow()->addColumn();
    $col->addContent(sprintf(__('Are you sure you want to accept the application for %1$s?'), $applicantName))->wrap('<b>', '</b>');

    // List active functionality
    if (!empty($processList)) {
        $col = $form->addRow()->addColumn();
        $col->addContent(__('The system will perform the following actions:'))->wrap('<i><u>', '</u></i>');
        $col->addContent(Format::list($processList, 'ol',));
    }

    //  List manual actions
    if (false) {
        $col = $form->addRow()->addColumn();
        $col->addContent(__('But you may wish to manually do the following:'))->wrap('<i><u>', '</u></i>');
        $list = $col->addContent();

        if (empty($values['gibbonFormGroupID'])) {
            $list->append('<li>'.__('Enrol the student in the selected school year (as the student has not been assigned to a form group).').'</li>');
        }

        $list->append('<li>'.__('Create an individual needs record for the student.').'</li>')
            ->append('<li>'.__('Create a note of the student\'s scholarship information outside of Gibbon.').'</li>')
            ->append('<li>'.__('Create a timetable for the student.').'</li>');

        $list->wrap('<ol>', '</ol>');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit(__('Accept'));

    echo $form->getOutput();
 
    
}
