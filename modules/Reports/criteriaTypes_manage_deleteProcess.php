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

use Gibbon\Module\Reports\Domain\ReportingCriteriaTypeGateway;

require_once '../../gibbon.php';

$gibbonReportingCriteriaTypeID = $_GET['gibbonReportingCriteriaTypeID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/criteriaTypes_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/criteriaTypes_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($gibbonReportingCriteriaTypeID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $criteriaTypeGateway = $container->get(ReportingCriteriaTypeGateway::class);
    $values = $criteriaTypeGateway->getByID($gibbonReportingCriteriaTypeID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $criteriaTypeGateway->delete($gibbonReportingCriteriaTypeID);
    $partialFail &= !$deleted;

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
