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

$_POST['address'] = '/modules/Staff/coverage_manage_edit.php';
$gibbonStaffCoverageID = $_GET['gibbonStaffCoverageID'] ?? '';
$gibbonStaffCoverageDateID = $_GET['gibbonStaffCoverageDateID'] ?? '';

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_manage_edit.php&gibbonStaffCoverageID='.$gibbonStaffCoverageID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonStaffCoverageID) || empty($gibbonStaffCoverageDateID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    $values = $staffCoverageDateGateway->getByID($gibbonStaffCoverageDateID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $staffCoverageDateGateway->delete($gibbonStaffCoverageDateID);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
