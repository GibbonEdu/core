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

use Gibbon\Contracts\Filesystem\FileHandler;
use Gibbon\Domain\System\FileGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/uploadedFiles_view.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/uploadedFiles_search.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $fileGateway = $container->get(FileGateway::class);

    // Get all gibbonFile records with no pointer — files uploaded via TinyMCE
    $stagedFiles = $fileGateway->selectNoPointerFiles()->fetchAll();

    if (empty($stagedFiles)) {
        $URL .= '&return=error5';
        header("Location: {$URL}");
        exit;
    }

    $fileHandler = $container->get(FileHandler::class);
    $partialFail = false;

    foreach ($stagedFiles as $file) {
        if ($fileHandler->isEditorFileUsed($file['filePath'], $file['gibbonFileID'])) {
            if ($file['isUsed'] === 'N' && !$fileGateway->update($file['gibbonFileID'], ['isUsed' => 'Y'])) {
                $partialFail = true;
            }
            continue;
        }

        if ($file['isUsed'] !== 'N') {
            $updated = $fileGateway->markAsUnused($file['gibbonFileID']);
            $partialFail = $partialFail || !$updated;
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
    exit;
}
