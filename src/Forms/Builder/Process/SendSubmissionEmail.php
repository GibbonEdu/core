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

use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\View\SendSubmissionEmailView;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Services\Format;
use Gibbon\Http\Url;

class SendSubmissionEmail extends AbstractFormProcess implements ViewableProcess
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
        return SendSubmissionEmailView::class;
    }

    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('sendSubmissionEmail') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        // Setup Details
        $details = [];
        foreach ($formData->getData() as $fieldName => $value) {
            $field = $builder->getField($fieldName);
            if (empty($field)) continue;

            if ($field['fieldType'] == 'heading' || $field['fieldType'] == 'subheading') {
                $details[$field['fieldType']] = __($field['label']);
            } else {
                $details[__($field['label'])]  =$value;
            }
        }

        // Setup Template 
        $template = $this->template->setTemplateByID($builder->getConfig('submissionEmailTemplate'));
        $templateData = [
            'email'                => $formData->get('email'),
            'date'                 => Format::date(date('Y-m-d')),
            'applicationID'        => $builder->getConfig('foreignTableID'),
            'applicationName'      => $builder->getDetail('name'),
            'submissionDetails'    => Format::listDetails($details),
            'studentPreferredName' => $formData->get('preferredName'),
            'studentSurname'       => $formData->get('surname'),
            'studentOfficialName'  => $formData->get('officialName'),
            'parentTitle'          => $formData->get('parent1Title'),
            'parentPreferredName'  => $formData->get('parent1PreferredName'),
            'parentSurname'        => $formData->get('parent1Surname'),
            'organisationAdmissionsEmail' => $this->session->get('organisationAdmissionsEmail'),
            'organisationAdmissionsName'  => $this->session->get('organisationAdmissionsName'),
        ];

        // Setup the email
        $this->mail->AddAddress($formData->get('email'));
        $this->mail->setDefaultSender($template->renderSubject($templateData));
        $this->mail->SetFrom($this->session->get('organisationAdmissionsEmail'), $this->session->get('organisationAdmissionsName'));
        
        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody($templateData),
            'button' => [
                'url'  => Url::fromModuleRoute('Admissions', 'applicationFormView')
                    ->withQueryParams(['acc' => $builder->getConfig('accessID', ''), 'tok' => $builder->getConfig('accessToken', '')])
                    ->withAbsoluteUrl(),
                'text' => __('Access your Account'),
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
}
