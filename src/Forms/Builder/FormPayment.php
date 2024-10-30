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

namespace Gibbon\Forms\Builder;

use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Session;
use Gibbon\Services\Payment\Payment;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\PaymentGateway;

class FormPayment extends Payment
{
    protected $mail;
    protected $formGateway;

    protected $form;
    protected $formConfig;
    protected $feeTotal = 0;
    protected $feeField = '';
    protected $feeClass = '';

    public function __construct(Session $session, Mailer $mail, SettingGateway $settingGateway, PaymentGateway $paymentGateway, FormGateway $formGateway)
    {
        $this->mail = $mail;
        $this->formGateway = $formGateway;

        parent::__construct($session, $settingGateway, $paymentGateway);
    }

    public function setForm(string $gibbonFormID, ?string $foreignTableID = null)
    {
        // Get the form details, to confirm fees and IDs
        $this->form = $this->formGateway->getByID($gibbonFormID);
        $this->formConfig = json_decode($this->form['config'] ?? '', true);

        // Set which foreign table these payments will be attached to
        if (!empty($foreignTableID)) {
            $foreignTable = $this->form['type'] == 'Application' ? 'gibbonAdmissionsApplication' : 'gibbonFormSubmission';
            $this->setForeignTable($foreignTable, $foreignTableID);
        }

        return $this;
    }

    public function setFormFee(string $formFeeType)
    {
        if (empty($this->formConfig) || empty($formFeeType)) {
            return;
        }

        // Set the fee total and field
        $this->feeField = $formFeeType == 'formSubmissionFee' ? 'gibbonPaymentIDSubmit' : 'gibbonPaymentIDProcess';
        $this->feeClass = $formFeeType == 'formSubmissionFee' ? 'PaySubmissionFee' : 'PayProcessingFee';
        $this->feeTotal = floatval($this->formConfig[$formFeeType] ?? 0);

        return $this->feeTotal;
    }

    public function hasFormFee()
    {
        return !empty($this->feeTotal) && is_numeric($this->feeTotal) && $this->feeTotal > 0;
    }

    public function getFormFee()
    {
        return $this->feeTotal;
    }

    public function getFormFeeField()
    {
        return $this->feeField;
    }

    public function getFormFeeClass()
    {
        return $this->feeClass;
    }

    public function getFeeInfo()
    {
        $formSubmissionFee = $this->formConfig['formSubmissionFee'] ?? '';
        $formProcessingFee = $this->formConfig['formProcessingFee'] ?? '';
        if (!empty($formSubmissionFee) || !empty($formProcessingFee)) {
            $feeInfo = '';
            if ($formSubmissionFee > 0 and is_numeric($formSubmissionFee)) {
                $feeInfo .= __('Please note that there is an application fee of:').' <b><u>'.$this->currency.$formSubmissionFee.'</u></b>. ';
            }
            if ($formProcessingFee > 0 and is_numeric($formProcessingFee)) {
                $feeInfo .= __('A processing fee of {fee} may be sent by email after your application has been submitted.', ['fee' => '<b><u>'.$this->currency.$formProcessingFee.'</u></b>']);
            }
            if ($this->isEnabled() && !empty($formSubmissionFee)) {
                $feeInfo .= ' '.__('Payment must be made by credit card, using our secure {gateway} payment gateway. When you press Submit at the end of this form, you will be directed to {gateway} in order to make payment. During this process we do not see or store your credit card details.', ['gateway' => $this->paymentGatewaySetting]);
            }

            return Format::alert($feeInfo, 'warning');
        }

        return '';
    }

    public function getProcessingFeeInfo()
    {
        return sprintf(__('Payment can be made by credit card, using our secure {gateway} payment gateway. When you press Pay Online Now, you will be directed to {gateway} in order to make payment. During this process we do not see or store your credit card details. Once the transaction is complete you will be returned to %1$s.', ['gateway' => $this->paymentGatewaySetting]), $this->session->get('systemName'));
    }

    public function sendPaymentSuccessEmail(string $email) : bool
    {
        $subject = __('Receipt from {organisation} via {system}', [
            'organisation' => $this->session->get('organisationNameShort'),
            'system'       => $this->session->get('systemName'),
        ]);
        $body = __('Thank you for your application fee payment. Please find attached a copy of the payment details for your record.');

        return $this->sendEmail($email, $subject, $body, $this->getPaymentDetails());
    }

    public function sendPaymentUncertainEmail() : bool
    {
        $body = __('Payment via {gateway} may or may not have been successful, but has not been recorded either way due to a system error. Please check your {gateway} account for details. The following may be useful:', ['gateway' => $this->paymentGatewaySetting]).Format::listDetails($this->getPaymentDetails());

        return $this->sendEmail($this->session->get('organisationAdmissionsEmail'), $this->getEmailSubject(), $body, $this->getPaymentDetails());
    }

    public function sendPaymentSuccessNotRecordedEmail() : bool
    {
        $body = __('Payment via {gateway} was successful, but has not been recorded due to a system error. Please check your {gateway} account for details. The following may be useful:', ['gateway' => $this->paymentGatewaySetting]);

        return $this->sendEmail($this->session->get('organisationAdmissionsEmail'), $this->getEmailSubject(), $body, $this->getPaymentDetails());
    }

    public function sendPaymentFailedNotRecordedEmail() : bool
    {
        $body = __('Payment via {gateway} was unsuccessful, and has also not been recorded due to a system error. Please check your {gateway} account for details. The following may be useful:', ['gateway' => $this->paymentGatewaySetting]);

        return $this->sendEmail($this->session->get('organisationAdmissionsEmail'), $this->getEmailSubject(), $body, $this->getPaymentDetails());
    }

    public function sendPaymentCancelled() : bool
    {
        $body = __('Payment via {gateway} was cancelled by the user before it could be completed. No charges have been processed. The following may be useful:', ['gateway' => $this->paymentGatewaySetting]);

        return $this->sendEmail($this->session->get('organisationAdmissionsEmail'), $this->getEmailSubject(), $body, $this->getPaymentDetails());
    }

    protected function getEmailSubject()
    {
        return $this->session->get('organisationNameShort').' '.__('{system} Application Form Payment Issue', ['system' => $this->session->get('systemName')]);
    }

    protected function getPaymentDetails() : array
    {
        $result = $this->getPaymentResult();
        if (!empty($result['success']) && $result['success'] == true) {
            return [
                __('Application ID') => $this->foreignTableID,
                __('Status')         => __('Paid'),
                __('Amount Paid')    => $this->currency.$this->feeTotal,
                __('Date Paid')      => Format::dateTime(date('Y-m-d H:i:s')),
            ];
        } else {
            return [
                __('Application ID')  => $this->foreignTableID,
                __('Application Fee') => $this->currency.$this->feeTotal,
                __('Status')          => $result['status'] ?? '',
                __('Payment ID')      => $result['gibbonPaymentID'] ?? '',
                __('Transaction ID')  => $result['transactionID'] ?? '',
                __('Payment Token')   => $result['token'] ?? '',
                __('Payee')           => $result['payer'] ?? '',
                __('Amount')          => $result['amount'] ?? '',
            ];
        }
    }

    protected function sendEmail($to, $subject, $body, $details = []) : bool
    {
        $this->mail->Subject = $subject;
        $this->mail->SetFrom($this->session->get('organisationAdmissionsEmail'), $this->session->get('organisationAdmissionsName'));
        $this->mail->AddAddress($to);
        
        $this->mail->renderBody('mail/message.twig.html', [
            'title'   => $subject,
            'body'    => $body,
            'details' => $details,
        ]);

        if ($to != $this->session->get('organisationAdmissionsEmail')) {
            $this->mail->AddBCC($this->session->get('organisationAdmissionsEmail'));
        }

        return $this->mail->Send();
    }
}
