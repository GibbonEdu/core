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

use Gibbon\Http\Url;
use Gibbon\Forms\Builder\FormPayment;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;

require_once '../../gibbon.php';

$pageNumber = $_REQUEST['page'] ?? 1;
$source = $_REQUEST['source'] ?? '';
$accessID = $_REQUEST['accessID'] ?? '';
$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$identifier = $_REQUEST['identifier'] ?? null;
$feeType = $_REQUEST['feeType'] ?? '';
$feeAmount = $_REQUEST['feeAmount'] ?? '';

$urlParams = compact('gibbonFormID', 'identifier', 'accessID', 'source', 'feeType');
$urlParams['page'] = $pageNumber;

$URL = $source == 'submission'
    ? Url::fromModuleRoute('Admissions', 'applicationForm')->withQueryParams($urlParams)
    : Url::fromModuleRoute('Admissions', 'applicationForm_payFee')->withQueryParams($urlParams);
$URLPayment = Url::fromHandlerRoute('modules/Admissions/applicationForm_payFeeProcess.php')->withQueryParams($urlParams);

if (empty($accessID) || empty($identifier) || empty($gibbonFormID)) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    $account = $container->get(AdmissionsAccountGateway::class)->getAccountByAccessID($accessID);

    // Setup the form data
    $formData = $container->get(ApplicationFormStorage::class)->setContext($gibbonFormID, null, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($identifier);
    
    // Setup the form payment class
    $formPayment = $container->get(FormPayment::class);
    $formPayment->setForm($gibbonFormID, $formData->identify($identifier));
    $formPayment->setFormFee($feeType);
    $formPayment->setReturnURL($URLPayment->withAbsoluteUrl());
    $formPayment->setCancelURL($URLPayment->withAbsoluteUrl());
    
    if (!$formPayment->incomingPayment()) {
        // Check that accounts exist and are accessible
        if (empty($account) || empty($formData)) {
            header("Location: {$URL->withReturn($source == 'submission' ? 'success5' : 'error0')}");
            exit;
        }

        // Check that payment is possible
        if (!$formPayment->isEnabled()) {
            header("Location: {$URL->withReturn($source == 'submission' ? 'success5' : 'error4')}");
            exit;
        }

        // Check that payment is possible
        if (!$formPayment->hasFormFee() || $formPayment->getFormFee() != $feeAmount) {
            header("Location: {$URL->withReturn($source == 'submission' ? 'success5' : 'error5')}");
            exit;
        }

        // Check that a payment ID has not already been recorded
        if ($formData->hasResult($formPayment->getFormFeeField())) {
            header("Location: {$URL->withReturn($source == 'submission' ? 'success5' : 'error8')}");
            exit;
        }

        // Attempt payment if everything is set up for it
        $return = $formPayment->requestPayment($formPayment->getFormFee(), __('Application Fee'));
        if (!empty($return)) {
            header("Location: {$URL->withReturn($source == 'submission' ? 'success5' : $return)}");
            exit;
        }
        
    } else {
        // Check everything is still setup for payment post-redirect
        if (!$formPayment->isEnabled() || !$formPayment->hasFormFee() || empty($account) || empty($formData)) {
            $formPayment->sendPaymentUncertainEmail();
            header("Location: {$URL->withReturn($source == 'submission' ? 'success2' : 'error4')}");
            exit;
        }

        // Finalize payment
        $return = $formPayment->confirmPayment();
        $result = $formPayment->getPaymentResult();
        $gibbonPaymentID = $result['gibbonPaymentID'];

        $formData->set($formPayment->getFormFeeClass().'Complete', !empty($gibbonPaymentID) ? 'Y' : 'N');
        $formData->setResult($formPayment->getFormFeeClass().'Result', $return);
        $formData->setResult($formPayment->getFormFeeField(), $gibbonPaymentID);
        $updated = $formData->save($identifier);

        if ($result && $result['success']) {
            // Payment was successful. Yeah!
            if (!empty($gibbonPaymentID) && !empty($updated)) {
                $receiptSent = $formPayment->sendPaymentSuccessEmail($account['email']);
                header("Location: {$URL->withQueryParam('receipt', $receiptSent)->withReturn('success1')}");
                exit;
            } else {
                $formPayment->sendPaymentSuccessNotRecordedEmail();
                header("Location: {$URL->withReturn($source == 'submission' ? 'success3' : 'warning2')}");
                exit;
            }
        } elseif ($result && $result['status'] == 'Cancelled') {
            // Payment was cancelled by the end user
            if ($source == 'submission') $formPayment->sendPaymentCancelled();
            header("Location: {$URL->withReturn($source == 'submission' ? 'success2' : $return)}");
            exit;
        } else {
            // Payment did not go through, or something else happened.
            $formPayment->sendPaymentFailedNotRecordedEmail();
            $submissionReturn = $return == 'error3' ? 'success4' : 'success2'; 
            header("Location: {$URL->withReturn($source == 'submission' ? $submissionReturn : $return)}");
            exit;
        }
    }
}
