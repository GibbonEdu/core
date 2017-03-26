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

use Gibbon\session;

global $guid;
require_once $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';

/**
 * Mailer class
 *
 * @version v14
 * @since   v14
 */
class GibbonMailer extends \PHPMailer
{
    protected $session;

    public function __construct(session $session)
    {
        $this->session = $session;
        $this->CharSet = 'UTF-8';
        $this->Encoding = 'base64';
        $this->IsHTML(true);

        if ($this->session->get('enableMailerSMTP') == 'Y') {
            $this->setupSMTP($this->session);
        }

        parent::__construct(null);
    }

    public function setupSMTP(session $session)
    {
        $host = $session->get('mailerSMTPHost');
        $port = $session->get('mailerSMTPPort');

        if ( !empty($host) && !empty($port) ) {
            $username = $session->get('mailerSMTPUsername');
            $password = $session->get('mailerSMTPPassword');
            $auth = ( !empty($username) && !empty($password) );

            $this->IsSMTP();
            $this->Host       = $host;      // SMTP server example
            $this->SMTPDebug  = 0;          // enables SMTP debug information (for testing)
            $this->SMTPAuth   = $auth;      // enable SMTP authentication
            $this->Port       = $port;      // set the SMTP port for the GMAIL server
            $this->Username   = $username;  // SMTP account username example
            $this->Password   = $password;  // SMTP account password example
        }
    }
}
