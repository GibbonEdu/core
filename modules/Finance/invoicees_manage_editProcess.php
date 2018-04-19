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

$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoicees_manage_edit.php&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&search=".$_GET['search'].'&allUsers='.$_GET['allUsers'];

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoicees_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFinanceInvoiceeID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
            $sql = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
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
            //Proceed!
            $invoiceTo = $_POST['invoiceTo'];
            if ($invoiceTo == 'Company') {
                $companyName = $_POST['companyName'];
                $companyContact = $_POST['companyContact'];
                $companyAddress = $_POST['companyAddress'];
                $companyEmail = $_POST['companyEmail'];
                $companyCCFamily = $_POST['companyCCFamily'];
                $companyPhone = $_POST['companyPhone'];
                $companyAll = $_POST['companyAll'];
                $gibbonFinanceFeeCategoryIDList = null;
                if ($companyAll == 'N') {
                    $gibbonFinanceFeeCategoryIDList == '';
                    $gibbonFinanceFeeCategoryIDArray = $_POST['gibbonFinanceFeeCategoryIDList'];
                    if (count($gibbonFinanceFeeCategoryIDArray) > 0) {
                        foreach ($gibbonFinanceFeeCategoryIDArray as $gibbonFinanceFeeCategoryID) {
                            $gibbonFinanceFeeCategoryIDList .= $gibbonFinanceFeeCategoryID.',';
                        }
                        $gibbonFinanceFeeCategoryIDList = substr($gibbonFinanceFeeCategoryIDList, 0, -1);
                    }
                }
            } else {
                $companyName = null;
                $companyContact = null;
                $companyAddress = null;
                $companyEmail = null;
                $companyCCFamily = null;
                $companyPhone = null;
                $companyAll = null;
                $gibbonFinanceFeeCategoryIDList = null;
            }
            if ($invoiceTo == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('invoiceTo' => $invoiceTo, 'companyName' => $companyName, 'companyContact' => $companyContact, 'companyAddress' => $companyAddress, 'companyEmail' => $companyEmail, 'companyCCFamily' => $companyCCFamily, 'companyPhone' => $companyPhone, 'companyAll' => $companyAll, 'gibbonFinanceFeeCategoryIDList' => $gibbonFinanceFeeCategoryIDList, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                    $sql = 'UPDATE gibbonFinanceInvoicee SET invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyCCFamily=:companyCCFamily, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
