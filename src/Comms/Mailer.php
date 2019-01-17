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

namespace Gibbon\Comms;

use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Comms\Mailer as MailerInterface;
use Gibbon\View\View;

/**
 * Mailer class
 *
 * @version v14
 * @since   v14
 */
class Mailer extends \PHPMailer implements MailerInterface
{
    protected $session;
    protected $view;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->CharSet = 'UTF-8';
        $this->Encoding = 'base64';
        $this->IsHTML(true);

        if ($this->session->get('enableMailerSMTP') == 'Y') {
            $this->setupSMTP();
        }

        parent::__construct(null);
    }

    public function setView(View $view)
    {
        $this->view = $view;
        $this->view->addData([
            'systemName'            => $this->session->get('systemName'),
            'organisationName'      => $this->session->get('organisationName'),
            'organisationNameShort' => $this->session->get('organisationNameShort'),
            'organisationEmail'     => $this->session->get('organisationEmail'),
            'organisationLogo'      => $this->session->get('organisationLogo'),
        ]);
        
        return $this;
    }

    public function renderBody(string $template, array $data = [])
    {
        $this->Body = $this->view->render($template, $data);
        $this->AltBody = emailBodyConvert($this->Body);
    }

    protected function setupSMTP()
    {
        $host = $this->session->get('mailerSMTPHost');
        $port = $this->session->get('mailerSMTPPort');

        if ( !empty($host) && !empty($port) ) {
            $username = $this->session->get('mailerSMTPUsername');
            $password = $this->session->get('mailerSMTPPassword');
            $auth = ( !empty($username) && !empty($password) );

            $this->IsSMTP();
            $this->Host       = $host;      // SMTP server example
            $this->SMTPDebug  = 0;          // enables SMTP debug information (for testing)
            $this->SMTPAuth   = $auth;      // enable SMTP authentication
            $this->Port       = $port;      // set the SMTP port for the Gmail server
            $this->Username   = $username;  // SMTP account username example
            $this->Password   = $password;  // SMTP account password example
            $this->Helo       = parse_url($this->session->get('absoluteURL'), PHP_URL_HOST);

            // Automatically applies the required type of SMTP security for Gmail 
            // based on the port used. https://support.google.com/a/answer/176600?hl=en
            if ($this->Host === 'smtp.gmail.com' || $this->Host === 'smtp-relay.gmail.com') {
                if ($port == 465) $this->SMTPSecure = 'ssl';
                if ($port == 587) $this->SMTPSecure = 'tls';
            }
        }
    }
}
