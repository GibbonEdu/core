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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Admissions\Tables\ApplicationDetailsTable;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_accept.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => $search])
        ->add(__('Accept Application'));

    $gibbonAdmissionsApplicationID = $_GET['gibbonAdmissionsApplicationID'] ?? '';
    $viewMode = $_GET['format'] ?? '';
    $processed = $_GET['return'] ?? false;

    $page->return->addReturns([
        'error3'    => __('There was an error accepting the application form. The acceptance process was rolled back to a previous state. You can review the results below.'),
    ]);
    
    // Get the application form data and admissions account
    $application = $container->get(AdmissionsApplicationGateway::class)->getApplicationDetailsByID($gibbonAdmissionsApplicationID);
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($application['foreignTableID'] ?? '');
    if (empty($application) || empty($account)) {
        $page->addError(__('The selected application does not exist or has already been processed.'));
        return;
    }

    // Setup the form builder
    $formBuilder = $container->get(FormBuilder::class);
    $formBuilder->populate($application['gibbonFormID']);

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);

    // Use the processor to get a list of active functionality
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $formProcessor->acceptForm($formBuilder, $formData, true);
    $processes = $formProcessor->getViewableProcesses();

    if ($processed) {
        // Display the results
        $form = Form::create('formBuilder', '');
        $form->setTitle(__('Results'));

        // Display any processing errors
        if ($formData->hasResult('errors')) {
            $col = $form->addRow()->addColumn();
            foreach ($formData->getResult('errors') as $errorMessage) {
                $col->addContent(Format::alert($errorMessage));
            }
        }
        
        // Display the process results
        foreach ($processes as $process) {
            if ($viewClass = $process->getViewClass()) {
                $view = $container->get($viewClass);
                $view->display($form, $formData);
            }
        }

        echo $form->getOutput();
        return;
    }

    if ($application['status'] != 'Pending' && $application['status'] != 'Waiting List') {
        $page->addError(__('The selected application does not exist or has already been processed.'));
        return;
    }

    // Gather the potential processes that this acceptance action will run
    $processList = [];
    $processListInvalid = [];

    foreach ($processes as $process) {
        if (!$process->isEnabled($formBuilder)) continue;

        $viewClass = $process->getViewClass();
        if (empty($viewClass)) continue;

        $view = $container->get($viewClass);
        if (empty($view->getDescription())) continue; 

        if ($process->isVerified()) {
            $processList[] = $view->getDescription();
        } else {
            $processListInvalid[] = $view->getDescription();
        }
    }

    // Display application details
    $detailsTable = $container->get(ApplicationDetailsTable::class)->createTable();
    echo $detailsTable->render([$application]);

    // FORM
    $form = Form::create('application', $session->get('absoluteURL').'/modules/Admissions/applications_manage_acceptProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);
    $form->addHiddenValue('search', $search);

    $applicantName = Format::name('', $formData->get('preferredName', ''), $formData->get('surname', ''), 'Student');
    $entryYear = $container->get(SchoolYearGateway::class)->getByID($formData->get('gibbonSchoolYearIDEntry'), ['name', 'status']);

    $col = $form->addRow()->addColumn();
    
    if (!empty($entryYear) && $entryYear['status'] == 'Upcoming') {
        $col->addContent(Format::alert(__('Students and parents accepted to an upcoming school year will have their status set to "Expected", unless you choose to send a welcome email to them, in which case their status will be "Full".'), 'message').'<br/>');
    }

    $col->addContent(sprintf(__('Are you sure you want to accept the application for %1$s?'), $applicantName))->wrap('<b>', '</b>');

    // Notification options
    if ($formBuilder->hasConfig('acceptanceEmailStudentTemplate') && !$formData->has('gibbonPersonIDStudent')) {
        $col->addCheckbox('informStudent')
            ->description(__('Automatically inform <u>student</u> of Gibbon login details by email?'))
            ->setValue('Y')
            ->checked($formBuilder->getConfig('acceptanceEmailStudentDefault'))
            ->inline(true)
            ->setClass('ml-4');
    }

    if ($formBuilder->hasConfig('acceptanceEmailParentTemplate') && !$formData->has('gibbonPersonIDParent1')) {
        $col->addCheckbox('informParents')
            ->description(__('Automatically inform <u>parents</u> of their Gibbon login details by email?'))
            ->setValue('Y')
            ->checked($formBuilder->getConfig('acceptanceEmailParentDefault'))
            ->inline(true)
            ->setClass('ml-4');
    }

    // List active functionality
    if (!empty($processList)) {
        $col = $form->addRow()->addColumn();
        $col->addContent(__('The system will perform the following actions:'))->wrap('<i><u>', '</u></i>');
        $col->addContent(Format::list($processList, 'ol',));
    }

    // List invalid functionality
    if (!empty($processListInvalid)) {
        $col = $form->addRow()->addColumn();
        $col->addContent(__('The system does not have enough data to perform the following actions:'))->wrap('<span class="underline italic text-red-700 font-bold">', '</span>');
        $col->addContent(Format::list($processListInvalid, 'ol',));
    }

    //  List manual actions
    $manualActions = [];

    if (!$formData->has('gibbonSchoolYearIDEntry')) {
        $manualActions[] = __('Enrol the student in the relevant academic year.');
    }

    if (!$formData->has('gibbonFormGroupIDEntry')) {
        $manualActions[] = __('Enrol the student in the selected school year (as the student has not been assigned to a form group).');
    }

    if ($container->get(SettingGateway::class)->getSettingByScope('Timetable Admin', 'autoEnrolCourses') != 'Y') {
        $manualActions[] = __('Create a timetable for the student.');
    }
    
    // $manualActions[] = __('Create a note of the student\'s scholarship information outside of Gibbon.');
    // $manualActions[] = __('Inform the student and parents of their Gibbon login details (if this was not done automatically).');

    if (!empty($manualActions)) {
        $col = $form->addRow()->addColumn();
        $col->addContent(__('But you may wish to manually do the following:'))->wrap('<i><u>', '</u></i>');
        $col->addContent(Format::list($manualActions, 'ol',));
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit(__('Accept'));

    echo $form->getOutput();
 
    
}
