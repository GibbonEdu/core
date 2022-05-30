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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;

require_once '../../gibbon.php';

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
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($application['foreignTableID']);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Setup the builder class
    $formBuilder = $container->get(FormBuilder::class)->populate($application['gibbonFormID'], -1, ['identifier' => $application['identifier'], 'accessID' => $account['accessID']])->includeHidden($officeOnly == 'Y');

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);
    $formBuilder->addConfig(['foreignTableID' => $formData->identify($application['identifier']), 'mode' => 'edit',]);

    $formBuilder->addConfig([
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
        // Setup which processes are going to run, based on user-selected checkboxes
        foreach ($processes as $processName => $process) {
            $formBuilder->addConfig([$processName.'Enabled' => $process['enabled'] ?? 'N']);
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
    $data = $formBuilder->acquire();
    if (!$data) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
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
        header("Location: {$URL->withReturn('error3')->withQueryParam('invalid', implode(',', $validated))}");
        exit;
    }

    header("Location: {$URL->withReturn($partialFail ? 'warning1' : 'success0')}");
}
