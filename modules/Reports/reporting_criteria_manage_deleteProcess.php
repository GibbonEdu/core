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

require_once '../../gibbon.php';

$gibbonReportingCriteriaID = $_GET['gibbonReportingCriteriaID'] ?? '';
$urlParams = [
    'gibbonReportingScopeID' => $_POST['gibbonReportingScopeID'] ?? '',
    'gibbonReportingCycleID' => $_POST['gibbonReportingCycleID'] ?? '',
    'gibbonYearGroupID' => $_POST['gibbonYearGroupID'] ?? null,
    'gibbonFormGroupID' => $_POST['gibbonFormGroupID'] ?? null,
    'gibbonCourseID' => $_POST['gibbonCourseID'] ?? null,
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_criteria_manage.php&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_criteria_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonReportingCriteriaID) || empty($urlParams['gibbonReportingScopeID']) || empty($urlParams['gibbonReportingCycleID'])) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $detach = $_POST['detach'] ?? null;

    $reportingCriteriaGateway = $container->get(ReportingCriteriaGateway::class);
    $values = $reportingCriteriaGateway->getByID($gibbonReportingCriteriaID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Delete the record (or grouped records). Optionally detach and delete only one record.
    if (!empty($values['groupID']) && $detach != 'Y') {
        $deleted = $reportingCriteriaGateway->deleteWhere(['gibbonReportingCycleID' => $urlParams['gibbonReportingCycleID'], 'groupID' => $values['groupID']]);
    } else {
        $deleted = $reportingCriteriaGateway->delete($gibbonReportingCriteriaID);
    }
    
    $partialFail &= !$deleted;

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
