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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../functions.php';
include '../../config.php';

include './moduleFunctions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$id = $_GET['id'];
$mode = $_GET['mode'];
$feeType = $_GET['feeType'];
$gibbonFinanceFeeID = $_GET['gibbonFinanceFeeID'];
$name = $_GET['name'];
$description = $_GET['description'];
$gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'];
$fee = $_GET['fee'];
$category = null;
if (isset($_GET['category'])) {
    $category = $_GET['category'];
}

makeFeeBlock($guid, $connection2, $id, $mode, $feeType, $gibbonFinanceFeeID, $name, $description, $gibbonFinanceFeeCategoryID, $fee, $category);
