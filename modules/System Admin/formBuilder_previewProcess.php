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
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\Builder\FormData;
use Gibbon\Forms\Builder\Processor\PreviewFormProcessor;

require_once '../../gibbon.php';

$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$page = $_REQUEST['page'] ?? 1;

$URL = Url::fromModuleRoute('System Admin', 'formBuilder_preview')->withQueryParams(['gibbonFormID' => $gibbonFormID, 'page' => $page]);

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    
    // Setup the form data
    $formData = $container->get(FormData::class);
    $formData->populate($gibbonFormID, 'preview');

    // Get any submitted values, the lazy way
    $data = $_POST + $_FILES;

    $formData->save($data);

    $validated = $formData->validate($data);

    if (!$validated) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Determine how to handle the next page
    $formPageGateway = $container->get(FormPageGateway::class);
    $finalPageNumber = $formPageGateway->getFinalPageNumber($gibbonFormID);
    $nextPage = $formPageGateway->getNextPageByNumber($gibbonFormID, $page);
    $maxPage = max($nextPage['sequenceNumber'] ?? $page, $formData->get('maxPage') ?? 1);

    if ($page >= $finalPageNumber) {
        // Run the form processor on this data
        $formProcessor = $container->get(PreviewFormProcessor::class);
        $formProcessor->submitForm($formData);

        $session->set('formpreview', []);

        $URL = $URL
            ->withQueryParam('return', 'success0')
            ->withQueryParam('page', $page+1);

    } elseif ($nextPage) {
        $formData->save(['maxPage' => $maxPage]);
        $URL = $URL->withQueryParam('page', $nextPage['sequenceNumber']);
    }

    header("Location: {$URL}");
}
