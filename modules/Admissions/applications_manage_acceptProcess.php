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
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonAdmissionsApplicationID = $_REQUEST['gibbonAdmissionsApplicationID'] ?? '';
$search = $_REQUEST['search'] ?? '';

$URL = Url::fromModuleRoute('Admissions', 'applications_manage_accept')->withQueryParams(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'search' => $search]);

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage.php') == false) {
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
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($application['foreignTableID']);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Setup the builder class
    $formBuilder = $container->get(FormBuilder::class)->populate($application['gibbonFormID'], -1, ['identifier' => $application['identifier'], 'accessID' => $account['accessID']])->includeHidden();

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($application['identifier']);
    $formData->setResults([]);
    $formData->setReadOnly(true);

    $formBuilder->addConfig(['foreignTableID' => $formData->identify($application['identifier'])]);
    
    // Run any accept-related processes
    $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
    $formProcessor->acceptForm($formBuilder, $formData);

    if ($formProcessor->hasErrors()) {
        $formData->setResult('errors', $formProcessor->getErrors());
        $formData->save($application['identifier']);

        header("Location: {$URL->withReturn('error3')}");
        exit;
    }

    // Save the status and results of the acceptance
    $formData->setStatus('Accepted');
    $formData->save($application['identifier']);

    header("Location: {$URL->withReturn('success0')}");
}
