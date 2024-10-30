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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['imageLink' => 'URL', 'fieldLink' => 'URL']);

include './moduleFunctions.php';

$gibbonLibraryItemID = $_POST['gibbonLibraryItemID'] ?? '';
$address = $_POST['address'] ?? '';
$name = $_GET['name'] ?? '';
$gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$status = $_GET['status'] ?? '';
$gibbonPersonIDOwnership = $_GET['gibbonPersonIDOwnership'] ?? '';
$typeSpecificFields = $_GET['typeSpecificFields'] ?? '';
$isParentRecord = $_POST['isParentRecord'] ?? '';
$isChildRecord = $_POST['isChildRecord'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/library_manage_catalog_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=$typeSpecificFields";

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonLibraryItemID specified
    if ($gibbonLibraryItemID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 

    $libraryGateway = $container->get(LibraryGateway::class);
    $libraryTypeGateway = $container->get(LibraryTypeGateway::class);

    $row = $libraryGateway->getByID($gibbonLibraryItemID);

    if (empty($row)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    } 

    //Proceed!
    //Get general fields
    $gibbonLibraryTypeID = $_POST['gibbonLibraryTypeID'] ?? '';
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $producer = $_POST['producer'] ?? '';
    $vendor = $_POST['vendor'] ?? '';
    $detach = $_POST['detach'] ?? '';
    $attach = $_POST['attach'] ?? '';
    $purchaseDate = !empty($_POST['purchaseDate']) ? Format::dateConvert($_POST['purchaseDate']) : null;

    $invoiceNumber = $_POST['invoiceNumber'] ?? '';
    $cost = !empty($_POST['cost']) ? $_POST['cost'] : null;
    $imageType = $_POST['imageType'] ?? '';
    if ($imageType == 'Link') {
        $imageLocation = $_POST['imageLink'] ?? '';
    } elseif ($imageType == 'File') {
        $imageLocation = $row['imageLocation'];
    } else {
        $imageLocation = '';
    }
    $replacement = $_POST['replacement'] ?? '';
    $gibbonSchoolYearIDReplacement = null;
    $replacementCost = null;
    if ($replacement == 'Y') {
        if ($_POST['gibbonSchoolYearIDReplacement'] != '') {
            $gibbonSchoolYearIDReplacement = $_POST['gibbonSchoolYearIDReplacement'] ?? '';
        }
        if ($_POST['replacementCost'] != '') {
            $replacementCost = $_POST['replacementCost'] ?? '';
        }
    } else {
        $replacement == 'N';
    }
    $comment = $_POST['comment'] ?? '';
    $gibbonSpaceID = $_POST['gibbonSpaceID'] ?? null;
    $locationDetail = $_POST['locationDetail'] ?? '';
    $ownershipType = $_POST['ownershipType'] ?? '';
    $gibbonPersonIDOwnership = null;
    if ($ownershipType == 'School' and $_POST['gibbonPersonIDOwnershipSchool'] != '') {
        $gibbonPersonIDOwnership = $_POST['gibbonPersonIDOwnershipSchool'] ?? '';
    } elseif ($ownershipType == 'Individual' and $_POST['gibbonPersonIDOwnershipIndividual'] != '') {
        $gibbonPersonIDOwnership = $_POST['gibbonPersonIDOwnershipIndividual'] ?? '';
    }
    $gibbonDepartmentID = $_POST['gibbonDepartmentID'] ?? '';
    $bookable = $_POST['bookable'] ?? '';
    $borrowable = $_POST['borrowable'] ?? '';
    if ($borrowable == 'Y') {
        $status = $_POST['statusBorrowable'] ?? '';
    } else {
        $status = $_POST['statusNotBorrowable'] ?? '';
    }
    $physicalCondition = $_POST['physicalCondition'] ?? '';

    //Get type-specific fields
    $typeDetails = $libraryTypeGateway->getByID($gibbonLibraryTypeID);

    if (!empty($typeDetails)) {
        $fieldsIn = json_decode($typeDetails['fields'], true);
        $fieldsOut = [];
        foreach ($fieldsIn as $field) {
            $fieldName = preg_replace('/ |\(|\)/', '', $field['name']);
            if ($field['type'] == 'Date') {
                $fieldsOut[$field['name']] = !empty($_POST['field'.$fieldName]) ? Format::dateConvert($_POST['field'.$fieldName]) : null;
            } else {
                $fieldsOut[$field['name']] = $_POST['field'.$fieldName] ?? null;
            }
        }
    }

    if ($gibbonLibraryTypeID == '' or $name == '' or $id == '' or $producer == '' or $bookable == '' or $borrowable == '' or $replacement == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }
    
    // Enable detaching and attaching child records
    if ($isChildRecord && $detach == 'Y') {
        $gibbonLibraryItemIDParent = null;
    } elseif (!$isChildRecord && !$isParentRecord && !empty($attach)) {
        $parent = $libraryGateway->getByRecordID($attach);
        $gibbonLibraryItemIDParent = $parent['gibbonLibraryItemID'] ?? null;
        $isChildRecord = true;
    } else {
        $gibbonLibraryItemIDParent = $row['gibbonLibraryItemIDParent'] ?? null;
    }

    if ($isChildRecord) {
        $data = ['id' => $id, 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'cost' => $cost, 'replacement' => $replacement, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'replacementCost' => $replacementCost, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'bookable' => $bookable, 'borrowable' => $borrowable, 'status' => $status, 'physicalCondition' => $physicalCondition, 'gibbonPersonIDUpdate' => $session->get('gibbonPersonID'), 'gibbonLibraryItemIDParent' => $gibbonLibraryItemIDParent, 'timestampUpdate' => date('Y-m-d H:i:s')];
    } else {
        $data = ['id' => $id, 'name' => $name, 'producer' => $producer, 'fields' => json_encode($fieldsOut), 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'cost' => $cost, 'imageType' => $imageType, 'imageLocation' => $imageLocation, 'replacement' => $replacement, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'replacementCost' => $replacementCost, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'gibbonDepartmentID' => $gibbonDepartmentID, 'bookable' => $bookable, 'borrowable' => $borrowable, 'status' => $status, 'physicalCondition' => $physicalCondition, 'gibbonPersonIDUpdate' => $session->get('gibbonPersonID'), 'gibbonLibraryItemIDParent' => $gibbonLibraryItemIDParent, 'timestampUpdate' => date('Y-m-d H:i:s')];
    }

    // Check unique inputs for uniqueness
    if (!$libraryGateway->unique($data, ['id'], $gibbonLibraryItemID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    } 

    $partialFail = false;

    // Move attached image  file, if there is one
    if (!empty($_FILES['imageFile']['tmp_name']) && $imageType == 'File') {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);
        $fileUploader->getFileExtensions('Graphics/Design');

        $file = (isset($_FILES['imageFile']))? $_FILES['imageFile'] : null;

        // Upload the file, return the /uploads relative path
        $data['imageLocation'] = $fileUploader->uploadFromPost($file, $id);

        if (empty($data['imageLocation'])) {
            $partialFail = true;
        }
    }

    // Write to database
    $updated = $libraryGateway->update($gibbonLibraryItemID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit; 
    }

    // Update child records
    if ($isParentRecord) {
        $libraryGateway->updateChildRecords($gibbonLibraryItemID);
    } else if ($isChildRecord) {
        $libraryGateway->updateFromParentRecord($gibbonLibraryItemID);
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");

}
