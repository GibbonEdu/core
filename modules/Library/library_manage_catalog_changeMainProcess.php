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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Domain\Library\LibraryTypeGateway;

$_POST['address'] = '/modules/Library/library_manage_catalog_edit.php';

require_once '../../gibbon.php';
include './moduleFunctions.php';

$gibbonLibraryItemID = $_GET['gibbonLibraryItemID'] ?? '';
$gibbonLibraryItemIDParent = $_GET['gibbonLibraryItemIDParent'] ?? '';

$name = $_GET['name'] ?? '';
$gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$status = $_GET['status'] ?? '';
$gibbonPersonIDOwnership = $_GET['gibbonPersonIDOwnership'] ?? '';
$typeSpecificFields = $_GET['typeSpecificFields'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Library/library_manage_catalog_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=$typeSpecificFields";

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (empty($gibbonLibraryItemID) || empty($gibbonLibraryItemIDParent)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 

    $libraryGateway = $container->get(LibraryGateway::class);

    $values = $libraryGateway->getByID($gibbonLibraryItemID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Detach this record
    $libraryGateway->update($gibbonLibraryItemID, ['gibbonLibraryItemIDParent' => null]);

    // Update all copies to point to this record
    $libraryGateway->updateWhere(['gibbonLibraryItemIDParent' => $gibbonLibraryItemIDParent], ['gibbonLibraryItemIDParent' => $gibbonLibraryItemID]);

    // Update the previous main record
    $libraryGateway->updateWhere(['gibbonLibraryItemID' => $gibbonLibraryItemIDParent], ['gibbonLibraryItemIDParent' => $gibbonLibraryItemID]);

    // Update all child records to match this one
    $libraryGateway->updateChildRecords($gibbonLibraryItemID);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");

}
