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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Module\Admissions\ApplicationBuilder;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Module\Admissions\Forms\ApplicationMilestonesForm;
use Gibbon\Module\Admissions\Forms\ApplicationProcessForm;
use Gibbon\Module\Admissions\Tables\ApplicationUploadsTable;
use Gibbon\Module\Admissions\Tables\ApplicationDetailsTable;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_REQUEST['search'] ?? '';
    $tab = $_REQUEST['tab'] ?? 0;

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => $search])
        ->add(__('Edit Application'));

    $gibbonAdmissionsApplicationID = $_GET['gibbonAdmissionsApplicationID'] ?? '';
    $viewMode = $_GET['format'] ?? '';
    
    $urlParams = compact('gibbonAdmissionsApplicationID', 'gibbonSchoolYearID', 'search');

    // Get the application form data
    $application = $container->get(AdmissionsApplicationGateway::class)->getApplicationDetailsByID($gibbonAdmissionsApplicationID);
    if (empty($application)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Get the admissions account
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($application['foreignTableID']);
    if (empty($account)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Setup the form builder
    $formBuilder = $container->get(ApplicationBuilder::class)->populate($application['gibbonFormID'], 1, $urlParams + [
        'identifier' => $application['identifier'],
        'accessID' => $account['accessID'],
    ]);

    // Setup form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);

    // Add configuration values
    $formBuilder->addConfig($application);
    $formBuilder->addConfig(['foreignTableID' => $gibbonAdmissionsApplicationID]);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $formProcessor->editForm($formBuilder, $formData, true);
    $editProcesses = $formProcessor->getViewableProcesses();
    
    // Display any validation errors
    $errors = $formProcessor->verifyForm($formBuilder);
    $processes = $formProcessor->getViewableProcesses();
    foreach ($errors as $errorMessage) {
        echo Format::alert($errorMessage);
    }

    // Load values from the form data storage
    $values = $formData->getData();
    $values['username'] = $formData->get('username') ?? $formData->getResult('username');
    $values['studentID'] = $formData->get('studentID') ?? $formData->getResult('studentID');

    $incomplete = empty($application['status']) || $application['status'] == 'Incomplete';

    // Display form actions
    if (!empty($search)) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Admissions', 'applications_manage')->withQueryParams($urlParams));
    }
    
    $page->navigator->addHeaderAction('view', __('View'))
        ->setURL('/modules/Admissions/applications_manage_view.php')
        ->addParam('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
        ->displayLabel();

    $page->navigator->addHeaderAction('print', __('Print'))
        ->setURL('/report.php')
        ->addParam('q', '/modules/Admissions/applications_manage_view.php')
        ->addParam('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
        ->addParam('format', 'print')
        ->setTarget('_blank')
        ->directLink()
        ->displayLabel();

    // Display application details
    $detailsTable = $container->get(ApplicationDetailsTable::class)->createTable($formBuilder);
    echo $detailsTable->render([$application]);

    // Build the form
    $action = Url::fromHandlerRoute('modules/Admissions/applications_manage_editProcess.php');

    $formBuilder->addConfig(['mode' => 'office']);
    $officeForm = $formBuilder->includeHidden(true)->edit($action);
    $officeForm->addHiddenValue('officeOnly', 'Y');
    $officeForm->addHiddenValue('tab', 0);
    $officeForm->loadAllValuesFrom($values);

    $formBuilder->addConfig(['mode' => 'edit']);
    $editForm = $formBuilder->includeHidden(false)->edit($action);
    $editForm->addHiddenValue('tab', 2);
    $editForm->loadAllValuesFrom($values);

    // Build forms and tables for other tabs
    $milestonesForm = $container->get(ApplicationMilestonesForm::class)->createForm($urlParams, $application['milestones']);
    $formsTable = DataTable::create('')->withData([]);
    $uploadsTable = $container->get(ApplicationUploadsTable::class)->createTable($application['gibbonFormID'], $gibbonAdmissionsApplicationID);
    $processForm = $container->get(ApplicationProcessForm::class)->createForm($urlParams, $formBuilder, $editProcesses);

    // Display the results
    if ($application['status'] != 'Incomplete') {
        $resultsForm = Form::create('formBuilder', '');
                        
        foreach ($processes as $process) {
            if ($viewClass = $process->getViewClass()) {
                $view = $container->get($viewClass);
                $view->display($resultsForm, $formData);
            }
        }
    }

    // Display the tabbed view
    echo $page->fetchFromTemplate('application.twig.html', [
        'defaultTab'     => $tab,
        'officeForm'     => $officeForm,
        'editForm'       => $editForm,
        'milestonesForm' => $milestonesForm ?? null,
        'formsTable'     => $formsTable ?? null,
        'uploadsTable'   => $uploadsTable ?? null,
        'processForm'    => $processForm ?? null,
        'resultsForm'    => $resultsForm ?? null,
    ]);

}
