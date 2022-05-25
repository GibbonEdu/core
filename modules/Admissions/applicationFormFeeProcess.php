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

use Gibbon\Http\Url;
use Gibbon\Forms\Builder\FormPayment;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

require_once '../../gibbon.php';

$accessID = $_REQUEST['accessID'] ?? '';
$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$identifier = $_REQUEST['identifier'] ?? null;
$page = $_REQUEST['page'] ?? 1;
$source = $_REQUEST['source'] ?? '';
$formFeeType = $_REQUEST['feeType'] ?? '';
$formFeeAmount = $_REQUEST['feeAmount'] ?? '';

$urlParams = compact('gibbonFormID', 'page', 'identifier', 'accessID', 'source', 'feeType');

$URL = $source == 'submission'
    ? Url::fromModuleRoute('Admissions', 'applicationForm')->withQueryParams($urlParams)
    : Url::fromModuleRoute('Admissions', 'applicationFormFee')->withQueryParams($urlParams);
$URLPayment = Url::fromHandlerRoute('modules/Admissions/applicationFormFeeProcess.php')->withQueryParams($urlParams);

if (empty($accessID) || empty($identifier) || empty($gibbonFormID) || empty($page)) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    $application = $admissionsApplicationGateway->getApplicationByIdentifier($gibbonFormID, $identifier);
    $account = $container->get(AdmissionsAccountGateway::class)->getAccountByAccessID($accessID);

    // Setup the form payment class
    $formPayment = $container->get(FormPayment::class);
    $formPayment->setForm($application['gibbonFormID'], $application['gibbonAdmissionsApplicationID']);
    $formPayment->setFormFee($formFeeType);
    $formPayment->setReturnURL($URLPayment->withAbsoluteUrl());
    $formPayment->setCancelURL($URLPayment->withAbsoluteUrl());
    
    if (!$formPayment->incomingPayment()) {
        // Check that accounts exist and are accessible
        if (empty($account) || empty($application) || $application['foreignTableID'] != $account['gibbonAdmissionsAccountID']) {
            header("Location: {$URL->withReturn('error0')}");
            exit;
        }

        // Check that payment is possible
        if (!$formPayment->isEnabled() || !$formPayment->hasFormFee() || $formPayment->getFormFee() != $formFeeAmount) {
            header("Location: {$URL->withReturn('error4')}");
            exit;
        }

        // Check that a payment ID has not already been recorded
        if (!empty($application[$formPayment->getFormFeeField()])) {
            header("Location: {$URL->withReturn('error8')}");
            exit;
        }

        // Attempt payment if everything is set up for it
        $return = $formPayment->requestPayment($formPayment->getFormFee(), __('Application Fee'));
        if (!empty($return)) {
            header("Location: {$URL->withReturn($return)}");
            exit;
        }
        
    } else {
        // Check everything is still setup for payment post-redirect
        if (!$formPayment->isEnabled() || !$formPayment->hasFormFee() || empty($account) || empty($application)) {
            $formPayment->sendPaymentUncertainEmail();
            header("Location: {$URL->withReturn('success2')}");
            exit;
        }

        // Finalize payment
        $return = $payment->confirmPayment();
        $result = $payment->getPaymentResult();
        $gibbonPaymentID = $result['gibbonPaymentID'];

        // Payment was successful. Yeah!
        if ($result && $result['success']) {
            $updated = $admissionsApplicationGateway->update($application['gibbonAdmissionsApplicationID'], [
                $formPayment->getFormFeeField() => $gibbonPaymentID,
            ]);

            if (!empty($gibbonPaymentID) && !empty($updated)) {
                $receiptSent = $formPayment->sendPaymentSuccessEmail($account['email']);
                header("Location: {$URL->withQueryParam('receipt', $receiptSent)->withReturn('success1')}");
                exit;
            } else {
                $formPayment->sendPaymentSuccessNotRecordedEmail();
                header("Location: {$URL->withReturn('success3')}");
                exit;
            }

        } else {
            // Payment did not go through, or something else happened.
            $formPayment->sendPaymentFailedNotRecordedEmail();
            header("Location: {$URL->withReturn($return)}");
            exit;
        }
    }
}
