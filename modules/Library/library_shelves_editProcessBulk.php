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
use Gibbon\Domain\Library\LibraryShelfItemGateway;

include '../../gibbon.php';
$action = $_POST['action'] ?? '';
$gibbonLibraryShelfID = $_POST['gibbonLibraryShelfID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Library/library_manage_shelves_edit.php&gibbonLibraryShelfID=$gibbonLibraryShelfID";

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $items = $_POST['gibbonLibraryShelfItemID'] ?? [];

    if (empty($action) || $action != 'Delete') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 
    
    // Check if items specified
    if (empty($items)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } 

    $shelf = $container->get(LibraryShelfGateway::class)->getByID($gibbonLibraryShelfID);
    if (empty($shelf)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    } 

    $shelfGateway = $container->get(LibraryShelfGateway::class);
    $itemGateway = $container->get(LibraryShelfItemGateway::class);

    $partialFail = false;
    $students = [];
    
    foreach ($items AS $gibbonLibraryShelfItemID) {
        $shelfItem = $itemGateway->getByID($gibbonLibraryShelfItemID);

        if (empty($shelfItem)) {
            $partialFail = true;
            continue;
        }

        if ($action == 'Delete') {
            $itemGateway->delete($shelfItem['gibbonLibraryShelfItemID']);
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
