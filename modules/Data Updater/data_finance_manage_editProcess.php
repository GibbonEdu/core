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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonFinanceInvoiceeUpdateID = $_GET['gibbonFinanceInvoiceeUpdateID'];
$gibbonFinanceInvoiceeID = $_POST['gibbonFinanceInvoiceeID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_finance_manage_edit.php&gibbonFinanceInvoiceeUpdateID=$gibbonFinanceInvoiceeUpdateID";

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFinanceInvoiceeUpdateID == '' or $gibbonFinanceInvoiceeID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = 'SELECT * FROM gibbonFinanceInvoiceeUpdate WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID';
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
            //Set values
            $data = array();
            $set = '';
            if (isset($_POST['newinvoiceToOn'])) {
                if ($_POST['newinvoiceToOn'] == 'on') {
                    $data['invoiceTo'] = $_POST['newinvoiceTo'];
                    $set .= 'gibbonFinanceInvoicee.invoiceTo=:invoiceTo, ';
                }
            }
            if (isset($_POST['newcompanyNameOn'])) {
                if ($_POST['newcompanyNameOn'] == 'on') {
                    $data['companyName'] = $_POST['newcompanyName'];
                    $set .= 'gibbonFinanceInvoicee.companyName=:companyName, ';
                }
            }
            if (isset($_POST['newcompanyContactOn'])) {
                if ($_POST['newcompanyContactOn'] == 'on') {
                    $data['companyContact'] = $_POST['newcompanyContact'];
                    $set .= 'gibbonFinanceInvoicee.companyContact=:companyContact, ';
                }
            }
            if (isset($_POST['newcompanyAddressOn'])) {
                if ($_POST['newcompanyAddressOn'] == 'on') {
                    $data['companyAddress'] = $_POST['newcompanyAddress'];
                    $set .= 'gibbonFinanceInvoicee.companyAddress=:companyAddress, ';
                }
            }
            if (isset($_POST['newcompanyEmailOn'])) {
                if ($_POST['newcompanyEmailOn'] == 'on') {
                    $data['companyEmail'] = $_POST['newcompanyEmail'];
                    $set .= 'gibbonFinanceInvoicee.companyEmail=:companyEmail, ';
                }
            }
            if (isset($_POST['newcompanyCCFamilyOn'])) {
                if ($_POST['newcompanyCCFamilyOn'] == 'on') {
                    $data['companyCCFamily'] = $_POST['newcompanyCCFamily'];
                    $set .= 'gibbonFinanceInvoicee.companyCCFamily=:companyCCFamily, ';
                }
            }
            if (isset($_POST['newcompanyPhoneOn'])) {
                if ($_POST['newcompanyPhoneOn'] == 'on') {
                    $data['companyPhone'] = $_POST['newcompanyPhone'];
                    $set .= 'gibbonFinanceInvoicee.companyPhone=:companyPhone, ';
                }
            }
            if (isset($_POST['newcompanyAllOn'])) {
                if ($_POST['newcompanyAllOn'] == 'on') {
                    $data['companyAll'] = $_POST['newcompanyAll'];
                    $set .= 'gibbonFinanceInvoicee.companyAll=:companyAll, ';
                }
            }
            if (isset($_POST['newgibbonFinanceFeeCategoryIDListOn'])) {
                if ($_POST['newgibbonFinanceFeeCategoryIDListOn'] == 'on') {
                    $data['gibbonFinanceFeeCategoryIDList'] = $_POST['newgibbonFinanceFeeCategoryIDList'];
                    $set .= 'gibbonFinanceInvoicee.gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList, ';
                }
            }

            if (strlen($set) > 1) {
                //Write to database
                try {
                    $data['gibbonFinanceInvoiceeID'] = $gibbonFinanceInvoiceeID;
                    $sql = 'UPDATE gibbonFinanceInvoicee SET '.substr($set, 0, (strlen($set) - 2)).' WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Write to database
                try {
                    $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
                    $sql = "UPDATE gibbonFinanceInvoiceeUpdate SET status='Complete' WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
                    $sql = "UPDATE gibbonFinanceInvoiceeUpdate SET status='Complete' WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&updateReturn=success1';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
