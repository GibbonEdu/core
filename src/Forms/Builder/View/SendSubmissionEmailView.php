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
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Domain\System\EmailTemplateGateway;
use Gibbon\Services\Format;

class SendSubmissionEmailView extends AbstractFormView
{
    protected $emailTemplateGateway;
    
    public function __construct(EmailTemplateGateway $emailTemplateGateway)
    {
        $this->emailTemplateGateway = $emailTemplateGateway;
    }
    
    public function getHeading() : string
    {
        return 'Submission Options';
    }

    public function getName() : string
    {
        return __('Submission Email');
    }

    public function getDescription() : string
    {
        return __('Send an email to the user once the form has been submitted.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('sendSubmissionEmail', $this->getName())->description($this->getDescription());
            $row->addYesNo('sendSubmissionEmail')->selected('N')->required();

        $form->toggleVisibilityByClass('submissionEmailTemplate')->onSelect('sendSubmissionEmail')->when('Y');

        $templates = $this->emailTemplateGateway->selectAvailableTemplatesByType('Admissions', 'Application Form Confirmation')->fetchKeyPair();
        $row = $form->addRow()->addClass('submissionEmailTemplate');
            $row->addLabel('submissionEmailTemplate', __('Email Template'))->description(__('The content of email templates can be customized in System Admin > Email Templates.'));
            $row->addSelect('submissionEmailTemplate')->fromArray($templates)->required()->placeholder();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        if (!$data->has('email')) return;

        $col = $form->addRow()->addColumn();
        $col->addLabel($this->getResultName(), $this->getName());

        if ($data->hasResult($this->getResultName())) {
            $col->addContent(Format::alert(__('An email was sent to {email}', ['email' => $data->get('email')]), 'success'));
        } else {
            $col->addContent(Format::alert(__('Email failed to send to {email}', ['email' => $data->get('email')]), 'warning'));
        }
    }
}
