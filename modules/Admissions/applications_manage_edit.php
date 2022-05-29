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
use Gibbon\Services\Format;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\Forms\FormUploadGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;

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
    $application = $container->get(AdmissionsApplicationGateway::class)->getByID($gibbonAdmissionsApplicationID);
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
    $formBuilder = $container->get(FormBuilder::class)->populate($application['gibbonFormID'], 1, $urlParams + [
        'identifier' => $application['identifier'],
        'accessID' => $account['accessID'],
    ]);

    // Setup form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);

    // Add configuration values
    $formBuilder->addConfig($application);
    $formBuilder->addConfig([
        'foreignTableID' => $gibbonAdmissionsApplicationID,
        'mode' => 'edit',
    ]);

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
    $incomplete = empty($application['status']) || $application['status'] == 'Incomplete';

    // Load related documents
    $formUploadGateway = $container->get(FormUploadGateway::class);
    $criteria = $formUploadGateway->newQueryCriteria()->fromPOST();
    $uploads = $formUploadGateway->queryAllDocumentsByContext($criteria, 'gibbonAdmissionsApplication', $gibbonAdmissionsApplicationID);

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

    // Build the form
    $action = Url::fromHandlerRoute('modules/Admissions/applications_manage_editProcess.php');

    $officeForm = $formBuilder->includeHidden(true)->edit($action);
    $officeForm->addHiddenValue('officeOnly', 'Y');
    $officeForm->addHiddenValue('tab', 0);
    $officeForm->loadAllValuesFrom($values);

    $editForm = $formBuilder->includeHidden(false)->edit($action);
    $editForm->addHiddenValue('tab', 2);
    $editForm->loadAllValuesFrom($values);

    // Build the other forms table
    $formsTable = DataTable::create('')->withData([]);

    // Build the uploads table
    $uploadsTable = DataTable::createPaginated('applicationDocuments', $criteria)->withData($uploads);
    $uploadsTable->addColumn('status', __('Status'))->width('6%')->format(function($values) use (&$session, &$page) {
        $fileExists = file_exists($session->get('absolutePath').'/'.$values['path']);
        return $page->fetchFromTemplate('ui/icons.twig.html', [
            'icon' => $fileExists ? 'check' : 'cross',
            'iconClass' => 'w-6 h-6 fill-current mr-3 -my-2',
        ]);
        return Format::link($session->get('absoluteURL').'/'.$values['path'], $values['name'], ['target' => '_blank']);
    });
    $uploadsTable->addColumn('name', __('Document'))->format(function($values) use (&$session) {
        return Format::link($session->get('absoluteURL').'/'.$values['path'], $values['name'], ['target' => '_blank']);
    });
    $uploadsTable->addColumn('type', __('Type'));
    $uploadsTable->addColumn('timestamp', __('When'))->format(Format::using('relativeTime', 'timestamp'));

    $uploadsTable->addActionColumn()
        ->format(function ($values, $actions) use ($session) {
            if (!empty($values['path'])) {
                $actions->addAction('view', __('View'))
                    ->setExternalURL($session->get('absoluteURL').'/'.$values['path'])
                    ->directLink();

                $actions->addAction('export', __('Download'))
                    ->setExternalURL($session->get('absoluteURL').'/'.$values['path'], null, true)
                    ->directLink();
            }
        });

    // Build the edit process list
    if (!empty($processes)) {
        $processForm = Form::create('applicationProcess', $action);
        $processForm->addHiddenValues($urlParams);
        $processForm->addHiddenValue('tab', 5);

        foreach ($editProcesses as $index => $process) {
            if (!$process->isEnabled($formBuilder)) continue;

            $processForm->addHiddenValue('applicationProcess['.$process->getProcessName().'][class]', $process->getProcessName());

            if ($viewClass = $process->getViewClass()) {
                $view = $container->get($viewClass);
                $row = $processForm->addRow();
                    $row->addLabel('applicationProcess['.$process->getProcessName().'][enabled]', $view->getName())->description($view->getDescription());
                    $row->addCheckbox('applicationProcess['.$process->getProcessName().'][enabled]')->setValue('Y');
            }
        }
        $processForm->addRow()->addSubmit();
    }

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
        'defaultTab'   => $tab,
        'officeForm'   => $officeForm,
        'editForm'     => $editForm,
        'formsTable'   => $formsTable ?? null,
        'uploadsTable' => $uploadsTable ?? null,
        'processForm'  => $processForm ?? null,
        'resultsForm'  => $resultsForm ?? null,
    ]);

}
