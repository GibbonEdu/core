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

//Get variables
$gibbonFinanceInvoiceID = '';
if (isset($_GET['gibbonFinanceInvoiceID'])) {
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'];
}
$key = '';
if (isset($_GET['key'])) {
    $key = $_GET['key'];
}

if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, array('error3' => "Your payment could not be made as the payment gateway does not support the system's currency.", 'success1' => 'Your payment has been successfully made to your credit card. A receipt has been emailed to you.', 'success2' => 'Your payment could not be made to your credit card. Please try an alternative payment method.', 'success3' => sprintf(__($guid, 'Your payment has been successfully made to your credit card, but there has been an error recording your payment in %1$s. Please print this screen and contact the school ASAP, quoting code %2$s.'), $_SESSION[$guid]['systemName'], $gibbonFinanceInvoiceID)));
}

if (!isset($_GET['return'])) { //No return message, so must just be landing to make payment
    //Check variables
    if ($gibbonFinanceInvoiceID == '' or $key == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Check for record
        $keyReadFail = false;
        try {
            $dataKeyRead = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'key' => $key);
            $sqlKeyRead = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND `key`=:key AND status='Issued'";
            $resultKeyRead = $connection2->prepare($sqlKeyRead);
            $resultKeyRead->execute($dataKeyRead);
        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed due to a database error.');
            echo '</div>';
        }

        if ($resultKeyRead->rowCount() != 1) { //If not exists, report error
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {    //If exists check confirmed
            $rowKeyRead = $resultKeyRead->fetch();

            //Get value of the invoice.
            $feeOK = true;
            try {
                $dataFees['gibbonFinanceInvoiceID'] = $gibbonFinanceInvoiceID;
                $sqlFees = 'SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber';
                $resultFees = $connection2->prepare($sqlFees);
                $resultFees->execute($dataFees);
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo __($guid, 'Your request failed due to a database error.');
                echo '</div>';
                $feeOK = false;
            }

            if ($feeOK == true) {
                $feeTotal = 0;
                while ($rowFees = $resultFees->fetch()) {
                    $feeTotal += $rowFees['fee'];
                }

                $currency = getSettingByScope($connection2, 'System', 'currency');
                $enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
                $paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
                $paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
                $paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');

                if ($enablePayments == 'Y' and $paypalAPIUsername != '' and $paypalAPIPassword != '' and $paypalAPISignature != '' and $feeTotal > 0) {
                    $financeOnlinePaymentEnabled = getSettingByScope($connection2, 'Finance', 'financeOnlinePaymentEnabled');
                    $financeOnlinePaymentThreshold = getSettingByScope($connection2, 'Finance', 'financeOnlinePaymentThreshold');
                    if ($financeOnlinePaymentEnabled == 'Y') {
                        echo "<h3 style='margin-top: 40px'>";
                        echo __($guid, 'Online Payment');
                        echo '</h3>';
                        echo '<p>';
                        if ($financeOnlinePaymentThreshold == '' or $financeOnlinePaymentThreshold >= $feeTotal) {
                            echo sprintf(__($guid, 'Payment can be made by credit card, using our secure PayPal payment gateway. When you press Pay Online Now, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details. Once the transaction is complete you will be returned to %1$s.'), $_SESSION[$guid]['systemName']).' ';
                            echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/invoices_payOnlineProcess.php'>";
                            echo "<input type='hidden' name='gibbonFinanceInvoiceID' value='$gibbonFinanceInvoiceID'>";
                            echo "<input type='hidden' name='key' value='$key'>";
                            echo "<div class='linkTop'>";
                            echo $currency.$feeTotal." <input type='submit' value='Pay Online Now'>";
                            echo '</div>';
                            echo '</form>';
                        } else {
                            echo "<div class='error'>".__($guid, 'Payment is not permitted for this invoice, as the total amount is greater than the permitted online payment threshold.').'</div>';
                        }
                        echo '</p>';
                    } else {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed due to a database error.');
                        echo '</div>';
                    }
                } else {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                }
            }
        }
    }
}
