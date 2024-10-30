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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Contracts\Services\Payment;

//Get variables
$gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'] ?? '';
$key = $_GET['key'] ?? '';

$payment = $container->get(Payment::class);
$payment->setForeignTable('gibbonFinanceInvoice', $gibbonFinanceInvoiceID);
$page->return->addReturns($payment->getReturnMessages());

if (!isset($_GET['return']) || stripos($_GET['return'], 'success') === false) { //No return message, so must just be landing to make payment
    //Check variables
    if ($gibbonFinanceInvoiceID == '' or $key == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        //Check for record
        $keyReadFail = false;
        try {
            $dataKeyRead = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'key' => $key);
            $sqlKeyRead = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND `key`=:key AND status='Issued'";
            $resultKeyRead = $connection2->prepare($sqlKeyRead);
            $resultKeyRead->execute($dataKeyRead);
        } catch (PDOException $e) {
            $page->addError(__('Your request failed due to a database error.'));
        }

        if ($resultKeyRead->rowCount() != 1) { //If not exists, report error
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
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
                $page->addError(__('Your request failed due to a database error.'));
                $feeOK = false;
            }

            if ($feeOK == true) {
                $feeTotal = 0;
                while ($rowFees = $resultFees->fetch()) {
                    $feeTotal += $rowFees['fee'];
                }

                if ($payment->isEnabled() and $feeTotal > 0) {
                    $settingGateway = $container->get(SettingGateway::class);
                    $financeOnlinePaymentEnabled = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentEnabled');
                    $financeOnlinePaymentThreshold = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentThreshold');
                    $paymentGateway = $settingGateway->getSettingByScope('System', 'paymentGateway');
                    if ($financeOnlinePaymentEnabled == 'Y') {
                        echo "<h3 style='margin-top: 40px'>";
                        echo __('Online Payment');
                        echo '</h3>';
                        echo '<p>';
                        if ($financeOnlinePaymentThreshold == '' or $financeOnlinePaymentThreshold >= $feeTotal) {
                            echo sprintf(__('Payment can be made by credit card, using our secure %2$s payment gateway. When you press Pay Online Now, you will be directed to %2$s in order to make payment. During this process we do not see or store your credit card details. Once the transaction is complete you will be returned to %1$s.'), $session->get('systemName'), $paymentGateway).' ';

                            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/invoices_payOnlineProcess.php');

                            $form->addHiddenValue('address', $session->get('address'));
                            $form->addHiddenValue('gibbonFinanceInvoiceID', $gibbonFinanceInvoiceID);
                            $form->addHiddenValue('key', $key);

                            $row = $form->addRow();
                                $row->addContent($session->get('currency').$feeTotal);
                                $row->addSubmit(__('Pay Online Now'));

                            echo $form->getOutput();
                        } else {
                            echo "<div class='error'>".__('Payment is not permitted for this invoice, as the total amount is greater than the permitted online payment threshold.').'</div>';
                        }
                        echo '</p>';
                    } else {
                        $page->addError(__('Your request failed due to a database error.'));
                    }
                } else {
                    $page->addError(__('Your request failed due to a database error.'));
                }
            }
        }
    }
}
