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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonLibraryItemID = $_POST['gibbonLibraryItemID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/library_manage_catalog_duplicate.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=".$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'];

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

            $status = 'Available';
            $imageType = $row['imageType'];
            $imageLocation = $row['imageLocation'];
            $gibbonLibraryTypeID = $row['gibbonLibraryTypeID'];
            $name = $row['name'];
            $producer = $row['producer'];
            $vendor = $row['vendor'];
            $purchaseDate = $row['purchaseDate'];
            $invoiceNumber = $row['invoiceNumber'];
            $replacement = $row['replacement'];
            $gibbonSchoolYearIDReplacement = $row['gibbonSchoolYearIDReplacement'];
            $replacementCost = $row['replacementCost'];
            $comment = $row['comment'];
            $gibbonSpaceID = $row['gibbonSpaceID'];
            $locationDetail = $row['locationDetail'];
            $ownershipType = $row['ownershipType'];
            $gibbonPersonIDOwnership = $row['gibbonPersonIDOwnership'];
            $gibbonDepartmentID = $row['gibbonDepartmentID'];
            $borrowable = $row['borrowable'];
            $bookable = $row['bookable'];
            $fields = $row['fields'];
            $count = $_POST['count'];

            if ($gibbonLibraryTypeID == '' or $name == '' or $producer == '' or $borrowable == '' or $count == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            }
            else {
                $partialFail = false;

                for ($i = 1; $i <= $count; ++$i) {
                    $id = $_POST['id'.$i];

                    if ($id == '') {
                        $partialFail = true;
                    }
                    else {
                        //Check unique inputs for uniquness
                        try {
                            $dataUnique = array('id' => $id);
                            $sqlUnique = 'SELECT * FROM gibbonLibraryItem WHERE id=:id';
                            $resultUnique = $connection2->prepare($sqlUnique);
                            $resultUnique->execute($dataUnique);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }

                        if ($resultUnique->rowCount() > 0) {
                            $partialFail = true;
                        } else {
                            //Write to database
                            try {
                                $data = array('gibbonLibraryTypeID' => $gibbonLibraryTypeID, 'id' => $id, 'name' => $name, 'producer' => $producer, 'fields' => $fields, 'vendor' => $vendor, 'purchaseDate' => $purchaseDate, 'invoiceNumber' => $invoiceNumber, 'imageType' => $imageType, 'imageLocation' => $imageLocation, 'replacement' => $replacement, 'gibbonSchoolYearIDReplacement' => $gibbonSchoolYearIDReplacement, 'replacementCost' => $replacementCost, 'comment' => $comment, 'gibbonSpaceID' => $gibbonSpaceID, 'locationDetail' => $locationDetail, 'ownershipType' => $ownershipType, 'gibbonPersonIDOwnership' => $gibbonPersonIDOwnership, 'gibbonDepartmentID' => $gibbonDepartmentID, 'borrowable' => $borrowable, 'bookable' => $bookable, 'status' => $status, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'timestampCreator' => date('Y-m-d H:i:s', time()));
                                $sql = 'INSERT INTO gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, id=:id, name=:name, producer=:producer, fields=:fields, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, imageType=:imageType, imageLocation=:imageLocation, replacement=:replacement, gibbonSchoolYearIDReplacement=:gibbonSchoolYearIDReplacement, replacementCost=:replacementCost, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, bookable=:bookable, status=:status, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                                $failCode = $e->getMessage();
                            }
                        }
                    }
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
