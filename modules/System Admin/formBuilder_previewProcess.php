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
use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Forms\Builder\Processor\PreviewFormProcessor;

require_once '../../gibbon.php';

$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$page = $_REQUEST['page'] ?? 1;

$URL = Url::fromModuleRoute('System Admin', 'formBuilder_preview')->withQueryParams(['gibbonFormID' => $gibbonFormID, 'page' => $page]);

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $formPageGateway = $container->get(FormPageGateway::class);

    // Setup the form processor
    $formProcessor = $container->get(PreviewFormProcessor::class);
    $formProcessor->setForm($gibbonFormID, 'preview');

    // Save any submitted values, the lazy way
    $formProcessor->saveData($_POST + $_FILES);

    // Determine how to handle the next page
    $finalPageNumber = $formPageGateway->getFinalPageNumber($gibbonFormID);
    $nextPage = $formPageGateway->getNextPageByNumber($gibbonFormID, $page);

    if ($page >= $finalPageNumber) {
        $formProcessor->submitProcess();

        $session->set('formpreview', []);

        $URL = $URL->withQueryParam('return', 'success0');
        $URL = $URL->withQueryParam('page', $page+1);
    } elseif ($nextPage) {
        $URL = $URL->withQueryParam('page', $nextPage['sequenceNumber']);
    }

    header("Location: {$URL}");
}
