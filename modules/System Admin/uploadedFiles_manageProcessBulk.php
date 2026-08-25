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

use Gibbon\Domain\System\FileGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$action         = $_POST['action'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFileIDs  = $_POST['gibbonFileID'] ?? [];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/uploadedFiles_manage.php&gibbonPersonID='.$gibbonPersonID;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/uploadedFiles_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

if (empty($action) || empty($gibbonPersonID) || empty($gibbonFileIDs)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$fileGateway = $container->get(FileGateway::class);
$partialFail = false;

if ($action == 'Delete') {
    foreach ($gibbonFileIDs as $gibbonFileID) {
        $file = $fileGateway->getByID($gibbonFileID);

        // Skip if not found or still in use
        if (empty($file) || $file['isUsed'] !== 'N') {
            $partialFail = true;
            continue;
        }

        // Delete the physical file from server
        $absoluteFilePath = $session->get('absolutePath').'/'.$file['filePath'];
        if (file_exists($absoluteFilePath)) {
            unlink($absoluteFilePath);
        }

        // Delete the database record
        if (!$fileGateway->delete($gibbonFileID)) {
            $partialFail = true;
        }
    }
}

$URL .= $partialFail ? '&return=warning1' : '&return=success0';
header("Location: {$URL}");
exit;
