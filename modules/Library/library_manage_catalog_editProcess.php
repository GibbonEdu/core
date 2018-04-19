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

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

@session_start();

$gibbonLibraryItemID = $_POST['gibbonLibraryItemID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/library_manage_catalog_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=".$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'];

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonLibraryItemID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            $row = $result->fetch();
            //Proceed!
            //Get general fields
            $gibbonLibraryTypeID = $_POST['gibbonLibraryTypeID'];
            $id = $_POST['id'];
            $name = $_POST['name'];
            $producer = $_POST['producer'];
            $vendor = $_POST['vendor'];
            $purchaseDate = null;
            if ($_POST['purchaseDate'] != '') {
                $purchaseDate = dateConvert($guid, $_POST['purchaseDate']);
            }
            $invoiceNumber = $_POST['invoiceNumber'];
            $imageType = $_POST['imageType'];
            if ($imageType == 'Link') {
                $imageLocation = $_POST['imageLink'];
            } elseif ($imageType == 'File') {
                $imageLocation = $row['imageLocation'];
            } else {
                $imageLocation = '';
            }
            $replacement = $_POST['replacement'];
            $gibbonSchoolYearIDReplacement = null;
            $replacementCost = null;
            if ($replacement == 'Y') {
                if ($_POST['gibbonSchoolYearIDReplacement'] != '') {
                    $gibbonSchoolYearIDReplacement = $_POST['gibbonSchoolYearIDReplacement'];
                }
                if ($_POST['replacementCost'] != '') {
                    $replacementCost = $_POST['replacementCost'];
                }
            } else {
                $replacement == 'N';
            }
            $comment = $_POST['comment'];
            $gibbonSpaceID = null;
            if ($_POST['gibbonSpaceID'] != '') {
                $gibbonSpaceID = $_POST['gibbonSpaceID'];
            }
            $locationDetail = $_POST['locationDetail'];
            $ownershipType = $_POST['ownershipType'];
            $gibbonPersonIDOwnership = null;
            if ($ownershipType == 'School' and $_POST['gibbonPersonIDOwnershipSchool'] != '') {
                $gibbonPersonIDOwnership = $_POST['gibbonPersonIDOwnershipSchool'];
            } elseif ($ownershipType == 'Individual' and $_POST['gibbonPersonIDOwnershipIndividual'] != '') {
                $gibbonPersonIDOwnership = $_POST['gibbonPersonIDOwnershipIndividual'];
            }
            $gibbonDepartmentID = null;
            if ($_POST['gibbonDepartmentID'] != '') {
                $gibbonDepartmentID = $_POST['gibbonDepartmentID'];
            }
            $bookable = $_POST['bookable'];
            $borrowable = $_POST['borrowable'];
            if ($borrowable == 'Y') {
                $status = $_POST['statusBorrowable'];
            } else {
                $status = $_POST['statusNotBorrowable'];
            }
            $physicalCondition = $_POST['physicalCondition'];

            //Get type-specific fields
            try {
                $data = array('gibbonLibraryTypeID' => $gibbonLibraryTypeID);
                $sql = "SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }

            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                $fieldsIn = unserialize($row['fields']);
                $fieldsOut = array();
                foreach ($fieldsIn as $field) {
                    $fieldName = preg_replace('/ |\(|\)/', '', $field['name']);
                    if ($field['type'] == 'Date') {
                        $fieldsOut[$field['name']] = dateConvert($guid, $_POST['field'.$fieldName]);
                    } else {
                        $fieldsOut[$field['name']] = $_POST['field'.$fieldName];
                    }
                }
            }

            if ($gibbonLibraryTypeID == '' or $name == '' or $id == '' or $producer == '' or $bookable == '' or $borrowable == '' or $replacement == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $dataUnique = array('id' => $id, 'gibbonLibraryItemID' => $gibbonLibraryItemID);
                    $sqlUnique = 'SELECT * FROM gibbonLibraryItem WHERE id=:id AND NOT gibbonLibraryItemID=:gibbonLibraryItemID';
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
                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
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
                        $data = array('id' => $id, 'name' => $name, 'producer' => $producer, 'fields' => serialize($fieldsOut), 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'imageType' => $imageType, 'imageLocation' => $imageLocation, 'replacement' => $replacement, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'replacementCost' => $replacementCost, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'gibbonDepartmentID' => $gibbonDepartmentID, 'bookable' => $bookable, 'borrowable' => $borrowable, 'status' => $status, 'physicalCondition' => $physicalCondition, 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'timestampUpdate' => date('Y-m-d H:i:s', time()), 'gibbonLibraryItemID' => $gibbonLibraryItemID);
                        $sql = 'UPDATE gibbonLibraryItem SET id=:id, name=:name, producer=:producer, fields=:fields, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, imageType=:imageType, imageLocation=:imageLocation, replacement=:replacement, gibbonSchoolYearIDReplacement=:gibbonSchoolYearIDReplacement, replacementCost=:replacementCost, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, bookable=:bookable, borrowable=:borrowable, status=:status, physicalCondition=:physicalCondition, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate=:timestampUpdate WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        $URL .= "&return=success0";
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
