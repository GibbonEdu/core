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
use Gibbon\Services\Format;

class SendReferenceRequestView extends AbstractFormView
{
    public function getHeading() : string
    {
        return 'References';
    }

    public function getName() : string
    {
        return __('Application Form Referee');
    }

    public function getDescription() : string
    {
        return __('Send an email to the application form referee once the form has been submitted.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('applicationReferee', $this->getName())->description($this->getDescription());
            $row->addYesNo('applicationReferee')->selected('N')->required();

        $form->toggleVisibilityByClass('referee')->onSelect('applicationReferee')->when('Y');

        $row = $form->addRow()->addClass('referee');
            $row->addLabel('applicationRefereeLink', __('Application Form Referee Link'))->description(__("Link to an external form that will be emailed to a referee of the applicant's choosing."));
            $row->addURL('applicationRefereeLink');

        $row = $form->addRow()->addClass('referee');
            $row->addLabel('applicationRefereeAutomatic', __('Automatic Reference Request'))->description(__('Should a reference request be sent automatically when the form is submitted? Otherwise, it can be sent manually through the application process tab.'));
            $row->addYesNo('applicationRefereeAutomatic')->required();
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        if (!$data->has('referenceEmail')) return;

        $col = $form->addRow()->addColumn();
        $col->addLabel($this->getResultName(), $this->getName());

        if ($data->hasResult($this->getResultName())) {
            $col->addContent(Format::alert(__('An email was sent to {email}', ['email' => $data->get('referenceEmail')]), 'message'));
        } else {
            $col->addContent(Format::alert(__('Email failed to send to {email}', ['email' => $data->get('referenceEmail')]), 'warning'));
        }
    }
}
