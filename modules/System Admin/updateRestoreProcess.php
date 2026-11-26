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
use Gibbon\Filesystem\Downloader;
use Gibbon\Filesystem\FileUpdater;
use Gibbon\Filesystem\Filesystem;
use Gibbon\Module\SystemAdmin\SystemUpdateMonitor;
use Gibbon\Module\SystemAdmin\SystemUpdateProcess;
use Gibbon\Module\SystemAdmin\SystemUpdateProgress;
use Gibbon\Services\BackgroundProcessor;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/update.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

// Require explicit POST values to ensure CSRF checking
if (($_POST['confirm'] ?? '') !== 'Y' || empty($_POST['address'])) {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
}

$settingGateway = $container->get(SettingGateway::class);
$absolutePath = $session->get('absolutePath');
$filesystem = $container->get(Filesystem::class);
$baseDir = $filesystem->getSecureDirectory($absolutePath, Downloader::BASE_DIR);
$lockPath = $baseDir.'/fileUpdate.lock';

if (!$filesystem->ensureDirectory($baseDir)) {
    $URL .= '&return=errorFilePermissions';
    header("Location: {$URL}");
    exit;
}

// Check the local backup holds the recorded version before accepting a restore request.
$backupVersion = (string) $settingGateway->getSettingByScope('System Admin', 'fileUpdaterBackup');
if (!$container->get(FileUpdater::class)->hasBackup($backupVersion)) {
    $URL .= '&return=errorNoBackup';
    header("Location: {$URL}");
    exit;
}

// Stale updates left behind by a crashed process are cleared by the monitor
if ($container->get(SystemUpdateMonitor::class)->isBusy()) {
    $URL .= '&return=errorUpdateBusy';
    header("Location: {$URL}");
    exit;
}

$backgroundProcessor = $container->get(BackgroundProcessor::class);

// Start the background process
try {
    $container->get(SystemUpdateProgress::class)->begin('restore');
    $gibbonLogID = $backgroundProcessor->startProcess(SystemUpdateProcess::class, 'run', ['restore']);
} catch (\Throwable $error) {
    error_log('updateRestoreProcess background start failed: '.$error->getMessage());

    try {
        $container->get(SystemUpdateProgress::class)->fail('preparing');
    } catch (\Throwable $progressError) {
        error_log('updateRestoreProcess progress failure-state error: '.$progressError->getMessage());
    }

    $URL .= '&return=errorUpdateFailed';
    header("Location: {$URL}");
    exit;
}

// Keep the initiating administrator's browser session valid after files change.
try {
    $data = [
        'gibbonSessionID' => session_id(),
        'gibbonPersonID' => $session->get('gibbonPersonID'),
        'sessionStatus' => 'Logged In',
        'timestampModified' => date('Y-m-d H:i:s'),
    ];
    $container->get(SessionGateway::class)->insertAndUpdate($data, $data);
} catch (\Throwable $error) {
    error_log('updateRestoreProcess session refresh failed: '.$error->getMessage());
}

if (($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true') {
    header('HX-Trigger: '.json_encode([
        'background-update-started' => ['gibbonLogID' => $gibbonLogID],
    ]));
    http_response_code(204);
    exit;
}

header("Location: {$URL}");
exit;
