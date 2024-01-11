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

use Gibbon\Module\Reports\SendReportsProcess;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$action = $_POST['action'] ?? [];
$gibbonReportID = $_POST['gibbonReportID'] ?? '';
$templateName = $_POST['templateName'] ?? '';
$contextData = $_POST['contextData'] ?? '';
$identifiers = $_POST['identifier'] ?? [];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reports_send_batch.php&gibbonReportID='.$gibbonReportID.'&contextData='.$contextData;

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
    
    if ($action == 'Send Reports to Parents') {
        $process = $container->get(SendReportsProcess::class);
        $success = $process->startSendReportsToParents($gibbonReportID, $templateName, $identifiers);
    } elseif ($action == 'Send Reports to Students') {
        $process = $container->get(SendReportsProcess::class);
        $success = $process->startSendReportsToStudents($gibbonReportID, $templateName, $identifiers);
    } else {
        $success = false;
    }

    $URL .= !$success
        ? "&return=error2"
        : "&return=success5";

    header("Location: {$URL}");
}
