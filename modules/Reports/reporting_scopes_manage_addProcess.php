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

use Gibbon\Services\Module\Resource;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportingCycleID = $_POST['gibbonReportingCycleID'] ?? '';
$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_scopes_manage_add.php&gibbonReportingCycleID='.$gibbonReportingCycleID;

if (isActionAccessible($guid, $connection2, Resource::fromRoute('Reports', 'reporting_scopes_manage_add')) == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);

    $data = [
        'gibbonReportingCycleID' => $gibbonReportingCycleID,
        'name'                   => $_POST['name'] ?? '',
        'scopeType'              => $_POST['scopeType'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['gibbonReportingCycleID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportingScopeGateway->unique($data, ['name', 'gibbonReportingCycleID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonReportingScopeID = $reportingScopeGateway->insert($data);

    $URL .= !$gibbonReportingScopeID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportingScopeID");
}
