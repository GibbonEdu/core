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
use Gibbon\Filesystem\VersionCatalog;
use Gibbon\Module\SystemAdmin\SystemUpdateMonitor;
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

// Two POST fields make gibbon.php check the CSRF token, so require confirm and address.
if (($_POST['confirm'] ?? '') !== 'Y' || empty($_POST['address'])) {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
}

// Only cutting edge installs on a Development version have a commit to check
$updater = $container->get(Updater::class);
if (!$updater->isCuttingEdge() || !$updater->isVersionValid() || $updater->isUpdateRequired() > 0) {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
}

// A newer version is offered as an update instead of a commit check
$versionCatalog = $container->get(VersionCatalog::class);
if ($versionCatalog->getNextRelease($updater->versionCode, true) !== null) {
    $URL .= '&return=error3';
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

try {
    $container->get(SystemUpdateProgress::class)->begin('cuttingEdgeCheck');
    $gibbonLogID = $backgroundProcessor->startProcess(SystemUpdateProcess::class, 'run', ['cuttingEdgeCheck']);
} catch (\Throwable $error) {
    error_log('updateCheckProcess background start failed: '.$error->getMessage());
    try {
        $container->get(SystemUpdateProgress::class)->fail('checking');
    } catch (\Throwable $progressError) {
        error_log('updateCheckProcess progress failure-state error: '.$progressError->getMessage());
    }
    $URL .= '&return=errorUpdateFailed';
    header("Location: {$URL}");
    exit;
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
