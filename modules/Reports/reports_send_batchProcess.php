<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Module\Reports\SendReportsProcess;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

require_once '../../gibbon.php';

$action = $_POST['action'] ?? [];
$gibbonReportID = $_POST['gibbonReportID'] ?? '';
$contextData = $_POST['contextData'] ?? '';
$identifiers = $_POST['identifier'] ?? [];

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reports_send_batch.php&gibbonReportID='.$gibbonReportID.'&contextData='.$contextData;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_send_batch.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportGateway = $container->get(ReportGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    // Validate the required data
    $identifiers = is_array($identifiers)? $identifiers : [$identifiers];
    if (empty($identifiers))  {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$reportGateway->exists($gibbonReportID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    foreach ($identifiers as $gibbonReportArchiveEntryID) {
        $reportArchiveEntryGateway->update($gibbonReportArchiveEntryID, ['timestampSent' => '0000-00-00 00:00:00']);
    }
    
    if ($action == 'parents') {
        $process = $container->get(SendReportsProcess::class);
        $success = $process->startSendReportsToParents($gibbonReportID, $identifiers);
    } elseif ($action == 'students') {
        $process = $container->get(SendReportsProcess::class);
        $success = $process->startSendReportsToStudents($gibbonReportID, $identifiers);
    } else {
        $success = false;
    }

    $URL .= !$success
        ? "&return=error2"
        : "&return=success5";

    header("Location: {$URL}");
}
