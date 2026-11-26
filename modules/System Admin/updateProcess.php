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
use Gibbon\Database\Migrations\EngineUpdate;
use Gibbon\Database\Updater;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Module\SystemAdmin\SystemUpdateMonitor;
use Gibbon\Module\SystemAdmin\SystemUpdateProcess;
use Gibbon\Module\SystemAdmin\SystemUpdateProgress;
use Gibbon\Services\BackgroundProcessor;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/update.php';
$session->set('systemUpdateError', '');

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

$type = $_REQUEST['type'] ?? '';

// Validate Inputs
if ($type != 'regularRelease' && $type != 'cuttingEdge' && $type != 'InnoDB') {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
}

$updater = $container->get(Updater::class);

if ($type == 'regularRelease' || $type == 'cuttingEdge') {
    if (!$updater->isVersionValid()) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    if (!$updater->isUpdateRequired()) {
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
        $container->get(SystemUpdateProgress::class)->begin('database');
        $gibbonLogID = $backgroundProcessor->startProcess(SystemUpdateProcess::class, 'run', [$type]);
    } catch (\Throwable $error) {
        error_log('updateProcess background start failed: '.$error->getMessage());

        try {
            $container->get(SystemUpdateProgress::class)->fail('preparing');
        } catch (\Throwable $progressError) {
            error_log('updateProcess progress failure-state error: '.$progressError->getMessage());
        }

        $URL .= '&return=errorBackgroundUpdateUnavailable';
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
}

// Do InnoDB migration work synchronously.
$success = $container->get(EngineUpdate::class)->migrate();

$URL .= !$success
    ? '&return=error2'
    : '&return=success0';
header("Location: {$URL}");
