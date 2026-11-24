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
use Gibbon\Database\Updater;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Filesystem\Downloader;
use Gibbon\Module\SystemAdmin\SystemUpdateProcess;
use Gibbon\Module\SystemAdmin\SystemUpdateProgress;
use Gibbon\Services\BackgroundProcessor;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/update.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

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

$backgroundProcessor = $container->get(BackgroundProcessor::class);
$processID = $backgroundProcessor->getProcessIDByName('SystemUpdateProcess');
$process = !empty($processID) ? $backgroundProcessor->getProcess($processID) : [];
if (in_array($process['status'] ?? '', ['Ready', 'Running'], true)) {
    $URL .= '&return=errorUpdateBusy';
    header("Location: {$URL}");
    exit;
}

// Retain the existing busy precondition without holding the lock needed by the worker.
$lock = @fopen($lockPath, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    if ($lock !== false) {
        fclose($lock);
    }

    $URL .= '&return=errorFileBusy';
    header("Location: {$URL}");
    exit;
}

flock($lock, LOCK_UN);
fclose($lock);

try {
    $container->get(SystemUpdateProgress::class)->begin('file');
    $backgroundProcessor->startProcess(SystemUpdateProcess::class, 'run', ['file'], false);
} catch (\Throwable $error) {
    error_log('updateFilesProcess background start failed: '.$error->getMessage());

    try {
        $container->get(SystemUpdateProgress::class)->fail('preparing');
    } catch (\Throwable $progressError) {
        error_log('updateFilesProcess progress failure-state error: '.$progressError->getMessage());
    }

    $URL .= '&return=errorBackgroundUpdateUnavailable';
    header("Location: {$URL}");
    exit;
}

// Re-attach the initiating browser session so the user stays logged in after files change.
try {
    $data = [
        'gibbonSessionID' => session_id(),
        'gibbonPersonID' => $session->get('gibbonPersonID'),
        'sessionStatus' => 'Logged In',
        'timestampModified' => date('Y-m-d H:i:s'),
    ];
    $container->get(SessionGateway::class)->insertAndUpdate($data, $data);
} catch (\Throwable $error) {
    error_log('updateFilesProcess session refresh failed: '.$error->getMessage());
}

header("Location: {$URL}");
exit;
