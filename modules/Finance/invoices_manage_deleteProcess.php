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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonFinanceInvoiceID = $_POST['gibbonFinanceInvoiceID'] ?? '';
$status = $_GET['status'] ?? null;
$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'] ?? null;
$monthOfIssue = $_GET['monthOfIssue'] ?? null;
$gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'] ?? null;
$gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'] ?? null;

if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage_delete.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID";
    $URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_delete.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        if ($gibbonFinanceInvoiceID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                $sql = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Pending'";
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
                //Write to database
                try {
                    $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                    $sql = 'DELETE FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                try {
                    $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                    $sql = 'DELETE FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
