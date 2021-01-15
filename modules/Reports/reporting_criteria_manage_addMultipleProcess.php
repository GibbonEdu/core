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

use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$scopeType = $_POST['scopeType'] ?? '';
$urlParams = [
    'gibbonReportingScopeID' => $_POST['gibbonReportingScopeID'] ?? '',
    'gibbonReportingCycleID' => $_POST['gibbonReportingCycleID'] ?? '',
    'referer' => $_REQUEST['referer'] ?? '',
];
$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_criteria_manage_addMultiple.php&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);

    $data = [
        'gibbonReportingCycleID'        => $urlParams['gibbonReportingCycleID'],
        'gibbonReportingScopeID'        => $urlParams['gibbonReportingScopeID'],
        'gibbonReportingCriteriaTypeID' => $_POST['gibbonReportingCriteriaTypeID'] ?? '',
        'gibbonYearGroupID'             => null,
        'gibbonRollGroupID'             => null,
        'gibbonCourseID'                => null,
        'target'                        => $_POST['target'] ?? '',
        'name'                          => $_POST['name'] ?? '',
        'description'                   => $_POST['description'] ?? '',
        'category'                      => $_POST['category'] ?? '',
        'groupID'                       => bin2hex(random_bytes(22)),
    ];

    // Validate the required values are present
    if (empty($scopeType) || empty($data['name']) || empty($data['gibbonReportingScopeID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($scopeType == 'Year Group') {
        $identifier = 'gibbonYearGroupID';
    } elseif ($scopeType == 'Roll Group') {
        $identifier = 'gibbonRollGroupID';
    } elseif ($scopeType == 'Course') {
        $identifier = 'gibbonCourseID';
    }

    // Create one record per selected year group/roll group/course
    $group = $_POST[$identifier] ?? [];
    foreach ($group as $scopeTypeID) {
        $data[$identifier] = $scopeTypeID;
        $data['sequenceNumber'] = $reportingCriteriaGateway->getHighestSequenceNumberByScope($data['gibbonReportingScopeID'], $scopeType, $scopeTypeID) + 1;
        $inserted = $reportingCriteriaGateway->insert($data);
        $partialFail &= !$inserted;
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}&scopeTypeID=".implode(',', $group));
}
