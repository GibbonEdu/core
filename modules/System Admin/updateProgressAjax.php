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

require_once __DIR__ . '/../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    echo __('Your request failed because you do not have access to this action.');
    exit;
}

$unavailableMessage = __('Background updates are unavailable on this system.');

/**
 * @param array{state: string, lifecycleLabel: string, typeLabel: string, stageLabel: string, message: string, progress: ?int} $response
 */
$respond = static function (array $response): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_THROW_ON_ERROR);
};

$respondUnavailable = static function () use ($respond, $unavailableMessage): void {
    $respond([
        'state'          => 'unavailable',
        'lifecycleLabel' => __('Unavailable'),
        'typeLabel'      => '',
        'stageLabel'     => '',
        'message'        => $unavailableMessage,
        'progress'       => null,
    ]);
};

$gibbonLogID = $_GET['gibbonLogID'] ?? '';
if (!is_string($gibbonLogID) || preg_match('/^[0-9]+$/D', $gibbonLogID) !== 1) {
    $respondUnavailable();
    exit;
}

// gibbonLogID is an UNSIGNED INT with a ZEROFILL display width. Normalize
// the page-emitted value before its direct, exact gibbonLog lookup.
$gibbonLogID = ltrim($gibbonLogID, '0');
if (empty($gibbonLogID) || !ctype_digit($gibbonLogID)) {
    $respondUnavailable();
    exit;
}

// Presence and title establish that this is a SystemUpdateProcess record. Do
// not inspect serialised process data: it may contain implementation details.
$gibbonLog = $container->get(LogGateway::class)->getByID($gibbonLogID, ['gibbonLogID', 'title']);
if (empty($gibbonLog) || $gibbonLog['gibbonLogID'] != $gibbonLogID || $gibbonLog['title'] !== 'Background Process - SystemUpdateProcess') {
    $respondUnavailable();
    exit;
}

$progressService = $container->get(SystemUpdateProgress::class);
$progressSnapshot = $progressService->get();
$progress = $progressSnapshot['progress'];

if (!is_int($progress) || $progress < 0 || $progress > 100) {
    $progress = null;
}

$typeLabel = $progressService->getTypeLabel($progressSnapshot['type']) ?? '';
$stageLabel = $progressService->getStageLabel($progressSnapshot['stage']) ?? '';

switch ($progressSnapshot['status']) {
    case 'working':
        $respond([
            'state'          => 'active',
            'lifecycleLabel' => $progressService->getStatusLabel('working') ?? '',
            'typeLabel'      => $typeLabel,
            'stageLabel'     => $stageLabel,
            'message'        => '',
            'progress'       => $progress,
        ]);
        break;

    case 'complete':
        $respond([
            'state'          => 'complete',
            'lifecycleLabel' => __('Complete'),
            'typeLabel'      => $typeLabel,
            'stageLabel'     => $stageLabel,
            'message'        => '',
            'progress'       => $progress,
        ]);
        break;

    case 'failed':
        $respond([
            'state'          => 'failed',
            'lifecycleLabel' => $progressService->getStatusLabel('failed') ?? '',
            'typeLabel'      => $typeLabel,
            'stageLabel'     => $stageLabel,
            'message'        => __('Update failed. Check the server logs and try again.'),
            'progress'       => $progress,
        ]);
        break;

    default:
        $respondUnavailable();
        break;
}
