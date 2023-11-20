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

include '../../gibbon.php';

include './moduleFunctions.php';

$queryArr = [
  "q" => "/modules/".getModuleName($_POST['address'])."/library_manage_catalog_delete.php",
  "gibbonLibraryItemID" => $_POST['gibbonLibraryItemID'] ?? '',
  "name" => $_GET['name'] ?? '',
  "gibbonLibraryTypeID" => $_GET['gibbonLibraryTypeID'] ?? '',
  "gibbonSpaceID" => $_GET['gibbonSpaceID'] ?? '',
  "status" => $_GET['space'] ?? '',
  "gibbonPersonIDOwnership" => $_GET['gibbonPersonIDOwnership'] ?? '',
  "typeSpecificfields" => $_GET['typeSpecificFields'] ?? ''
];
$baseURL = $session->get('absoluteURL').'/index.php?';

if (isActionAccessible($guid, $connection2, $queryArr['q']) == false) {
    $queryArr['return'] = "error0";
    header("Location: " . $baseURL . http_build_query($queryArr));
} else {
    //Proceed!
    if ($queryArr['gibbonLibraryItemID'] == '') {
        $queryArr['return'] = "error1";
        header("Location: " . $baseURL . http_build_query($queryArr));
    } else {
        //Write to database
        try {
            $data = array('gibbonLibraryItemID' => $queryArr['gibbonLibraryItemID']);
            $sql = 'DELETE FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $queryArr['return'] = "error0";
            header("Location: " . $baseURL . http_build_query($queryArr));
            exit();
        }

        //Success 0
        $queryArr['q'] = "/modules/".getModuleName($_POST['address'])."/library_manage_catalog.php";
        $queryArr['return'] = "success0";
        header("Location: " . $baseURL . http_build_query($queryArr));
        exit;
    }
}
