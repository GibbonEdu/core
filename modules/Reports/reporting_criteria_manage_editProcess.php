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

use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportingCriteriaID = $_POST['gibbonReportingCriteriaID'] ?? '';
$urlParams = [
    'gibbonReportingScopeID' => $_POST['gibbonReportingScopeID'] ?? '',
    'gibbonReportingCycleID' => $_POST['gibbonReportingCycleID'] ?? '',
    'gibbonYearGroupID' => $_POST['gibbonYearGroupID'] ?? null,
    'gibbonFormGroupID' => $_POST['gibbonFormGroupID'] ?? null,
    'gibbonCourseID' => $_POST['gibbonCourseID'] ?? null,
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_criteria_manage.php&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
    $groupID = $_POST['groupID'] ?? null;
    $detach = $_POST['detach'] ?? null;

    $data = [
        'gibbonReportingCriteriaTypeID' => $_POST['gibbonReportingCriteriaTypeID'] ?? '',
        'name'                          => $_POST['name'] ?? '',
        'description'                   => $_POST['description'] ?? '',
        'category'                      => $_POST['category'] ?? '',
        'target'                        => $_POST['target'] ?? '',
    ];
    
    // Allow users to detach a record from it's group
    if ($detach) {
        $data['groupID'] = null;
        $groupID = null;
    }

    // Validate the required values are present
    if (empty($gibbonReportingCriteriaID) || empty($urlParams['gibbonReportingScopeID']) || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$reportingCriteriaGateway->exists($gibbonReportingCriteriaID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record (or grouped records)
    if (!empty($groupID)) {
        $updated = $reportingCriteriaGateway->updateWhere(['gibbonReportingCycleID' => $urlParams['gibbonReportingCycleID'], 'groupID' => $groupID], $data);
    } else {
        $updated = $reportingCriteriaGateway->update($gibbonReportingCriteriaID, $data);
    }

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
