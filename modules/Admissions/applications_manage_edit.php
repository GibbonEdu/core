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

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => $search])
        ->add(__('Edit Application'));

    $gibbonAdmissionsApplicationID = $_GET['gibbonAdmissionsApplicationID'] ?? '';
    $viewMode = $_GET['format'] ?? '';
    
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

    // Setup the form builder & data
    $formBuilder = $container->get(FormBuilder::class)->populate($application['gibbonFormID'], 1, ['identifier' => $application['identifier'], 'accessID' => $account['accessID']])->includeHidden();
    $formBuilder->addConfig($application);
    
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);
    $formBuilder->addConfig(['foreignTableID' => $formData->identify($application['identifier'])]);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $errors = $formProcessor->verifyForm($formBuilder);

    // Display any validation errors
    foreach ($errors as $errorMessage) {
        echo Format::alert($errorMessage);
    }

    // Load values from the form data storage
    $values = $formData->getData();
    $incomplete = empty($application['status']) || $application['status'] == 'Incomplete';

    // Build the form
    $action = Url::fromHandlerRoute('modules/Admissions/applications_manage_editProcess.php');

    $form = $formBuilder->edit($action);

    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $form->addHeaderAction('view', __('View'))
        ->setURL('/modules/Admissions/applications_manage_view.php')
        ->addParam('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
        ->append(' | ')
        ->displayLabel();

    $form->addHeaderAction('print', __('Print'))
        ->setURL('/report.php')
        ->addParam('q', '/modules/Admissions/applications_manage_view.php')
        ->addParam('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
        ->addParam('format', 'print')
        ->setTarget('_blank')
        ->directLink()
        ->displayLabel();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

}
