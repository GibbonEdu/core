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

use Gibbon\Services\Format;
use Gibbon\Module\Reports\SendNotificationsProcess;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$type = $_POST['type'] ?? '';
$gibbonReportingCycleIDList = !empty($_POST['gibbonReportingCycleIDList'])? explode(',', $_POST['gibbonReportingCycleIDList']) : [];
$notificationText = $_POST['notificationText'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/notification_send.php&type='.$type;

if (isActionAccessible($guid, $connection2, '/modules/Reports/notification_send.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($type) || empty($gibbonReportingCycleIDList)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $process = $container->get(SendNotificationsProcess::class);
    $success = false;

    if ($type == 'proofReadingEdits') {
        $success = $process->startSendProofReadingEdits($gibbonReportingCycleIDList, $notificationText);
    } elseif ($type == 'reportsAvailable') {
        $success = $process->startSendReportsAvailable($gibbonReportingCycleIDList, $notificationText);
    }
    
    $URL .= !$success
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
