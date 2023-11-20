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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\FormPrefill;
use Gibbon\Forms\Builder\FormPayment;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_REQUEST['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => $search])
        ->add(__('Add Application'));

    $accessID = $_REQUEST['accessID'] ?? '';
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

    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    if (empty($identifier) && $pageNumber <= 1) {
        $identifier = $admissionsApplicationGateway->getNewUniqueIdentifier($gibbonFormID);
    }

    if ($accountType == 'new') {
        echo Format::alert(__('A new admissions account has been created for {email}', ['email' => '<u>'.$account['email'].'</u>']), 'success');
    }

    echo Format::alert(__('Manually creating and submitting an application form will bypass the normal emails, payments, and notifications that are sent upon submission. Most of these actions can be triggered afterwards through the Process tab in the Edit Application page.'), 'message');

    // Setup the form builder & data
    $formBuilder = $container->get(FormBuilder::class)->populate($gibbonFormID, $pageNumber, ['identifier' => $identifier, 'accessID' => $accessID]);
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    
    $formData->load($identifier);
    $formBuilder->addConfig([
        'foreignTableID' => $formData->identify($identifier),
        'gibbonPersonID' => $account['gibbonPersonID'] ?? null,
        'gibbonFamilyID' => $account['gibbonFamilyID'] ?? null,
        'invalid' => !empty($_GET['invalid']) ? explode(',', $_GET['invalid']) : [],
    ]);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $errors = $formProcessor->submitForm($formBuilder, $formData, true);

    // Load values from the form data storage
    $values = $formData->getData();
    $incomplete = empty($formData->getStatus()) || $formData->getStatus() == 'Incomplete';

    // Prefill application form values
    if ($incomplete && !empty($account)) {
        $formPrefill = $container->get(FormPrefill::class)
            ->loadApplicationData($admissionsAccountGateway, $admissionsApplicationGateway, $gibbonFormID, $accessID, $account['accessToken'])
            ->loadPersonalData($admissionsAccountGateway, $account['gibbonPersonID'], $accessID, $account['accessToken'])
            ->prefill($formBuilder, $formData, $pageNumber, $values);

        if ($formPrefill->isPrefilled()) {
            echo Format::alert(__('Some information has been pre-filled for you, feel free to change this information as needed.'), 'message');
        }
    }

    // Has the form been completed?
    if ($incomplete && $formBuilder->getPageNumber() <= $formBuilder->getFinalPageNumber()) {
        $action = Url::fromHandlerRoute('modules/Admissions/applications_manage_addProcess.php');
        $pageUrl = Url::fromModuleRoute('Admissions', 'applications_manage_add');

        // Build the form
        $form = $formBuilder->build($action, $pageUrl);
        $form->setMaxPage($formData->get('maxPage') ?? $formBuilder->getPageNumber());
        $form->loadAllValuesFrom($values);

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
