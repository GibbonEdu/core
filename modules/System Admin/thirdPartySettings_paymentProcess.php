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

use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Payment;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/thirdPartySettings.php';
$URLPayment = $session->get('absoluteURL').'/modules/System Admin/thirdPartySettings_paymentProcess.php?test=true';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $amount = $_GET['amount'] ?? 20.00;

    $payment = $container->get(Payment::class);
    $payment->setReturnURL($URLPayment);
    $payment->setCancelURL($URLPayment);
    $payment->setForeignTable('gibbonTest', 1234);

    if ($payment->incomingPayment()) {
        // Handle incoming payment
        $return = $payment->confirmPayment();
        $result = $payment->getPaymentResult();

        // Send a receipt
        if ($result && $result['success']) {
            $subject = __('Receipt from {organisation} via {system}', [
                'organisation' => $session->get('organisationNameShort'),
                'system' => $session->get('systemName'),
            ]);

            $mail = $container->get(Mailer::class);
            $mail->AddAddress($session->get('email'));
            $mail->setDefaultSender($subject);
            $mail->renderBody('mail/message.twig.html', [
                'title'  => __('Test Payment'),
                'body'   => '',
                'details' => [
                    __('Transaction ID') => $result['transactionID'],
                    __('Status')         => __('Paid'),
                    __('Amount Paid')    => $session->get('currency').$amount,
                    __('Date Paid')      => Format::dateTime(date('Y-m-d H:i:s')),
                ]
            ]);

            $mail->Send();
        }
        
        $URL .= '&return='.$return;
        header("Location: " . $URL);
        exit;
    } else if (!empty($amount)) {
        // No incoming payment, send a request
        $return = $payment->requestPayment($amount, __('Test Payment'));

        if (!empty($return)) {
            $URL .= '&return='.$return;
            header("Location: " . $URL);
            exit;
        }
    }
    
    $URL .= '&return=error1';
    header("Location: " . $URL);
}
