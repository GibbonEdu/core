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

use Gibbon\Module\Reports\Domain\ReportingAccessGateway;

require_once '../../gibbon.php';

$gibbonReportingAccessID = $_GET['gibbonReportingAccessID'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_access_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_access_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonReportingAccessID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $reportingAccessGateway = $container->get(ReportingAccessGateway::class);
    $values = $reportingAccessGateway->getByID($gibbonReportingAccessID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $reportingAccessGateway->delete($gibbonReportingAccessID);
    $partialFail &= !$deleted;

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
