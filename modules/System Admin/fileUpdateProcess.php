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

use Gibbon\Data\Validator;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Database\Updater;
use Gibbon\Filesystem\Downloader;
use Gibbon\Filesystem\VersionCatalog;
use Gibbon\Filesystem\FileUpdater;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/update.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $updater = $container->get(Updater::class);

    // Need a valid install, DB already up to date, and confirm checked
    if ($updater->isUpdateRequired() > 0 || ($_POST['confirm'] ?? '') != 'Y') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    // Working folder for zip, staging, backup, and lock
    $absolutePath = $session->get('absolutePath');
    $baseDir = $absolutePath.'/'.Downloader::BASE_DIR;
    $lockPath = $baseDir.'/fileUpdate.lock';

    $directoryReady = !empty($absolutePath) && is_dir($absolutePath);
    if ($directoryReady && !is_dir($baseDir)) {
        $directoryReady = mkdir($baseDir, 0755, true) || is_dir($baseDir);
    }

    if (!$directoryReady || !is_writable($baseDir)) {
        $URL .= '&return=errorFileDirectory';
        header("Location: {$URL}");
        exit;
    }

    // Stop two admins running update at the same time
    $lock = @fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        if ($lock !== false) {
            fclose($lock);
        }
        $URL .= '&return=errorFileBusy';
        header("Location: {$URL}");
        exit;
    }

    // Keep going even if the browser disconnects and allow enough time for download + replace
    ignore_user_abort(true);
    @set_time_limit(300);

    // Put the site into maintenance mode while files are replaced
    $settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', 'Y');
    $settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'running');

    $return = 'errorFiles';

    try {
        // Find the next version from the version catalog
        $release = $container->get(VersionCatalog::class)->getNextRelease($updater->versionCode, $updater->isCuttingEdge());
        if (empty($release) || empty($release['filePath'])) {
            throw new \RuntimeException(Downloader::ERROR_INVALID);
        }

        // Download, check the hash, and unzip into staging directory
        $files = $container->get(Downloader::class)->run($release);

        // Backup the old files, then copy staging files over the live install
        $container->get(FileUpdater::class)->run($files);

        // Clear caches so any new files are loaded
        removeDirectoryContents($absolutePath.'/uploads/cache');
        $session->forget('pageLoads');

        // Re-attach the admin session so the user stays logged in after files change
        $data = [
            'gibbonSessionID' => session_id(),
            'gibbonPersonID' => $session->get('gibbonPersonID'),
            'sessionStatus' => 'Logged In',
            'timestampModified' => date('Y-m-d H:i:s'),
        ];
        $container->get(SessionGateway::class)->insertAndUpdate($data, $data);

        $settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'idle');
        $return = 'success0';
    } catch (\Throwable $e) {
        // Save the failure stage for the update page message
        error_log('fileUpdateProcess: '.$e->getMessage());
        $session->set('fileUpdateStage', $e->getMessage());
        $settingGateway->updateSettingByScope('System Admin', 'fileUpdater', 'failed');
    } finally {
        // Clear staging and any leftover zip (backup folder is kept)
        removeDirectoryContents($baseDir.'/staging');
        if (is_file($baseDir.'/package.zip')) {
            unlink($baseDir.'/package.zip');
        }

        // Turn maintenance off and release the lock
        $settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', 'N');
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    // Return to the update page with success or failure
    $URL .= '&return='.$return;
    header("Location: {$URL}");
    exit;
}
