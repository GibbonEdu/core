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
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/library_manage_catalog_delete.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=".$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'];
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'];

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonLibraryItemID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Write to database
        try {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'DELETE FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Success 0
        $URLDelete = $URLDelete.'&return=success0';
        header("Location: {$URLDelete}");
    }
}
