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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

require_once '../../gibbon.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonAdmissionsApplicationID = $_REQUEST['gibbonAdmissionsApplicationID'] ?? '';
$search = $_REQUEST['search'] ?? '';

$URL = Url::fromModuleRoute('Admissions', 'applications_manage_accept')->withQueryParams(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'search' => $search]);

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_accept.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
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
    $formBuilder = $container->get(FormBuilder::class)->populate($application['gibbonFormID'], -1, ['identifier' => $application['identifier'], 'accessID' => $account['accessID']])->includeHidden();

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);
    
    // Link the application to this parent, if one exists (eg: created after the application)
    if (!$formData->has('gibbonPersonIDParent1') && !empty($account['gibbonPersonID'])) {
        $formData->set('gibbonPersonIDParent1', $account['gibbonPersonID']);
    }
    // Link the application to this account family, if one exists
    if (!$formData->has('gibbonFamilyID') && !empty($account['gibbonFamilyID'])) {
        $formData->set('gibbonFamilyID', $account['gibbonFamilyID']);
    }

    // Set the data in readonly mode, so all changes are recorded as results
    $formBuilder->addConfig(['foreignTableID' => $formData->identify($application['identifier'])]);
    $formData->setResults([]);
    $formData->setReadOnly(true);
    
    // Run any accept-related processes
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $formProcessor->acceptForm($formBuilder, $formData);

    // Handle errors
    if ($formProcessor->hasErrors()) {
        $formData->setResult('errors', $formProcessor->getErrors());
        $return = $formProcessor->getMode() == 'rollback' ? 'error3' : 'warning1';
    }

    // Save the final results of the acceptance
    $formData->save($application['identifier']);

    // Link the admissions account to new parent and family, if they were created during this acceptance process
    if ($formData->getStatus() == 'Accepted') {
        if ($formData->hasResult('familyCreated') && $formData->hasResult('gibbonFamilyID') && empty($account['gibbonFamilyID'])) {
            $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], [
                'gibbonFamilyID' => $formData->getResult('gibbonFamilyID'),
            ]);
        }
        if ($formData->hasResult('parent1created') && $formData->hasResult('gibbonPersonIDParent1') && empty($account['gibbonPersonID'])) {
            $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], [
                'gibbonPersonID' => $formData->getResult('gibbonPersonIDParent1'),
            ]);
        }
    }

    header("Location: {$URL->withReturn(!empty($return) ? $return : 'success0')}");
}
