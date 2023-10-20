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
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Payment;
use Gibbon\Domain\Students\ApplicationFormGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

include '../../modules/Finance/moduleFunctions.php';

$gibbonApplicationFormID = $_REQUEST['gibbonApplicationFormID'];
$key = $_REQUEST['key'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Students/applicationForm_payFee.php&gibbonApplicationFormID=$gibbonApplicationFormID&key=$key";
$URLPayment = $session->get('absoluteURL')."/modules/Students/applicationForm_payFeeProcess.php?gibbonApplicationFormID=$gibbonApplicationFormID&key=$key";

if (empty($key) || empty($gibbonApplicationFormID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $applicationFormGateway = $container->get(ApplicationFormGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $currency = $settingGateway->getSettingByScope('System', 'currency');
    $feeTotal = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFee');

    $payment = $container->get(Payment::class);
    $payment->setReturnURL($URLPayment);
    $payment->setCancelURL($URLPayment);
    $payment->setForeignTable('gibbonApplicationForm', $gibbonApplicationFormID);

    $application = $applicationFormGateway->selectBy(['gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonApplicationFormHash' => $key])->fetch();
    if (empty($application)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if (!$payment->isEnabled()) {
        $URL .= '&return=error4';
        header("Location: {$URL}");
        exit;
    }

    if (!$payment->incomingPayment()) {
        // Make payment
        $return = $payment->requestPayment($feeTotal, __('Application Fee'));

        if (!empty($return)) {
            $URL .= '&return='.$return;
            header("Location: " . $URL);
            exit;
        }

    } else {
        // Finalize payment
        $return = $payment->confirmPayment();
        $result = $payment->getPaymentResult();
        $gibbonPaymentID = $result['gibbonPaymentID'];

        // Payment was successful. Yeah!
        if ($result['success']) {
            $updated = $applicationFormGateway->update($gibbonApplicationFormID, ['paymentMade2' => 'Y', 'gibbonPaymentID2' => $gibbonPaymentID]);

            // Send a receipt
            $subject = __('Receipt from {organisation} via {system}', [
                'organisation' => $session->get('organisationNameShort'),
                'system' => $session->get('systemName'),
            ]);
            $body = __('Thank you for your application fee payment. Please find attached a copy of the payment details for your record.');

            $mail = $container->get(Mailer::class);
            $mail->AddAddress($application['parent1email']);
            $mail->AddBCC($session->get('organisationAdmissionsEmail'));
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

            $URL .= '&return='.$return.'&receipt='.$receiptSent;
            header("Location: {$URL}");
            exit;
        } else {
            // Payment did not go through, or something else happened.
            $URL .= '&return='.$return;
            header("Location: {$URL}");
            exit;
        }
    }
}
