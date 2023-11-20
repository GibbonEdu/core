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

use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\SendReferenceRequestView;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Services\Format;

class SendReferenceRequest extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['referenceEmail'];

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
        return SendReferenceRequestView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('applicationReferee') == 'Y' && $this->checkEnabled($builder);
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Setup Template 
        $template = $this->template->setTemplate('Application Form Reference Request');
        $templateData = [
            'email'                       => $formData->get('referenceEmail'),
            'date'                        => Format::date(date('Y-m-d')),
            'applicationRefereeLink'      => $builder->getConfig('applicationRefereeLink'),
            'studentPreferredName'        => $formData->get('preferredName'),
            'studentSurname'              => $formData->get('surname'),
            'studentOfficialName'         => $formData->get('officialName'),
            'parentTitle'                 => $formData->get('parent1title'),
            'parentPreferredName'         => $formData->get('parent1preferredName'),
            'parentSurname'               => $formData->get('parent1surname'),
            'organisationAdmissionsEmail' => $this->session->get('organisationAdmissionsEmail'),
            'organisationAdmissionsName'  => $this->session->get('organisationAdmissionsName'),
        ];

        // Setup the email
        $this->mail->SetFrom($this->session->get('organisationAdmissionsEmail'), $this->session->get('organisationAdmissionsName'));
        $this->mail->AddAddress($formData->get('referenceEmail'));
        $this->mail->setDefaultSender($template->renderSubject($templateData));

        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody($templateData),
            'button' => [
                'url'  => $builder->getConfig('applicationRefereeLink'),
                'text' => __('Click Here'),
                'external' => true,
            ],
        ]);

        // Send the email
        $sent = $this->mail->Send();

        $this->setResult($sent);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $data)
    {
        // Cannot unsend what has been sent...
    }

    private function checkEnabled(FormBuilderInterface $builder)
    {
        if ($builder->getConfig('mode') == 'submit' && $builder->getConfig('applicationRefereeAutomatic') == 'N') {
            return false;
        }

        if ($builder->getConfig('mode') == 'process' && $builder->getConfig($this->getProcessName().'Enabled') != 'Y') {
            return false;
        }

        return true;
    }
}
