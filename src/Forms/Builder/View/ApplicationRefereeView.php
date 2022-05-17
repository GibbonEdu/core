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

class ApplicationRefereeView extends AbstractFormView
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
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists($this->getResultName())) return;

        $row = $form->addRow();

        if ($data->hasResult($this->getResultName())) {
            $row->addContent(__('An email was sent to {email}', ['email' => $data->get('email')]));
        } else {
            $row->addContent(__('Email failed to send to {email}', ['email' => $data->get('email')]));
        }
    }
}
