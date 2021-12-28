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
use Gibbon\Forms\Builder\FormData;

class SendEmail extends AbstractFormProcess
{
    protected $requiredFields = ['email', 'firstName'];

    protected $session;
    protected $mail;

    public function __construct(Session $session, Mailer $mail)
    {
        $this->session = $session;
        $this->mail = $mail;
    }

    public function process(FormData $formData)
    {
        $this->mail->Subject = 'Preview Test Mail';
        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => 'Testing',
            'body'   => 'Hello this is a test.',
        ]);

        $this->mail->SetFrom($this->session->get('organisationEmail'), $this->session->get('organisationName'));
        $this->mail->AddAddress($formData->get('email'));

        $this->mail->Send();
    }

    public function rollback(FormData $data)
    {
        
    }
}
