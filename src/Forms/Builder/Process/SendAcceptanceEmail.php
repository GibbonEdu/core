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

namespace Gibbon\Forms\Builder\Process;

use Gibbon\Comms\EmailTemplate;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\SendAcceptanceEmailView;
use Gibbon\Services\Format;

class SendAcceptanceEmail extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['email'];

    private $session;
    private $mail;
    private $template;

    public function __construct(Session $session, Mailer $mail, EmailTemplate $template)
    {
        $this->session = $session;
        $this->mail = $mail;
        $this->template = $template;
    }

    public function getViewClass() : string
    {
        return SendAcceptanceEmailView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return true;
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Inform Student?
        if ($formData->getResult('informStudent') == 'Y' && $formData->has('email')) {
            $this->sendWelcomeEmail($builder, $formData, 'acceptanceEmailStudent');
        }

        // Inform Parent 1?
        if ($formData->getResult('informParent') == 'Y' && $formData->has('parent1email')) {
            $this->sendWelcomeEmail($builder, $formData, 'acceptanceEmailParent', 'parent1');
        }

        // Inform Parent 2?
        if ($formData->getResult('informParent') == 'Y' && $formData->has('parent2email')) {
            $this->sendWelcomeEmail($builder, $formData, 'acceptanceEmailParent', 'parent2');
        }
    } 

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Cannot unsend what has been sent...
    }

    protected function sendWelcomeEmail(FormBuilderInterface $builder, FormDataInterface $formData, $type, $prefix = '')
    {
        // Setup Template 
        $template = $this->template->setTemplateByID($builder->getConfig($type.'Template'));
        $templateData = [
            'date'                        => Format::date(date('Y-m-d')),
            'username'                    => $formData->getResult($prefix.'username'),
            'password'                    => $formData->getResult($prefix.'password'),
            'applicationID'               => $formData->get('gibbonAdmissionsApplicationID'),
            'applicationName'             => $builder->getDetail('name'),
            'studentPreferredName'        => $formData->get('preferredName'),
            'studentSurname'              => $formData->get('surname'),
            'parentTitle'                 => $formData->get(!empty($prefix) ? $prefix.'Title' : 'parent1Title'),
            'parentPreferredName'         => $formData->get(!empty($prefix) ? $prefix.'PreferredName' : 'parent1PreferredName'),
            'parentSurname'               => $formData->get(!empty($prefix) ? $prefix.'Surname' : 'parent1Surname'),
            'organisationAdmissionsName'  => $this->session->get('organisationAdmissionsName'),
            'organisationAdmissionsEmail' => $this->session->get('organisationAdmissionsEmail'),
        ];

        // Setup the email
        $this->mail->SetFrom($this->session->get('organisationAdmissionsEmail'), $this->session->get('organisationAdmissionsName'));
        $this->mail->SetReplyTo($this->session->get('organisationAdmissionsEmail'));
        $this->mail->setDefaultSender($template->renderSubject($templateData));

        $this->mail->AddAddress($formData->get($prefix.'email'));
        if (!empty($formData->get($prefix.'emailAlternate'))) {
            $this->mail->AddAddress($formData->get($prefix.'emailAlternate'));
        }

        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody($templateData),
        ]);

        // Send the email
        $sent = $this->mail->Send();
        $formData->setResult($type.'Sent', $sent);
    }
}
