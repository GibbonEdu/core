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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\FormProcessException;
use Gibbon\Forms\Builder\Process\ViewableProcess;
use Gibbon\Forms\Builder\View\NewStudentDetailsView;
use Gibbon\Http\Url;
use Gibbon\Services\Format;

class NewStudentDetails extends AbstractFormProcess implements ViewableProcess
{
    protected $requiredFields = ['preferredName', 'surname'];

    private $session;
    private $mail;
    private $userGateway;
    
    public function __construct(UserGateway $userGateway, Session $session, Mailer $mail)
    {
        $this->session = $session;
        $this->mail = $mail;
        $this->userGateway = $userGateway;
    }

    public function getViewClass() : string
    {
        return NewStudentDetailsView::class;
    }
    
    public function isEnabled(FormBuilderInterface $builder)
    {
        return $builder->getConfig('newStudentDetails') == 'Y';
    }

    public function process(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) {
            return;
        }

        // Set and assign default values
        $this->setLastSchool($formData);
        $this->setStudentEmail($builder, $formData);
        $this->setStudentWebsite($builder, $formData);

        $this->userGateway->update($formData->get('gibbonPersonIDStudent'), [
            'email'          => $formData->get('email'),
            'emailAlternate' => $formData->get('emailAlternate'),
            'website'        => $formData->get('website', ''),
            'lastSchool'     => $formData->get('lastSchool', ''),
        ]);

        // Setup the content
        $subject = sprintf(__('Create Student Email/Websites for %1$s at %2$s'), $this->session->get('systemName'), $this->session->get('organisationNameShort'));
        $body = sprintf(__('Please create the following for new student %1$s.'), Format::name('', $formData->get('preferredName'), $formData->get('surname'), 'Student'))."<br/><br/>";

        $list = [];

        if ($builder->hasConfig('studentDefaultEmail')) {
            $list[__('Email')] = $formData->get('email');
        }
        if ($builder->hasConfig('studentDefaultWebsite')) {
            $list[__('Website')] = $formData->get('website');
        }
        if ($formData->hasAll(['gibbonSchoolYearIDEntry', 'schoolYearName'])) {
            $list[__('School Year')] = $formData->get('schoolYearName');
        }
        if ($formData->hasAll(['gibbonYearGroupIDEntry', 'yearGroupName'])) {
            $list[__('Year Group')] = $formData->get('yearGroupName');
        }
        if ($formData->hasAll(['gibbonYearGroupIDEntry', 'formGroupName'])) {
            $list[__('Form Group')] = $formData->get('formGroupName');
        }
        if ($formData->has('dateStart')) {
            $list[__('Start Date')] = Format::date($formData->get('dateStart'));
        }

        $body .= Format::listDetails($list);

        // Setup the email
        $this->mail->SetFrom($this->session->get('organisationEmail'), $this->session->get('organisationName'));
        $this->mail->AddAddress($this->session->get('organisationAdministratorEmail'));
        $this->mail->setDefaultSender($subject);
        
        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => $subject,
            'body'   => $body,
            'button' => [
                'url'  => Url::fromModuleRoute('User Admin', 'user_manage_edit')
                    ->withQueryParam('gibbonPersonID', $formData->get('gibbonPersonIDStudent'))
                    ->withAbsoluteUrl(),
                'text' => __('View Details'),
                'external' => true,
            ],
        ]);

        // Send the email
        $sent = $this->mail->Send();
        $this->setResult($sent);
    }

    public function rollback(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$formData->has('gibbonPersonIDStudent')) return;

        $this->userGateway->update($formData->get('gibbonPersonIDStudent'), [
            'email'          => $formData->getData('email'),
            'emailAlternate' => $formData->getData('emailAlternate'),
            'website'        => $formData->getData('website', ''),
            'lastSchool'     => $formData->getData('lastSchool', ''),
        ]);
    }

    /**
     * Determine the last school based on dates provided
     *
     * @param FormDataInterface $formData
     */
    private function setLastSchool(FormDataInterface $formData)
    {
        if ($formData->get('schoolDate2', date('Y-m-d')) > $formData->get('schoolDate1', date('Y-m-d'))) {
            $formData->set('lastSchool', $formData->get('schoolName2'));
        } else {
            $formData->set('lastSchool', $formData->get('schoolName1'));
        }
    }

    /**
     * Set default email address for student
     *
     * @param FormBuilderInterface $builder
     * @param FormDataInterface $formData
     */
    private function setStudentEmail(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$builder->hasConfig('studentDefaultEmail')) return;

        $formData->set('emailAlternate', $formData->get('email'));
        $formData->set('email', str_replace('[username]', $formData->get('username'), $builder->getConfig('studentDefaultEmail')));
    }

    /**
     * Set default website address for student
     *
     * @param FormBuilderInterface $builder
     * @param FormDataInterface $formData
     */
    private function setStudentWebsite(FormBuilderInterface $builder, FormDataInterface $formData)
    {
        if (!$builder->hasConfig('studentDefaultWebsite')) return;

        $formData->set('website', str_replace('[username]', $formData->get('username'), $builder->getConfig('studentDefaultWebsite')));
    }
}
