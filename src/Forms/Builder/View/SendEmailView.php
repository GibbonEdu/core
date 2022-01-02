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
use Gibbon\Forms\Builder\View\FormViewInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;

class SendEmailView implements FormViewInterface
{
    public function getName()
    {
        return __('Send Email');
    }

    public function getDescription()
    {
        return __('Enables this form to send an email once the form has been submitted.');
    }

    public function configure(Form $form)
    {
        $row = $form->addRow();
            $row->addLabel('sendEmail', $this->getName())->description($this->getDescription());
            $row->addYesNo('sendEmail')->selected('N');
    }

    public function display(Form $form, FormDataInterface $data)
    {
        if (!$data->exists('sendEmailResult')) return;

        $row = $form->addRow();

        if ($data->get('sendEmailResult')) {
            $row->addContent(__('An email was sent to {email}', ['email' => $data->get('email')]));
        } else {
            $row->addContent(__('Email failed to send to {email}', ['email' => $data->get('email')]));
        }
    }
}
