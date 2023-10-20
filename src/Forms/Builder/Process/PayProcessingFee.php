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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Services\Payment;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\PayProcessingFeeView;
use Gibbon\Forms\Builder\Exception\MissingFieldException;

class PayProcessingFee extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['Payment Gateway', 'parent1email'];

    private $session;
    private $payment;
    private $mail;
    private $template;

    public function __construct(Session $session, Payment $payment, Mailer $mail, EmailTemplate $template)
    {
        $this->session = $session;
        $this->payment = $payment;
        $this->mail = $mail;
        $this->template = $template;
    }

    public function getViewClass() : string
    {
        return PayProcessingFeeView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->hasConfig('formProcessingFee') && $this->checkEnabled($builder);
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        $processingFee = $builder->getConfig('formProcessingFee');

        if (!is_numeric($processingFee) || $processingFee <= 0) return;

        $link = Url::fromModuleRoute('Admissions', 'applicationForm_payFee')
            ->withAbsoluteUrl()
            ->withQueryParams([
                'acc'  => $builder->getConfig('accessID', ''),
                'tok'  => $builder->getConfig('accessToken', ''),
                'id'   => $builder->getConfig('identifier', ''),
                'form' => $builder->getFormID(),
            ]);

        // Setup Template 
        $template = $this->template->setTemplateByID($builder->getConfig('formProcessingEmailTemplate'));
        $templateData = [
            'email'                => $formData->get('parent1email'),
            'date'                 => Format::date(date('Y-m-d')),
            'link'                 => (string)$link,
            'applicationID'        => intval($builder->getConfig('foreignTableID')),
            'applicationName'      => $builder->getDetail('name'),
            'applicationFee'       => $this->session->get('currency').$processingFee,
            'studentPreferredName' => $formData->get('preferredName'),
            'studentSurname'       => $formData->get('surname'),
            'studentOfficialName'  => $formData->get('officialName'),
            'parentTitle'          => $formData->get('parent1title'),
            'parentPreferredName'  => $formData->get('parent1preferredName'),
            'parentSurname'        => $formData->get('parent1surname'),
            'organisationAdmissionsName'  => $this->session->get('organisationAdmissionsName'),
            'organisationAdmissionsEmail' => $this->session->get('organisationAdmissionsEmail'),
        ];

        // Setup the email
        $this->mail->AddAddress($formData->get('parent1email'));
        $this->mail->setDefaultSender($template->renderSubject($templateData));
        $this->mail->SetFrom($this->session->get('organisationAdmissionsEmail'), $this->session->get('organisationAdmissionsName'));
        
        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody($templateData),
            'details' => [
                __('Application Form')           => $builder->getDetail('name'),
                __('Application ID')             => intval($builder->getConfig('foreignTableID')),
                __('Application Processing Fee') => $this->session->get('currency').$processingFee,
            ],
            'button' => [
                'url'  => $link,
                'text' => __('Pay Online'),
                'external' => true,
            ],
        ]);

        // Send the email
        $sent = $this->mail->Send();
        $this->setResult($sent);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Cannot unsend what has been sent...
    }

    public function verify(FormBuilderInterface $builder, FormDataInterface $formData = null)
    {
        if (!$this->payment->isEnabled()) {
            throw new MissingFieldException('Payment Gateway');
        }

        if (!$builder->hasField('parent1email')) {
            throw new MissingFieldException('parent1email');
        }
    }

    private function checkEnabled(FormBuilderInterface $builder)
    {
        if ($builder->getConfig('mode') == 'process' && $builder->getConfig($this->getProcessName().'Enabled') != 'Y') {
            return false;
        }

        return true;
    }
}
