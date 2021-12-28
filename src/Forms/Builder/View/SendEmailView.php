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
use Gibbon\Forms\Builder\FormData;

class SendEmailView 
{
    public function getName()
    {
        return __('Send Email');
    }

    public function getDescription()
    {
        return __('Enables this form to send an email once the form has been submitted.');
    }

    public function configure(Form &$form)
    {
        $row = $form->addRow();
            $row->addLabel('sendEmail', $this->getName())->description($this->getDescription());
            $row->addYesNo('sendEmail');
    }

    public function display(Form &$form, FormData &$data)
    {
        $row = $form->addRow();
        $row->addContent(__('An email was sent to {email}', ['email' => $data->get('email')]));
    }
}
