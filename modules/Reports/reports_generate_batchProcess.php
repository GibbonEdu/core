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

use Gibbon\Services\Format;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\GenerateReportProcess;

require_once '../../gibbon.php';

$gibbonReportID = $_POST['gibbonReportID'] ?? '';
$contextData = $_POST['contextData'] ?? '';
$status = $_POST['status'] ?? 'Draft';
$twoSided = $_POST['twoSided'] ?? 'N';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reports_generate_batch.php&gibbonReportID='.$gibbonReportID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_generate_batch.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportGateway = $container->get(ReportGateway::class);

    // Validate the database relationships exist
    if (!$reportGateway->exists($gibbonReportID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $contexts = is_array($contextData)? $contextData : [$contextData];
    $options = compact('status', 'twoSided');
    $process = $container->get(GenerateReportProcess::class);

    $success = $process->startReportBatch($gibbonReportID, $contexts, $options, $gibbon->session->get('gibbonPersonID'));
    
    sleep(1.0);

    $URL .= !$success
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
