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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_view_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'];
    $type = $_GET['type'];
    $gibbonPersonID = null;
    if (isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '' or $type == '' or $gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Confirm access to this student
        try {
            $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
            $resultChild = $connection2->prepare($sqlChild);
            $resultChild->execute($dataChild);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultChild->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $rowChild = $resultChild->fetch();

            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT surname, preferredName, gibbonFinanceInvoice.* FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND gibbonFinanceInvoicee.gibbonPersonID=:gibbonPersonID AND (gibbonFinanceInvoice.status='Issued' OR gibbonFinanceInvoice.status='Paid' OR gibbonFinanceInvoice.status='Paid - Partial')";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->fetch();

                $statusExtra = '';
                if ($row['status'] == 'Issued' and $row['invoiceDueDate'] < date('Y-m-d')) {
                    $statusExtra = 'Overdue';
                }
                if ($row['status'] == 'Paid' and $row['invoiceDueDate'] < $row['paidDate']) {
                    $statusExtra = 'Late';
                }

                if ($type == 'invoice') {
                    echo '<h2>';
                    echo 'Invoice';
                    echo '</h2>';
                    $invoiceContents = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency'], false, true);
                    if ($invoiceContents == false) {
                        echo "<div class='error'>";
                        echo __($guid, 'An error occurred.');
                        echo '</div>';
                    } else {
                        echo $invoiceContents;
                    }
                } elseif ($type = 'Receipt') {
                    echo '<h2>';
                    echo __($guid, 'Receipt');
                    echo '</h2>';
                    $receiptContents = receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency'], false, $receiptNumber);
                    if ($receiptContents == false) {
                        echo "<div class='error'>";
                        echo __($guid, 'An error occurred.');
                        echo '</div>';
                    } else {
                        echo $receiptContents;
                    }
                }
            }
        }
    }
}
