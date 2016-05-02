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

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'];
    $type = $_GET['type'];
    $preview = null;
    if (isset($_GET['preview'])) {
        $preview = $_GET['preview'];
    }
    $receiptNumber = null;
    if (isset($_GET['receiptNumber'])) {
        $receiptNumber = $_GET['receiptNumber'];
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '' or $type == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = 'SELECT surname, preferredName, gibbonFinanceInvoice.* FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
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
                if ($preview) {
                    echo "<p style='font-weight: bold; color: #c00; font-size: 100%; letter-spacing: -0.5px'>";
                    echo __($guid, 'THIS INVOICE IS A PREVIEW: IT HAS NOT YET BEEN ISSUED AND IS FOR TESTING PURPOSES ONLY!');
                    echo '</p>';
                }

                $invoiceContents = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency'], false, true);
                if ($invoiceContents == false) {
                    echo "<div class='error'>";
                    echo __($guid, 'An error occurred.');
                    echo '</div>';
                } else {
                    echo $invoiceContents;
                }
            } elseif ($type == 'reminder1' or $type == 'reminder2' or $type == 'reminder3') {
                //Update reminder count
                if ($row['reminderCount'] < 3) {
                    try {
                        $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = 'UPDATE gibbonFinanceInvoice SET reminderCount='.($row['reminderCount'] + 1).' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                }

                //Reminder Text
                if ($type == 'reminder1') {
                    echo '<h2>';
                    echo 'Reminder 1';
                    echo '</h2>';
                    $reminderText = getSettingByScope($connection2, 'Finance', 'reminder1Text');
                } elseif ($type == 'reminder2') {
                    echo '<h2>';
                    echo 'Reminder 2';
                    echo '</h2>';
                    $reminderText = getSettingByScope($connection2, 'Finance', 'reminder2Text');
                } elseif ($type == 'reminder3') {
                    echo '<h2>';
                    echo 'Reminder 3';
                    echo '</h2>';
                    $reminderText = getSettingByScope($connection2, 'Finance', 'reminder3Text');
                }
                if ($reminderText != '') {
                    echo '<p>';
                    echo $reminderText;
                    echo '</p>';
                }

                echo '<h2>';
                echo __($guid, 'Invoice');
                echo '</h2>';
                $invoiceContents = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency']);
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
