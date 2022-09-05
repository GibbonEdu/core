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

namespace Gibbon\Forms\Builder\View;

use Gibbon\Forms\Form;
use Gibbon\Forms\Builder\AbstractFormView;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Domain\System\EmailTemplateGateway;
use Gibbon\Services\Format;

class SendAcceptanceEmailView extends AbstractFormView
{
    protected $emailTemplateGateway;
    
    public function __construct(EmailTemplateGateway $emailTemplateGateway)
    {
        $this->emailTemplateGateway = $emailTemplateGateway;
    }
    
    public function getHeading() : string
    {
        return 'Notification Options';
    }

    public function getName() : string
    {
        return __('Acceptance Email');
    }

    public function getDescription() : string
    {
        return '';
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('acceptanceEmailStudentDefault', __('Student Notification Default'))->description(__('Should student acceptance email be turned on or off by default.'));
            $row->addYesNo('acceptanceEmailStudentDefault')->required()->selected('N');

        $templates = $this->emailTemplateGateway->selectAvailableTemplatesByType('Admissions', 'Student Welcome Email')->fetchKeyPair();
        $row = $form->addRow();
            $row->addLabel('acceptanceEmailStudentTemplate', __('Student Acceptance Template'))->description(__('The content of email templates can be customized in System Admin > Email Templates.'));
            $row->addSelect('acceptanceEmailStudentTemplate')->fromArray($templates)->required()->placeholder();

        $row = $form->addRow();
            $row->addLabel('acceptanceEmailParentDefault', __('Parents Notification Default'))->description(__('Should parent acceptance email be turned on or off by default.'));
            $row->addYesNo('acceptanceEmailParentDefault')->required()->selected('N');

        $templates = $this->emailTemplateGateway->selectAvailableTemplatesByType('Admissions', 'Parent Welcome Email')->fetchKeyPair();
        $row = $form->addRow();
            $row->addLabel('acceptanceEmailParentTemplate', __('Parent Acceptance Template'))->description(__('The content of email templates can be customized in System Admin > Email Templates.'));
            $row->addSelect('acceptanceEmailParentTemplate')->fromArray($templates)->required()->placeholder();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $col = $form->addRow()->addColumn();
        $col->addSubheading($this->getName());

        // Student email result
        if (!empty($data->getAny('email'))) {
            $studentName = Format::name('', $data->get('preferredName'), $data->get('surname'), 'Student');
            if ($data->hasResult('acceptanceEmailStudentSent')) {
                $col->addContent(Format::alert(__('A welcome email was successfully sent to').' '.$studentName, 'success'));
            } else {
                $col->addContent(Format::alert(__('A welcome email could not be sent to').' '.$studentName, 'error'));
            }
        } else {
            $col->addContent(Format::alert(__('There are no student email addresses to send to.'), 'warning'));
        }
       
        // Parent email result
        if (!empty($data->getAny('parent1email'))) {
            $parent1Name = Format::name('', $data->get('parent1preferredName'), $data->get('parent1surname'), 'Parent');
            if ($data->hasResult('acceptanceEmailParentSent')) {
                $col->addContent(Format::alert(__('A welcome email was successfully sent to').' '.$parent1Name, 'success'));
            } else {
                $col->addContent(Format::alert(__('A welcome email could not be sent to').' '.$parent1Name, 'error'));
            }
        } else {
            $col->addContent(Format::alert(__('There are no parent email addresses to send to.'), 'warning'));
        }
    }
}
