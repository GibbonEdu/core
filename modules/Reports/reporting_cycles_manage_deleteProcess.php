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

use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Module\Reports\Domain\ReportingValueGateway;
use Gibbon\Module\Reports\Domain\ReportingAccessGateway;
use Gibbon\Module\Reports\Domain\ReportingCriteriaGateway;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;
use Gibbon\Module\Reports\Domain\ReportingProofGateway;

require_once '../../gibbon.php';

$gibbonReportingCycleID = $_GET['gibbonReportingCycleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_cycles_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonReportingCycleID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);
    $reportingValueGateway = $container->get(ReportingValueGateway::class);

    $values = $reportingCycleGateway->getByID($gibbonReportingCycleID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $reportingCycleGateway->delete($gibbonReportingCycleID);
    $partialFail &= !$deleted;

    // Delete access
    $partialFail &= !$container->get(ReportingAccessGateway::class)->deleteWhere(['gibbonReportingCycleID' => $gibbonReportingCycleID]);

    // Delete criteria
    $partialFail &= !$container->get(ReportingCriteriaGateway::class)->deleteWhere(['gibbonReportingCycleID' => $gibbonReportingCycleID]);

    // Delete progress
    $scopes = $reportingScopeGateway->selectBy(['gibbonReportingCycleID' => $gibbonReportingCycleID])->fetchAll();
    foreach ($scopes as $scopeData) {
        $partialFail &= !$container->get(ReportingProgressGateway::class)->deleteWhere(['gibbonReportingScopeID' => $scopeData['gibbonReportingScopeID']]);
    }

    // Delete Scopes
    $partialFail &= !$reportingScopeGateway->deleteWhere(['gibbonReportingCycleID' => $gibbonReportingCycleID]);

    // Delete proofs
    $values = $reportingValueGateway->selectBy(['gibbonReportingCycleID' => $gibbonReportingCycleID])->fetchAll();
    foreach ($values as $valueData) {
        $partialFail &= !$container->get(ReportingProofGateway::class)->deleteWhere(['gibbonReportingValueID' => $valueData['gibbonReportingValueID']]);
    }

    // Delete values
    $partialFail &= !$reportingValueGateway->deleteWhere(['gibbonReportingCycleID' => $gibbonReportingCycleID]);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
