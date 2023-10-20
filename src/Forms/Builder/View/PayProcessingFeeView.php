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

namespace Gibbon\Forms\Builder\View;

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Payment;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Domain\System\EmailTemplateGateway;

class PayProcessingFeeView extends AbstractFormView
{
    protected $payment;
    protected $emailTemplateGateway;
    
    public function __construct(Payment $payment, EmailTemplateGateway $emailTemplateGateway)
    {
        $this->payment = $payment;
        $this->emailTemplateGateway = $emailTemplateGateway;
    }

    public function getHeading() : string
    {
        return 'Payment Options';
    }

    public function getName() : string
    {
        return __('Application Processing Fee');
    }

    public function getDescription() : string
    {
        return __('Send a processing fee request to the parent by email.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow()->setHeading($this->getHeading());
            $row->addLabel('formProcessingFee', $this->getName())->description(__('An optional fee that is paid before processing the application form. Sent by staff via the Manage Applications page.'));
            $row->addCurrency('formProcessingFee');

        $templates = $this->emailTemplateGateway->selectAvailableTemplatesByType('Admissions', 'Application Form Fee Request')->fetchKeyPair();
        $row = $form->addRow()->addClass('formProcessingEmailTemplate');
            $row->addLabel('formProcessingEmailTemplate', __('Application Processing Email Template'))->description(__('The content of email templates can be customized in System Admin > Email Templates.'));
            $row->addSelect('formProcessingEmailTemplate')->fromArray($templates)->required()->placeholder();
    }

    public function display(Form $form, FormDataInterface $formData)
    {
        if (!$formData->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addLabel($this->getResultName(), $this->getName());

        $messages = $this->payment->getReturnMessages();

        if ($formData->hasResult('gibbonPaymentIDProcess')) {
            $col->addContent(Format::alert($messages[Payment::RETURN_SUCCESS], 'success'));
        } else {
            $return = $formData->getResult($this->getResultName());
            $col->addContent(Format::alert($messages[$return] ?? $messages[Payment::RETURN_INCOMPLETE], stripos($return, 'error') !== false ? 'error' : 'warning'));
        }
    }
}
