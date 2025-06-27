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
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Builder\FormPayment;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Domain\Finance\PaymentGateway;

$gibbonFormID = $_GET['form'] ?? $_GET['gibbonFormID'] ?? '';
$identifier = $_GET['id'] ?? $_GET['identifier'] ?? null;
$accessID = $_GET['acc'] ?? $_GET['accessID'] ?? '';
$accessToken = $_GET['tok'] ?? $session->get('admissionsAccessToken') ?? '';

$proceed = false;
$public = false;

if (!$session->has('gibbonPersonID')) {
    $public = true;
    if (!empty($accessID) && !empty($accessToken)) {
        $proceed = true;
    }
} else if (isActionAccessible($guid, $connection2, '/modules/Admissions/applicationFormView.php') != false) {
    $proceed = true;
}

if (!$proceed) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('My Application Forms'), 'modules/Admissions/applicationFormView.php', compact('accessID', 'accessToken'))
        ->add(__('Application Fee'));

    if (empty($accessID) || empty($identifier)) {
        echo Format::alert(__('You have not specified one or more required parameters.'));
        return;
    }

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);

    $account = $public
        ? $admissionsAccountGateway->getAccountByAccessToken($accessID, $accessToken)
        : $admissionsAccountGateway->getAccountByPerson($session->get('gibbonPersonID'));

    if ($public && empty($account)) {
        $page->addError(__('The application link does not match an existing record in our system. The record may have been removed or the link is no longer valid.'));
        $session->forget('admissionsAccessToken');
        return;
    } else {
        $session->set('admissionsAccessToken', $accessToken);
    }

    $application = $admissionsApplicationGateway->getApplicationByIdentifier($gibbonFormID, $identifier, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'] ?? 0);
    $paymentDetails = $admissionsApplicationGateway->getApplicationDetailsByID($application['gibbonAdmissionsApplicationID'] ?? '');

    $formPayment = $container->get(FormPayment::class);
    $formPayment->setForeignTable('gibbonAdmissionsApplication', $application['gibbonAdmissionsApplicationID']);

    $form = $container->get(FormGateway::class)->getByID($gibbonFormID);
    $formConfig = json_decode($form['config'] ?? '', true);

    if (empty($form) || empty($account) || empty($application) || empty($paymentDetails)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (!$formPayment->isEnabled()) {
        $page->addError(__('Online payment options are not available at this time.'));
        return;
    }

    $processPaymentRequired = !empty($formConfig['formProcessingFee']) && $paymentDetails['processingFeeComplete'] != 'Exemption';
    $processPaymentMade = !empty($application['gibbonPaymentIDProcess']) || $paymentDetails['processingFeeComplete'] == 'Y';

    $submitPaymentRequired = !empty($formConfig['formSubmissionFee']) && $paymentDetails['submissionFeeComplete'] != 'Exemption';
    $submitPaymentMade = !empty($application['gibbonPaymentIDSubmit']) || $paymentDetails['submissionFeeComplete'] == 'Y';

    $page->return->addReturns($formPayment->getReturnMessages() + ['error8' => __('A payment has already been made for this application form.')]);

    // APPLICATION PROCESSING FEE
    if ($processPaymentRequired) {
        $form = Form::create('action', $session->get('absoluteURL').'/modules/Admissions/applicationForm_payFeeProcess.php');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('accessID', $accessID);
        $form->addHiddenValue('gibbonFormID', $gibbonFormID);
        $form->addHiddenValue('identifier', $identifier);
        $form->addHiddenValue('feeType', 'formProcessingFee');
        $form->addHiddenValue('feeAmount', $formConfig['formProcessingFee']);

        $form->addRow()->addHeading('Application Fee', __('Application Fee'));

        $row = $form->addRow()->addContent(!$processPaymentMade ? $formPayment->getProcessingFeeInfo() : Format::alert(__('A payment has already been made for this application form.'), 'success'))->wrap('<p class="my-2">', '</p>');

        $row = $form->addRow();
            $row->addLabel('gibbonApplicationFormIDLabel', __('Application ID'));
            $row->addTextField('gibbonApplicationFormID')->readOnly()->setValue(intval($application['gibbonAdmissionsApplicationID']));

        $row = $form->addRow();
            $row->addLabel('applicationProcessFeeLabel', __('Application Processing Fee'));
            $row->addTextField('applicationProcessFee')->readOnly()->setValue($session->get('currency').$formConfig['formProcessingFee']);

        if (!$processPaymentMade) {
            $form->addRow()->addSubmit(__('Pay Online Now'));
        } else {
            $payment = $container->get(PaymentGateway::class)->getByID($application['gibbonPaymentIDProcess']);

            $row = $form->addRow();
            $row->addLabel('statusLabel', __('Status'));
            $row->addTextField('status')->readOnly()->setValue($payment['status'] ?? __('Complete'));

            $row = $form->addRow();
            $row->addLabel('timestampLabel', __('Date Paid'));
            $row->addTextField('timestamp')->readOnly()->setValue(Format::dateTimeReadable($payment['timestamp'] ?? ''));

            $row = $form->addRow();
            $row->addLabel('gatewayLabel', __('Payment Gateway'));
            $row->addTextField('gateway')->readOnly()->setValue($payment['gateway'] ?? '');
        }

        echo $form->getOutput();
    }


    // APPLICATION SUBMISSION FEE
    if ($submitPaymentRequired) {
        $form = Form::create('action', $session->get('absoluteURL').'/modules/Admissions/applicationForm_payFeeProcess.php');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('accessID', $accessID);
        $form->addHiddenValue('gibbonFormID', $gibbonFormID);
        $form->addHiddenValue('identifier', $identifier);
        $form->addHiddenValue('feeType', 'formSubmissionFee');
        $form->addHiddenValue('feeAmount', $formConfig['formSubmissionFee']);

        $form->addRow()->addHeading('Application Submission Fee', __('Application Submission Fee'));

        $row = $form->addRow()->addContent(!$submitPaymentMade ? Format::alert(__('It appears your application payment was not successfully completed when your form was initially submitted. You may use the online payment option below to pay the fees now.'), 'message') : Format::alert(__('A payment has already been made for this application form.'), 'success'))->wrap('<p class="my-2">', '</p>');

        $row = $form->addRow();
            $row->addLabel('gibbonApplicationFormIDLabel', __('Application ID'));
            $row->addTextField('gibbonApplicationFormID')->readOnly()->setValue(intval($application['gibbonAdmissionsApplicationID']));

        $row = $form->addRow();
            $row->addLabel('applicationProcessFeeLabel', __('Application Submission Fee'));
            $row->addTextField('applicationProcessFee')->readOnly()->setValue($session->get('currency').$formConfig['formSubmissionFee']);

        if (!$submitPaymentMade) {
            $form->addRow()->addSubmit(__('Pay Online Now'));
        } else {
            $payment = $container->get(PaymentGateway::class)->getByID($application['gibbonPaymentIDSubmit']);

            $row = $form->addRow();
            $row->addLabel('statusLabel', __('Status'));
            $row->addTextField('status')->readOnly()->setValue($payment['status'] ?? __('Complete'));

            $row = $form->addRow();
            $row->addLabel('timestampLabel', __('Date Paid'));
            $row->addTextField('timestamp')->readOnly()->setValue(Format::dateTimeReadable($payment['timestamp'] ?? ''));

            $row = $form->addRow();
            $row->addLabel('gatewayLabel', __('Payment Gateway'));
            $row->addTextField('gateway')->readOnly()->setValue($payment['gateway'] ?? '');
        }

        echo $form->getOutput();
    }
}
