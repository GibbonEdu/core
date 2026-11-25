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

use Gibbon\Domain\System\LogGateway;
use Gibbon\Module\SystemAdmin\SystemUpdateProgress;
use Gibbon\View\View;

require_once __DIR__ . '/../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    echo __('Your request failed because you do not have access to this action.');
    exit;
}

$view = $container->get(View::class);
$unavailableMessage = __('Background updates are unavailable on this system.');

$renderUnavailable = static function () use ($view, $unavailableMessage): void {
    echo $view->fetchFromTemplate('updateProgress.twig.html', [
        'isDormant'      => false,
        'isPolling'      => false,
        'isFailure'      => true,
        'lifecycleLabel' => '',
        'typeLabel'      => '',
        'stageLabel'     => '',
        'message'        => $unavailableMessage,
        'progress'       => null,
    ]);
};

$gibbonLogID = $_GET['gibbonLogID'] ?? '';
if (!is_string($gibbonLogID) || preg_match('/^[0-9]+$/D', $gibbonLogID) !== 1) {
    $renderUnavailable();
    exit;
}

// gibbonLogID is an UNSIGNED INT with a ZEROFILL display width. Normalize
// the page-emitted value before its direct, exact gibbonLog lookup.
$gibbonLogID = ltrim($gibbonLogID, '0');
if (empty($gibbonLogID) || !ctype_digit($gibbonLogID)) {
    $renderUnavailable();
    exit;
}

$gibbonLog = $container->get(LogGateway::class)->getByID($gibbonLogID, ['gibbonLogID', 'title']);
if (empty($gibbonLog) || $gibbonLog['gibbonLogID'] != $gibbonLogID || $gibbonLog['title'] !== 'Background Process - SystemUpdateProcess') {
    $renderUnavailable();
    exit;
}

$progressService = $container->get(SystemUpdateProgress::class);
$progressSnapshot = $progressService->get();
$snapshotStatus = $progressSnapshot['status'];

if ($snapshotStatus === 'complete') {
    header('HX-Refresh: true');
    exit;
}

if (!in_array($snapshotStatus, ['working', 'failed'], true)) {
    $renderUnavailable();
    exit;
}

$isPolling = $snapshotStatus === 'working';
$isFailure = $snapshotStatus === 'failed';
$progress = $progressSnapshot['progress'];

if (!is_int($progress) || $progress < 0 || $progress > 100) {
    $progress = null;
}

echo $view->fetchFromTemplate('updateProgress.twig.html', [
    'isDormant'      => false,
    'isPolling'      => $isPolling,
    'pollUrl'        => $session->get('absoluteURL').'/modules/System Admin/updateProgressAjax.php?gibbonLogID=' . rawurlencode($gibbonLogID),
    'isFailure'      => $isFailure,
    'lifecycleLabel' => $progressService->getStatusLabel($snapshotStatus) ?? '',
    'typeLabel'      => $progressService->getTypeLabel($progressSnapshot['type']) ?? '',
    'stageLabel'     => $progressService->getStageLabel($progressSnapshot['stage']) ?? '',
    'message'        => $isFailure
        ? __('Update failed. Check the server logs and try again.')
        : '',
    'progress'      => $progress,
]);
