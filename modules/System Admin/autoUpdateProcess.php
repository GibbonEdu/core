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
use Gibbon\Database\Updater as DatabaseUpdater;
use Gibbon\Filesystem\Downloader;
use Gibbon\Filesystem\VersionCatalog;
use Gibbon\Filesystem\Updater as FilesystemUpdater;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

require_once __DIR__ . '/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'] ?? '').'/update.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

$settingGateway = $container->get(SettingGateway::class);
$databaseUpdater = $container->get(DatabaseUpdater::class);

// Stable: Legacy/Current only. Cutting Edge include Development version
if (!$databaseUpdater->isVersionValid() || $databaseUpdater->isUpdateRequired() || ($_POST['confirm'] ?? '') != 'Y') {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
}

$absolutePath = rtrim(str_replace('\\', '/', (string) $session->get('absolutePath')), '/');
$baseDir = $absolutePath.'/'.Downloader::BASE_DIR;
$lockPath = $baseDir.'/autoUpdate.lock';

if ($absolutePath == '' || !is_dir($absolutePath) || (!is_dir($baseDir) && !@mkdir($baseDir, 0755, true)) || !is_writable($baseDir)) {
    $URL .= '&return=errorFiles';
    header("Location: {$URL}");
    exit;
}

$lock = @fopen($lockPath, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    if ($lock !== false) {
        fclose($lock);
    }
    $URL .= '&return=error10';
    header("Location: {$URL}");
    exit;
}

ignore_user_abort(true);
@set_time_limit(300);

$settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', 'Y');
$settingGateway->updateSettingByScope('System Admin', 'autoUpdater', 'running');

$return = 'errorFiles';

try {
    $release = $container->get(VersionCatalog::class)->getNextRelease($databaseUpdater->versionCode, $databaseUpdater->isCuttingEdge());
    if ($release === null || empty($release['filePath'])) {
        throw new \RuntimeException(Downloader::ERROR_INVALID);
    }

    // Non-CE must never apply a Development package even if the catalog entry was altered.
    $status = strtolower((string) ($release['status'] ?? ''));
    if (!$databaseUpdater->isCuttingEdge() && $status == 'development') {
        throw new \RuntimeException(Downloader::ERROR_INVALID);
    }

    // Download the new files
    $files = $container->get(Downloader::class)->run($release);

    // Replace the old files in the server with the new ones
    $container->get(FilesystemUpdater::class)->run($files);

    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    removeDirectoryContents($absolutePath.'/uploads/cache');
    $session->forget('pageLoads');

    // Keep the admin session attached after file replacement
    $data = [
        'gibbonSessionID' => session_id(),
        'gibbonPersonID' => $session->get('gibbonPersonID'),
        'sessionStatus' => 'Logged In',
        'timestampModified' => date('Y-m-d H:i:s'),
    ];
    $container->get(SessionGateway::class)->insertAndUpdate($data, $data);

    $settingGateway->updateSettingByScope('System Admin', 'autoUpdater', 'idle');
    $return = 'success0';
} catch (\Throwable $e) {
    error_log('autoUpdateProcess: '.$e->getMessage());
    $settingGateway->updateSettingByScope('System Admin', 'autoUpdater', 'failed');
} finally {
    $settingGateway->updateSettingByScope('System Admin', 'maintenanceMode', 'N');
    flock($lock, LOCK_UN);
    fclose($lock);
}

$URL .= '&return='.$return;
header("Location: {$URL}");
exit;
