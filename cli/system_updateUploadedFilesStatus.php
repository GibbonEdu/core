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
use Gibbon\Domain\System\SettingGateway;

require getcwd().'/../gibbon.php';

// Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;

if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    print __('This script cannot be run from a browser, only via CLI.');
} else {
    $fileGateway = $container->get(FileGateway::class);

    // All gibbonFile records with no pointer — these are files uploaded via tinyMCE
    $stagedFiles = $fileGateway->selectNoPointerFiles()->fetchAll();

    if (empty($stagedFiles)) {
        print __("No uploaded files to process.");
        return;
    }

    $fileHandler = $container->get(FileHandler::class);
    $markedUnused = 0;
    $kept = 0;

    foreach ($stagedFiles as $file) {
        if ($fileHandler->isEditorFileUsed($file['filePath'], $file['gibbonFileID'])) {
            if ($file['isUsed'] === 'N') {
                $fileGateway->update($file['gibbonFileID'], ['isUsed' => 'Y']);
            }
            $kept++;
            continue;
        }

        if ($file['isUsed'] === 'N' || $fileGateway->markAsUnused($file['gibbonFileID'])) {
            $markedUnused++;
        }
    }

    echo sprintf("Uploaded files check complete. Checked %1\$s file(s): %2\$s marked as unused, %3\$s still in use.\n", count($stagedFiles), $markedUnused, $kept);
}
