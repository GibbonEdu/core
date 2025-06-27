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

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\FormPrefill;
use Gibbon\Forms\Builder\FormPayment;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Admissions\ApplicationBuilder;

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
} else if (isActionAccessible($guid, $connection2, '/modules/Admissions/applicationForm.php') != false) {
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

    if (!$public) {
        $page->breadcrumbs
            ->add(__('My Application Forms'), 'applicationFormView.php', ['accessID' => $accessID])
            ->add(__('Application Form'));
    } elseif (!empty($accessID) && !empty($accessToken)) {
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

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $account = $admissionsAccountGateway->getAccountByAccessID($accessID);
    if (empty($account)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    if ($session->has('gibbonPersonID') && $account['gibbonPersonID'] != $session->get('gibbonPersonID')) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    if (empty($identifier) && $pageNumber <= 1) {
        $identifier = $admissionsApplicationGateway->getNewUniqueIdentifier($gibbonFormID);
    }

    if ($accountType == 'new') {
        echo Format::alert(__('A new admissions account has been created for {email}', ['email' => '<u>'.$account['email'].'</u>']).' '.__('You can access any application forms in progress using this email address through the admissions page.'), 'success');
    } else if ($accountType == 'existing') {
        echo Format::alert(__("We've found an existing admissions account for {email}. Your new application form will be attached to this account. Please check that this is your correct email, as any current forms will be accessible through this email address.", ['email' => '<u>'.$account['email'].'</u>']), 'message');
    }

    // Setup the form builder & data
    $formBuilder = $container->get(ApplicationBuilder::class)->populate($gibbonFormID, $pageNumber, ['identifier' => $identifier, 'accessID' => $accessID]);
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    
    $formData->load($identifier);
    $formBuilder->addConfig([
        'foreignTableID' => $formData->identify($identifier),
        'gibbonPersonID' => !$public ? $account['gibbonPersonID'] : '',
        'gibbonFamilyID' => !$public ? $account['gibbonFamilyID'] : '',
        'invalid' => !empty($_GET['invalid']) ? explode(',', $_GET['invalid']) : [],
    ]);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $errors = $formProcessor->submitForm($formBuilder, $formData, true);

    // Load values from the form data storage
    $values = $formData->getData();
    $values['secondParent'] = $formData->get('secondParent') ?? ($formData->has('parent2surname') ? 'No' : 'Yes');

    // Is the current application incomplete?
    $incomplete = empty($formData->getStatus()) || $formData->getStatus() == 'Incomplete';

    // Display form fee info
    $hasApplicationFee = $formBuilder->hasConfig('formSubmissionFee') || $formBuilder->hasConfig('formProcessingFee');
    if ($hasApplicationFee) {
        $formPayment = $container->get(FormPayment::class)->setForm($gibbonFormID);
        $page->return->addReturns(!$incomplete ? $formPayment->getReturnMessages() : []);
    }

    // Add page returns and javascript
    $page->return->addReturns($formBuilder->getReturns());

    // Prefill application form values
    if ($incomplete && !empty($account)) {
        $formPrefill = $container->get(FormPrefill::class)
            ->loadApplicationData($admissionsAccountGateway, $admissionsApplicationGateway, $gibbonFormID, $accessID, $accessToken)
            ->loadPersonalData($admissionsAccountGateway, $account['gibbonPersonID'], $accessID, $accessToken)
            ->prefill($formBuilder, $formData, $pageNumber, $values);

        if ($formPrefill->isPrefilled()) {
            echo Format::alert(__('Some information has been pre-filled for you, feel free to change this information as needed.'), 'message');
        }
    }

    // Has the form been completed?
    if ($incomplete && $formBuilder->getPageNumber() <= $formBuilder->getFinalPageNumber()) {
        $action = Url::fromHandlerRoute('modules/Admissions/applicationFormProcess.php');
        $pageUrl = Url::fromModuleRoute('Admissions', 'applicationForm');

        // Display fee info
        if ($hasApplicationFee) echo $formPayment->getFeeInfo();

        // Build the form
        $form = $formBuilder->build($action, $pageUrl);
        $form->setMaxPage($formData->get('maxPage') ?? $formBuilder->getPageNumber());
        $form->loadAllValuesFrom($values);

        $currentPage = $formBuilder->getCurrentPage();
        $form->getRenderer()->addData('introduction', $currentPage['introduction'] ?? '');
        $form->getRenderer()->addData('postScript', $currentPage['postScript'] ?? '');

        echo $form->getOutput();
        
    } else {
        // Display the results
        $form = Form::create('formBuilder', '');
        $form->setTitle(__('Results'));
                        
        $processes = $formProcessor->getViewableProcesses();
        
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

    echo $formBuilder->getJavascript();
}
