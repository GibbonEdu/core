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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['imageLink' => 'URL', 'fieldLink' => 'URL']);

include './moduleFunctions.php';

$address = $_POST['address'] ?? '';
$name = $_GET['name'] ?? '';
$gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$status = $_GET['status'] ?? '';
$gibbonPersonIDOwnership = $_GET['gibbonPersonIDOwnership'] ?? '';
$typeSpecificFields = $_GET['typeSpecificFields'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/library_manage_catalog_add.php&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=$typeSpecificFields";

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Get general fields
    $gibbonLibraryTypeID = $_POST['gibbonLibraryTypeID'] ?? '';
    $id = $_POST['idCheck'] ?? '';
    $name = $_POST['name'] ?? '';
    $producer = $_POST['producer'] ?? '';
    $vendor = $_POST['vendor'] ?? '';
    $purchaseDate = !empty($_POST['purchaseDate']) ? Format::dateConvert($_POST['purchaseDate']) : null;

    $invoiceNumber = $_POST['invoiceNumber'] ?? '';
    $cost = !empty($_POST['cost']) ? $_POST['cost'] : null;
    $imageType = $_POST['imageType'] ?? '';
    if ($imageType == 'Link') {
        $imageLocation = $_POST['imageLink'] ?? '';
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
    $gibbonDepartmentID = $_POST['gibbonDepartmentID'] ?? null;

    $bookable = $_POST['bookable'] ?? '';
    $borrowable = $_POST['borrowable'] ?? '';
    $status = $_POST['status'] ?? '';
    $physicalCondition = $_POST['physicalCondition'] ?? '';

    //Get type-specific fields

        $data = array('gibbonLibraryTypeID' => $gibbonLibraryTypeID);
        $sql = "SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() == 1) {
        $row = $result->fetch();
        $fieldsIn = json_decode($row['fields'], true);
        $fieldsOut = array();
        foreach ($fieldsIn as $field) {
            $fieldName = preg_replace('/ |\(|\)/', '', $field['name']);
            if ($field['type'] == 'Date') {
                $fieldsOut[$field['name']] = !empty($_POST['field'.$fieldName]) ? Format::dateConvert($_POST['field'.$fieldName]) : null;
            } else {
                $fieldsOut[$field['name']] = $_POST['field'.$fieldName] ?? null;
            }
        }
    }

    if ($gibbonLibraryTypeID == '' or $name == '' or $id == '' or $producer == '' or $bookable == '' or $borrowable == ''  or $status == '' or $replacement == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $dataUnique = array('id' => $id);
            $sqlUnique = 'SELECT * FROM gibbonLibraryItem WHERE id=:id';
            $resultUnique = $connection2->prepare($sqlUnique);
            $resultUnique->execute($dataUnique);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($resultUnique->rowCount() > 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            //Move attached image  file, if there is one
            if (!empty($_FILES['imageFile']['tmp_name']) && $imageType == 'File') {
                $fileUploader = new Gibbon\FileUploader($pdo, $session);
                $fileUploader->getFileExtensions('Graphics/Design');

                $file = (isset($_FILES['imageFile']))? $_FILES['imageFile'] : null;

                // Upload the file, return the /uploads relative path
                $imageLocation = $fileUploader->uploadFromPost($file, $id);

                if (empty($imageLocation)) {
                    $partialFail = true;
                }
            }

            //Write to database
            try {
                $data = array('gibbonLibraryTypeID' => $gibbonLibraryTypeID, 'id' => $id, 'name' => $name, 'producer' => $producer, 'fields' => json_encode($fieldsOut), 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'cost' => $cost, 'imageType' => $imageType, 'imageLocation' => $imageLocation, 'replacement' => $replacement, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'replacementCost' => $replacementCost, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'gibbonDepartmentID' => $gibbonDepartmentID, 'bookable' => $bookable, 'borrowable' => $borrowable, 'status' => $status, 'physicalCondition' => $physicalCondition, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'timestampCreator' => date('Y-m-d H:i:s', time()));
                $sql = 'INSERT INTO gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, id=:id, name=:name, producer=:producer, fields=:fields, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, cost=:cost, imageType=:imageType, imageLocation=:imageLocation, replacement=:replacement, gibbonSchoolYearIDReplacement=:gibbonSchoolYearIDReplacement, replacementCost=:replacementCost, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, bookable=:bookable, borrowable=:borrowable, status=:status, physicalCondition=:physicalCondition, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 10, '0', STR_PAD_LEFT);

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
            }
        }
    }
}
