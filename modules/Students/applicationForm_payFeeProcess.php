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

use Gibbon\Services\Format;
use Gibbon\Domain\Students\ApplicationFormGateway;
use Gibbon\Contracts\Comms\Mailer;

include '../../gibbon.php';

include '../../modules/Finance/moduleFunctions.php';

$paid = $_GET['paid'] ?? '';
$paymentToken = $_GET['token'] ?? '';

$gibbonApplicationFormID = $_REQUEST['gibbonApplicationFormID'];
$key = $_REQUEST['key'];

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/applicationForm_payFee.php&gibbonApplicationFormID=$gibbonApplicationFormID&key=$key";

if (empty($key) || empty($gibbonApplicationFormID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $applicationFormGateway = $container->get(ApplicationFormGateway::class);
    $currency = getSettingByScope($connection2, 'System', 'currency');
    $feeTotal = getSettingByScope($connection2, 'Application Form', 'applicationProcessFee');

    $application = $applicationFormGateway->selectBy(['gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonApplicationFormHash' => $key])->fetch();
    if (empty($application)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($paid != 'Y' && empty($paymentToken)) {
        // Make payment
        $enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
        $paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
        $paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
        $paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');

        if ($enablePayments != 'Y' || empty($paypalAPIUsername) || empty($paypalAPIPassword) || empty($paypalAPISignature)) {
            $URL .= '&return=error4';
            header("Location: {$URL}");
            exit;
        }

        $_SESSION[$guid]['gatewayCurrencyNoSupportReturnURL'] = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/applicationForm_payFee.php&return=error3';

        $URL = $_SESSION[$guid]['absoluteURL']."/lib/paypal/expresscheckout.php?Payment_Amount=$feeTotal&return=".urlencode("modules/Students/applicationForm_payFeeProcess.php?return=success1&paid=Y&feeTotal=$feeTotal&gibbonApplicationFormID=$gibbonApplicationFormID&key=$key").'&fail='.urlencode("modules/Students/applicationForm_payFeeProcess.php?return=success2&paid=N&feeTotal=$feeTotal&gibbonApplicationFormID=$gibbonApplicationFormID&key=$key");
        header("Location: {$URL}");
        exit;

    } else {
        // Finalize payment
        $returnCode = $_GET['return'] ?? '';
        $paymentMade = $returnCode == 'success1' ? 'Y' : 'N';
        $paymentToken = $_GET['token'] ?? '';
        $paymentPayerID = $_GET['PayerID'] ?? '';
        $feeTotal = $_GET['feeTotal'] ?? '';

        if (empty($paymentToken) || empty($paymentPayerID) || empty($feeTotal)) {
            header("Location: {$URL}");
            exit;
        }

        // PROCEED AND FINALISE PAYMENT
        require '../../lib/paypal/paypalfunctions.php';

        // Ask paypal to finalise the payment
        $confirmPayment = confirmPayment($guid, $feeTotal, $paymentToken, $paymentPayerID);

        $ACK = $confirmPayment['ACK'];
        $paymentTransactionID = $confirmPayment['PAYMENTINFO_0_TRANSACTIONID'] ?? '';
        $paymentReceiptID = $confirmPayment['PAYMENTINFO_0_RECEIPTID'] ?? '';

        // Payment was successful. Yeah!
        if ($ACK == 'Success') {
            // Save payment details to gibbonPayment
            $gibbonPaymentID = setPaymentLog($connection2, $guid, 'gibbonApplicationForm', $gibbonApplicationFormID, 'Online', 'Complete', $feeTotal, 'Paypal', 'Success', $paymentToken, $paymentPayerID, $paymentTransactionID, $paymentReceiptID);

            $updated = $applicationFormGateway->update($gibbonApplicationFormID, ['paymentMade2' => $paymentMade, 'gibbonPaymentID2' => $gibbonPaymentID]);

            if (empty($gibbonPaymentID) || !$updated) {
                $URL .= '&return=success3';
                header("Location: {$URL}");
                exit;
            }

            // Send a receipt
            $subject = __('Receipt from {organisation} via {system}', [
                'organisation' => $_SESSION[$guid]['organisationNameShort'],
                'system' => $_SESSION[$guid]['systemName'],
            ]);
            $body = __('Thank you for your application fee payment. Please find attached a copy of the payment details for your record.');

            $mail = $container->get(Mailer::class);
            $mail->AddAddress($application['parent1email']);
            $mail->AddBCC($_SESSION[$guid]['organisationAdmissionsEmail']);
            $mail->setDefaultSender($subject);
            $mail->renderBody('mail/message.twig.html', [
                'title'  => __('Application Fee'),
                'body'   => $body,
                'details' => [
                    __('Application ID') => $gibbonApplicationFormID,
                    __('Status')         => __('Paid'),
                    __('Amount Paid')    => $currency.$feeTotal,
                    __('Date Paid')      => Format::dateTime(date('Y-m-d H:i:s')),
                ]
            ]);

            $receiptSent = $mail->Send();

            $URL .= '&return=success1&receipt='.$receiptSent;
            header("Location: {$URL}");
            exit;
        } else {
            // Payment did not go through, or something else happened.
            $URL .= '&return=success2';
            header("Location: {$URL}");
            exit;
        }
    }
}
