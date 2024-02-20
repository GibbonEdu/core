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

use Gibbon\Domain\Library\LibraryShelfGateway;

require_once '../../gibbon.php';

$gibbonLibraryShelfID = $_GET['gibbonLibraryShelfID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Library/library_manage_shelves.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonLibraryShelfID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $shelfGateway = $container->get(LibraryShelfGateway::class);
    $values = $shelfGateway->getByID($gibbonLibraryShelfID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // TODO: Add code for removing books from shelves as well (might require gateway method)
    $partialFail = false;

    // // Delete each date first
    // foreach ($coverageDates as $date) {
    //     $partialFail &= !$staffCoverageDateGateway->delete($date['gibbonStaffCoverageDateID']);
    // }

    // Then delete the coverage itself
    $partialFail &= $shelfGateway->delete($gibbonLibraryShelfID);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
