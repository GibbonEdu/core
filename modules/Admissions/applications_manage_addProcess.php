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
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['officeNotes' => 'HTML']);

$accessID = $_REQUEST['accessID'] ?? '';
$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$identifier = $_REQUEST['identifier'] ?? null;
$pageNumber = $_REQUEST['page'] ?? 1;

$URL = Url::fromModuleRoute('Admissions', 'applications_manage_add')->withQueryParams(['gibbonFormID' => $gibbonFormID, 'page' => $pageNumber, 'identifier' => $identifier, 'accessID' => $accessID]);

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_add.php') == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    if (empty($gibbonFormID) || empty($pageNumber)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    $partialFail = false;

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $account = $admissionsAccountGateway->getAccountByAccessID($accessID);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }
    
    // Setup the form data
    $formBuilder = $container->get(FormBuilder::class)->populate($gibbonFormID, $pageNumber, ['identifier' => $identifier, 'accessID' => $accessID]);
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($identifier);

    // Acquire data from POST - on error, return to the current page
    $data = $formBuilder->acquire();
    if (!$data) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Save data before validation, so users don't lose data?
    $formData->addData($data);
    $formData->save($identifier);

    // Add configuration data to the form, such as recently created IDs
    $formBuilder->addConfig([
        'mode'           => 'manual',
        'foreignTableID' => $formData->identify($identifier),
        'accessID'       => $accessID,
        'accessToken'    => $account['accessToken'],
        'gibbonPersonID' => $account['gibbonPersonID'] ?? null,
        'gibbonFamilyID' => $account['gibbonFamilyID'] ?? null,
    ]);

    // Handle file uploads - on error, flag partial failures
    $uploaded = $formBuilder->upload();
    $partialFail &= !$uploaded;

    // Validate submitted data - on error, return to the current page
    $validated = $formBuilder->validate($data);
    if (!empty($validated)) {
        header("Location: {$URL->withReturn('error3')->withQueryParam('invalid', implode(',', $validated))}");
        exit;
    }

    // Update the admissions account email, if there is none
    if (empty($account['email']) && $formData->has('parent1email')) {
        $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], [
            'email' => $formData->get('parent1email'),
        ]);
    }

    // Determine how to handle the next page
    $formPageGateway = $container->get(FormPageGateway::class);
    $finalPageNumber = $formPageGateway->getFinalPageNumber($gibbonFormID);
    $nextPage = $formPageGateway->getNextPageByNumber($gibbonFormID, $pageNumber);
    $maxPage = max($nextPage['sequenceNumber'] ?? $pageNumber, $formData->get('maxPage') ?? 1);

    if ($pageNumber >= $finalPageNumber) {
        // Do not run the submission processes, but do update the status manually
        $formData->setStatus('Pending');
        $formData->setResult('statusDate', date('Y-m-d H:i:s'));
        $formData->set('gibbonSchoolYearIDEntry', $formData->getAny('gibbonSchoolYearIDEntry') ?? $session->get('gibbonSchoolYearID'));

        $formData->save($identifier);

        $URL = $URL->withQueryParam('page', $pageNumber+1)->withReturn('success0');

    } elseif ($nextPage) {
        // Save data and proceed to the next page
        $formData->addData(['maxPage' => $maxPage]);
        $formData->save($identifier);

        $URL = $URL->withQueryParam('page', $nextPage['sequenceNumber'])->withReturn($partialFail ? 'warning1' : '');
    }

    header("Location: {$URL}");
}
