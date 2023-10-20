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

use Gibbon\Services\Format;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Tables\Renderer\SpreadsheetRenderer;
use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => $search])
        ->add(__('View & Print Application'));

    $gibbonAdmissionsApplicationID = $_GET['gibbonAdmissionsApplicationID'] ?? '';
    $viewMode = $_GET['format'] ?? '';

    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    $application = $admissionsApplicationGateway->getByID($gibbonAdmissionsApplicationID);

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
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);
    $formBuilder->addConfig(['foreignTableID' => $gibbonAdmissionsApplicationID]);

    // Verify the form
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $errors = $formProcessor->verifyForm($formBuilder);

    // Display any validation errors
    foreach ($errors as $errorMessage) {
        echo Format::alert($errorMessage);
    }

    // Display a message for incomplete applications
    if ($application['status'] == 'Incomplete') {
        echo Format::alert(__('This application form was created by {email} and is still in progress. It has not been submitted yet.', ['email' => '<u>'.$account['email'].'</u>']), 'warning');
    }
    
    // Display the submitted data
    $table = $formBuilder->display();

    if ($viewMode == 'print') {
        $table->addHeaderAction('print', __('Print'))
            ->onClick('javascript:window.print(); return false;')
            ->setURL('#')
            ->displayLabel();
        $table->addMetaData('hidePagination', true);
    } else {
        $table->addHeaderAction('print', __('Print'))
            ->setURL('/report.php')
            ->addParam('q', '/modules/Admissions/applications_manage_view.php')
            ->addParam('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
            ->addParam('format', 'print')
            ->setTarget('_blank')
            ->directLink()
            ->displayLabel();
    }

    if ($viewMode == 'export') {
        $table->setRenderer(new SpreadsheetRenderer($session->get('absolutePath')));
        $table->addMetaData('filename', 'gibbonExport_'.$gibbonAdmissionsApplicationID);
        $table->addMetaData('creator', Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff'));

    } else {
        $table->addHeaderAction('export', __('Export'))
            ->setURL('/export.php')
            ->addParam('q', '/modules/Admissions/applications_manage_view.php')
            ->addParam('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID)
            ->addParam('format', 'export')
            ->setTarget('_blank')
            ->prepend(' | ')
            ->directLink()
            ->displayLabel();
    }

    echo $table->render([$formData->getData()]);
}
