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

use Gibbon\Data\Validator;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Domain\Library\LibraryShelfGateway;
use Gibbon\Domain\Library\LibraryShelfItemGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);


$URL = $session->get('absoluteURL').'/index.php?q=/modules/Library/library_manage_shelves_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $libraryGateway = $container->get(LibraryGateway::class);
    $shelfGateway = $container->get(LibraryShelfGateway::class);
    $itemGateway = $container->get(LibraryShelfItemGateway::class);

    $data = [
        'name' => $_POST['shelfName'] ?? '',
        'active'         => $_POST['active'] ?? '',
        'type'           => $_POST['type'] ?? '',
        'field'        => $_POST['field'] ?? '',
        'fieldValue'       => $_POST['fieldValue'] ?? '',
        'shuffle'       => $_POST['shuffle'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['active']) || empty($data['type']) || empty($data['field'])|| empty($data['shuffle'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate that this person doesn't already have a record
    if (!$shelfGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the substitute
    $gibbonLibraryShelfID = $shelfGateway->insert($data);

    $shelfItems = isset($_POST['addItems'])? explode(',', $_POST['addItems']) : [];
    //$gibbonLibraryTypeID = isset($_POST['gibbonLibraryTypeID'])? $_POST['gibbonLibraryTypeID'] : '';
    $gibbonLibraryTypeID = $_POST['gibbonLibraryTypeID'];
    
    if(!empty($shelfItems) && $data['type'] == 'Manual') {
        foreach($shelfItems as $item) {
            $itemGateway->insertShelfItem($item, $gibbonLibraryShelfID);
        }
    } else if(!empty($gibbonLibraryTypeID) && $data['type'] == 'Automatic') {
        $autoItems = $libraryGateway->selectItemsByTypeFields($gibbonLibraryTypeID, $data['field'], $data['fieldValue'])->fetchAll();
        foreach($autoItems as $item) {
            $itemGateway->insertShelfItem($item['gibbonLibraryItemID'], $gibbonLibraryShelfID);
        }
    } else {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $URL .= !$gibbonLibraryShelfID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonLibraryShelfID");
}
