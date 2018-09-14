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

include '../../gibbon.php';

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$search = $_GET['search'];

if ($gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/fees_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/fees_manage_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $name = $_POST['name'];
        $nameShort = $_POST['nameShort'];
        $active = $_POST['active'];
        $description = $_POST['description'];
        $gibbonFinanceFeeCategoryID = $_POST['gibbonFinanceFeeCategoryID'];
        $fee = $_POST['fee'];

        if ($name == '' or $nameShort == '' or $active == '' or $gibbonFinanceFeeCategoryID == '' or $fee == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {

            //Write to database
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'name' => $name, 'nameShort' => $nameShort, 'active' => $active, 'description' => $description, 'gibbonFinanceFeeCategoryID' => $gibbonFinanceFeeCategoryID, 'fee' => $fee, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "INSERT INTO gibbonFinanceFee SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, nameShort=:nameShort, active=:active, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='".date('Y-m-d H:i:s')."'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 6, '0', STR_PAD_LEFT);

            $URL .= "&return=success0&editID=$AI";
            header("Location: {$URL}");
        }
    }
}
