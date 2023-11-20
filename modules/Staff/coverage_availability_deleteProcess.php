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

use Gibbon\Domain\Staff\StaffCoverageDateGateway;

require_once '../../gibbon.php';

$gibbonPersonID = $_REQUEST['gibbonPersonID'] ?? '';
$gibbonStaffCoverageDateID = $_REQUEST['gibbonStaffCoverageDateID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_availability.php&gibbonPersonID='.$gibbonPersonID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_availability.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonPersonID) || empty($gibbonStaffCoverageDateID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    $exceptionList = is_array($gibbonStaffCoverageDateID)? $gibbonStaffCoverageDateID : [$gibbonStaffCoverageDateID];
    $partialFail = false;

    foreach ($exceptionList as $exceptionID) {
        $deleted = $staffCoverageDateGateway->delete($exceptionID);
        $partialFail &= !$deleted;
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
