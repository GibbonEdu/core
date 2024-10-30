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
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Storage\FormSessionStorage;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;

require_once '../../gibbon.php';

$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$identifier = $_REQUEST['identifier'] ?? null;
$pageNumber = $_REQUEST['page'] ?? 1;

$URL = Url::fromModuleRoute('System Admin', 'formBuilder_preview')->withQueryParams(['gibbonFormID' => $gibbonFormID, 'page' => $pageNumber, 'identifier' => $identifier]);

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    if (empty($gibbonFormID) || empty($pageNumber)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }
    
    // Setup the form data
    $formBuilder = $container->get(FormBuilder::class)->populate($gibbonFormID, $pageNumber, ['identifier' => $identifier]);
    $formData = $container->get(FormSessionStorage::class);
    $formData->load($identifier);

    // Acquire data and handle file uploads - on error, return to the current page
    $data = $formBuilder->acquire();
    if (!$data) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Save data before validation, so users don't lose data?
    $formData->addData($data);
    $formData->save($identifier);

    // Validate submitted data - on error, return to the current page
    $validated = $formBuilder->validate($data);
    if (!empty($validated)) {
        header("Location: {$URL->withReturn('error3')->withQueryParam('invalid', implode(',', $validated))}");
        exit;
    }

    // Determine how to handle the next page
    $formPageGateway = $container->get(FormPageGateway::class);
    $finalPageNumber = $formPageGateway->getFinalPageNumber($gibbonFormID);
    $nextPage = $formPageGateway->getNextPageByNumber($gibbonFormID, $pageNumber);
    $maxPage = max($nextPage['sequenceNumber'] ?? $pageNumber, $formData->get('maxPage') ?? 1);

    if ($pageNumber >= $finalPageNumber) {
        // Run the form processor on this data
        $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
        $formProcessor->submitForm($formBuilder, $formData);

        $formData->save($identifier);
        
        // Cleanup data, probably remove file uploads here
        $session->set('formpreview', []);

        $URL = $URL->withQueryParam('return', 'success0')->withQueryParam('page', $pageNumber+1);

    } elseif ($nextPage) {
        // Save data and proceed to the next page
        $formData->addData(['maxPage' => $maxPage]);
        $formData->save($identifier);

        $URL = $URL->withQueryParam('page', $nextPage['sequenceNumber']);
    }

    header("Location: {$URL}");
}
