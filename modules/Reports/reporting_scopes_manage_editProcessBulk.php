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
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;

require_once '../../gibbon.php';

$urlParams = [
    'gibbonReportingScopeID' =>  $_POST['gibbonReportingScopeID'] ?? '',
    'gibbonReportingCycleID' =>  $_POST['gibbonReportingCycleID'] ?? '',
];

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_scopes_manage_edit.php&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_scopes_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);

    $action = $_REQUEST['action'] ?? [];
    $scopeTypeIDs = $_REQUEST['scopeTypeID'] ?? [];
    if (!is_array($scopeTypeIDs)) $scopeTypeIDs = explode(',', $scopeTypeIDs);

    // Validate the required values are present
    if (empty($action) || empty($scopeTypeIDs) || empty($urlParams['gibbonReportingScopeID']) || empty($urlParams['gibbonReportingCycleID'])) {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $values = $reportingScopeGateway->getByID($urlParams['gibbonReportingScopeID']);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($action == 'Add Multiple') {
        // Add scope IDs and redirect to the add multiple criteria page
        $urlParams['scopeTypeID'] = $scopeTypeIDs;
        $URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_criteria_manage_addMultiple.php&'.http_build_query($urlParams);
        header("Location: {$URL}");
        exit;
    } elseif ($action == 'Delete') {
        // Delete all selected criteria based on it's scope type
        foreach ($scopeTypeIDs as $id) {
            $data = [
                'gibbonReportingScopeID' => $values['gibbonReportingScopeID'],
                'gibbonReportingCycleID' => $values['gibbonReportingCycleID'],
            ];
            if ($values['scopeType'] == 'Year Group') {
                $reportingCriteriaGateway->deleteWhere($data + ['gibbonYearGroupID' => $id]);
            } elseif ($values['scopeType'] == 'Roll Group') {
                $reportingCriteriaGateway->deleteWhere($data + ['gibbonRollGroupID' => $id]);
            } elseif ($values['scopeType'] == 'Course') {
                $reportingCriteriaGateway->deleteWhere($data + ['gibbonCourseID' => $id]);
            }
        }

        $URL .= "&return=success0";
        header("Location: {$URL}");
        exit;
    }

    $URL .= "&return=error1";
    header("Location: {$URL}");
}
