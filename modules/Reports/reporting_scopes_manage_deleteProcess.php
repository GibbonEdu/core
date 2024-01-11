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

use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;

require_once '../../gibbon.php';

$gibbonReportingScopeID = $_GET['gibbonReportingScopeID'] ?? '';
$gibbonReportingCycleID = $_POST['gibbonReportingCycleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_scopes_manage.php&gibbonReportingCycleID='.$gibbonReportingCycleID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_scopes_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonReportingScopeID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $values = $reportingScopeGateway->getByID($gibbonReportingScopeID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Delete scope
    $deleted = $reportingScopeGateway->delete($gibbonReportingScopeID);
    $partialFail &= !$deleted;

    // Delete criteria
    $partialFail &= !$container->get(ReportingCriteriaGateway::class)->deleteWhere(['gibbonReportingScopeID' => $gibbonReportingScopeID]);

    // Delete progress
    $partialFail &= !$container->get(ReportingProgressGateway::class)->deleteWhere(['gibbonReportingScopeID' => $gibbonReportingScopeID]);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
