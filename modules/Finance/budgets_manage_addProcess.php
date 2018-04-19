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

include './moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/budgets_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $name = $_POST['name'];
    $nameShort = $_POST['nameShort'];
    $active = $_POST['active'];
    $category = $_POST['category'];

    //Lock table
    try {
        $sql = 'LOCK TABLES gibbonFinanceBudget WRITE, gibbonFinanceBudgetPerson WRITE';
        $result = $connection2->query($sql);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    //Get next autoincrement
    try {
        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonFinanceBudget'";
        $resultAI = $connection2->query($sqlAI);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    $rowAI = $resultAI->fetch();
    $AI = str_pad($rowAI['Auto_increment'], 4, '0', STR_PAD_LEFT);

    if ($name == '' or $nameShort == '' or $active == '' or $category == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check for uniqueness
        try {
            $data = array('name' => $name, 'nameShort' => $nameShort);
            $sql = 'SELECT * FROM gibbonFinanceBudget WHERE name=:name OR nameShort=:nameShort';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() > 0) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('name' => $name, 'nameShort' => $nameShort, 'active' => $active, 'category' => $category, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "INSERT INTO gibbonFinanceBudget SET name=:name, nameShort=:nameShort, active=:active, category=:category, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='".date('Y-m-d H:i:s')."'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Scan through staff
            $partialFail = false;
            $staff = array();
            if (isset($_POST['staff'])) {
                $staff = $_POST['staff'];
            }
            $access = $_POST['access'];
            if ($access != 'Full' and $access != 'Write' and $access != 'Read') {
                $role = 'Read';
            }
            if (count($staff) > 0) {
                foreach ($staff as $t) {
                    //Check to see if person is already registered in this budget
                    try {
                        $dataGuest = array('gibbonPersonID' => $t, 'gibbonFinanceBudgetID' => $AI);
                        $sqlGuest = 'SELECT * FROM gibbonFinanceBudgetPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                        $resultGuest = $connection2->prepare($sqlGuest);
                        $resultGuest->execute($dataGuest);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultGuest->rowCount() == 0) {
                        try {
                            $data = array('gibbonPersonID' => $t, 'gibbonFinanceBudgetID' => $AI, 'access' => $access);
                            $sql = 'INSERT INTO gibbonFinanceBudgetPerson SET gibbonPersonID=:gibbonPersonID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID, access=:access';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }

            try {
                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
            }

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
