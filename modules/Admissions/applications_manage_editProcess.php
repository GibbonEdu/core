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
use Gibbon\Data\Validator;
use Gibbon\Module\Admissions\ApplicationBuilder;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['officeNotes' => 'HTML']);

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonAdmissionsApplicationID = $_POST['gibbonAdmissionsApplicationID'] ?? '';
$search = $_POST['search'] ?? '';
$officeOnly = $_POST['officeOnly'] ?? 'N';
$tab = $_POST['tab'] ?? 0;

$URL = Url::fromModuleRoute('Admissions', 'applications_manage_edit')->withQueryParams(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'search' => $search, 'tab' => $tab]);

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_edit.php') == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    // Get the application form data
    $application = $container->get(AdmissionsApplicationGateway::class)->getByID($gibbonAdmissionsApplicationID);
    if (empty($gibbonAdmissionsApplicationID) || empty($application)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Get the admissions account
    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $account = $admissionsAccountGateway->getByID($application['foreignTableID']);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Setup the builder class
    $formBuilder = $container->get(ApplicationBuilder::class)->populate($application['gibbonFormID'], -1, ['identifier' => $application['identifier'], 'accessID' => $account['accessID']])->includeHidden($officeOnly == 'Y');

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);

    $formBuilder->addConfig([
        'mode'           => 'edit',
        'foreignTableID' => $gibbonAdmissionsApplicationID,
        'identifier'     => $application['identifier'],
        'accessID'       => $account['accessID'],
        'accessToken'    => $account['accessToken'],
        'gibbonPersonID' => $account['gibbonPersonID'],
        'gibbonFamilyID' => $account['gibbonFamilyID'],
    ]);

    // Is this form running a set of processes?
    $processes = $_POST['applicationProcess'] ?? [];
    if (!empty($processes)) {
        // Extend the lifetime of any access tokens emailed to people
        $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], [
            'timestampTokenExpire' => date('Y-m-d H:i:s', strtotime("+2 days")),
        ]);

        // Setup which processes are going to run, based on user-selected checkboxes
        foreach ($processes as $processName => $process) {
            $formBuilder->addConfig(['mode' => 'process', $processName.'Enabled' => $process['enabled'] ?? 'N']);
            $formData->addResults($process['data'] ?? []);
        }

        // Run any edit-related processes
        $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
        $formProcessor->editForm($formBuilder, $formData);
        
        $formData->save($application['identifier']);
        $partialFail &= !empty($formProcessor->getErrors());

        header("Location: {$URL->withReturn($partialFail ? 'warning1' : 'success0')}");
        return;
    }

    // Acquire data and handle file uploads - on error, return to the current page
    $data = $officeOnly == 'Y' ? $formBuilder->acquireOfficeOnly() : $formBuilder->acquire();
    if (!$data) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Enable manually changing the status for non-accepted forms
    if (!empty($_POST['status']) && $application['status'] != 'Accepted') {
        $formData->setStatus($_POST['status']);
    }

    // Save data before validation, so users don't lose data?
    $formData->addData($data);
    $formData->save($application['identifier']);

    // Handle file uploads - on error, flag partial failures
    $uploaded = $formBuilder->upload();
    $partialFail &= !$uploaded;

    // Validate submitted data - on error, return to the current page
    $validated = $formBuilder->validate($data);
    if (!empty($validated)) {
        header("Location: {$URL->withReturn('warning4')->withQueryParam('invalid', implode(',', $validated))}");
        exit;
    }

    // Update the admissions account email, if there is none
    if (empty($account['email']) && $formData->has('parent1email')) {
        $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], [
            'email' => $formData->get('parent1email'),
        ]);
    }

    header("Location: {$URL->withReturn($partialFail ? 'warning1' : 'success0')}");
}
