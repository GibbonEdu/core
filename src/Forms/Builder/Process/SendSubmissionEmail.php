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
use Gibbon\Forms\Builder\View\SendSubmissionEmailView;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Services\Format;
use Gibbon\Http\Url;

class SendSubmissionEmail extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['email', 'parent1email'];
    protected $requiredFieldLogic = 'ANY';

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
        $data = $formData->getData();
        foreach ($data as $fieldName => $value) {
            $field = $builder->getField($fieldName);
            if (empty($field) || empty($value)) continue;
            if ($field['fieldGroup'] == 'PersonalDocuments') continue;
            if ($field['fieldGroup'] == 'RequiredDocuments') continue;

            $fieldGroup = $builder->getFieldGroup($field['fieldGroup']);

            if ($field['fieldType'] == 'heading' || $field['fieldType'] == 'subheading') {
                $details[$field['fieldType']] = __($field['label']);
            } else {
                $label = __($field['label']);
                if (stripos($fieldName, 'parent1') !== false && stripos($field['label'], 'Parent') === false) {
                    $label = __('Parent/Guardian').' 1 '.$label;
                } else if (stripos($fieldName, 'parent2') !== false && stripos($field['label'], 'Parent') === false) {
                    $label = __('Parent/Guardian').' 2 '.$label;
                }
                $details[$label]  = $fieldGroup->displayFieldValue($builder, $fieldName, $field, $data);
            }
        }

        // Setup Template 
        $template = $this->template->setTemplateByID($builder->getConfig('submissionEmailTemplate'));
        $templateData = [
            'email'                => $builder->getConfig('accountEmail'),
            'date'                 => Format::date(date('Y-m-d')),
            'applicationID'        => $builder->getConfig('foreignTableID'),
            'applicationName'      => $builder->getDetail('name'),
            'submissionDetails'    => Format::listDetails($details),
            'studentPreferredName' => $formData->get('preferredName'),
            'studentSurname'       => $formData->get('surname'),
            'studentOfficialName'  => $formData->get('officialName'),
            'parentTitle'          => $formData->get('parent1title'),
            'parentPreferredName'  => $formData->get('parent1preferredName'),
            'parentSurname'        => $formData->get('parent1surname'),
            'organisationAdmissionsEmail' => $this->session->get('organisationAdmissionsEmail'),
            'organisationAdmissionsName'  => $this->session->get('organisationAdmissionsName'),
        ];

        // Setup the email
        $this->mail->AddAddress($builder->getConfig('accountEmail'));
        if ($formData->has('parent1email')) {
            $this->mail->AddAddress($formData->has('parent1email'));
        }

        $this->mail->setDefaultSender($template->renderSubject($templateData));
        $this->mail->SetFrom($this->session->get('organisationAdmissionsEmail'), $this->session->get('organisationAdmissionsName'));
        
        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody(array_merge($data, $templateData)),
            'button' => [
                'url'  => Url::fromModuleRoute('Admissions', 'applicationFormView')
                    ->withQueryParams(['acc' => $builder->getConfig('accessID', ''), 'tok' => $builder->getConfig('accessToken', '')])
                    ->withAbsoluteUrl(),
                'text' => __('Access your Application Forms'),
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
