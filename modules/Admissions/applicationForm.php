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
use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Http\Url;

$proceed = false;
$public = false;

$settingGateway = $container->get(SettingGateway::class);

if (!$session->has('username')) {
    $public = true;

    //Get public access
    $publicApplications = $settingGateway->getSettingByScope('Application Form', 'publicApplications');
    if ($publicApplications == 'Y') {
        $proceed = true;
    }
} else if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm.php') != false) {
    $proceed = true;
}

$gibbonPersonID = $session->get('gibbonPersonID', null);

if ($proceed == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $accessID = $_REQUEST['accessID'] ?? '';
    $accessToken = $session->get('admissionsAccessToken') ?? '';

    if (!empty($accessID) && !empty($accessToken)) {
        $page->breadcrumbs
            ->add(__('My Application Forms'), '/modules/Admissions/applicationFormView.php', ['accessID' => $accessID])
            ->add(__('Application Form'));
    } else {
        $page->breadcrumbs
            ->add(__('Admissions Welcome'), '/modules/Admissions/applicationFormSelect.php')
            ->add(__('Application Form'));
    }
    
    $accountType = $_REQUEST['accountType'] ?? '';
    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
    $identifier = $_REQUEST['identifier'] ?? null;
    $pageNumber = $_REQUEST['page'] ?? 1;

    if (empty($accessID) || empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $account = $container->get(AdmissionsAccountGateway::class)->getAccountByAccessID($accessID);
    if (empty($account)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    if (empty($identifier) && $pageNumber <= 1) {
        $identifier = $admissionsApplicationGateway->getNewUniqueIdentifier($gibbonFormID);
    }

    if ($accountType == 'new') {
        echo Format::alert(__('A new admissions account has been created for {email}. You can access any application forms in progress using this email address through the admissions page.', ['email' => '<u>'.$account['email'].'</u>']), 'success');
    } else if ($accountType == 'existing') {
        echo Format::alert(__("We've found an existing admissions account for {email}. Your new application form will be attached to this account. Please check that this is your email address, as a copy of all submitted data will be sent to this address.", ['email' => '<u>'.$account['email'].'</u>']), 'message');
    }

    // Setup the form builder & data
    $formBuilder = $container->get(FormBuilder::class)->populate($gibbonFormID, $pageNumber, ['identifier' => $identifier, 'accessID' => $accessID]);
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($identifier);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $errors = $formProcessor->verifyForm($formBuilder);

    // Display any validation errors
    foreach ($errors as $errorMessage) {
        echo Format::alert($errorMessage);
    }

    // Load values from the form data storage
    $values = $formData->getData();
    $incomplete = empty($values['status']) || $values['status'] == 'Incomplete';

    // Prefill values? WIP
    if (empty($values['status']) && !empty($account)) {
        $recentApplication = $admissionsApplicationGateway->selectMostRecentApplicationByContext($gibbonFormID, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'])->fetch();

        if (!empty($recentApplication)) {
            $data = json_decode($recentApplication['data'] ?? '', true);
            foreach ($data as $fieldName => $value) {
                $field = $formBuilder->getField($fieldName);
                if (empty($field['prefill']) || $field['prefill'] == 'N') continue;

                $values[$fieldName] = $value;
            }
        }

    }

    // Has the form been completed?
    if ($incomplete && $formBuilder->getPageNumber() <= $formBuilder->getFinalPageNumber()) {
        $action = Url::fromHandlerRoute('modules/Admissions/applicationFormProcess.php');
        $pageUrl = Url::fromModuleRoute('Admissions', 'applicationForm');

        // Build the form
        $form = $formBuilder->build($action, $pageUrl);
        $form->setMaxPage($formData->get('maxPage') ?? $formBuilder->getPageNumber());
        $form->loadAllValuesFrom($values);

        echo $form->getOutput();

    } else {
        // Display the results
        $form = Form::create('formBuilder', '');
        $form->setTitle(__('Results'));
                        
        $processes = $formProcessor->getViewableProcesses(true, false, false);
        foreach ($processes as $process) {
            if ($viewClass = $process->getViewClass()) {
                $view = $container->get($viewClass);
                $view->display($form, $formData);
            }
        }

        echo $form->getOutput();

        // Display the submitted data
        $table = $formBuilder->display();
        echo $table->render([$values]);
    }
}
