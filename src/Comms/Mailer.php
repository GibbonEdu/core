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

namespace Gibbon\Comms;

use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Comms\Mailer as MailerInterface;
use Gibbon\View\View;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Mailer class
 *
 * @version v14
 * @since   v14
 */
class Mailer extends PHPMailer implements MailerInterface
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
        $this->AltBody = $this->emailBodyStripTags($data['body'] ?? '', $data['button'] ?? []);
    }

    public function setDefaultSender($subject)
    {
        $this->Subject = $this->session->get('organisationNameShort').' - '.$subject;
        $this->SetFrom($this->session->get('organisationEmail'), $this->session->get('organisationName'));
    }

    protected function setupSMTP()
    {
        $host = $this->session->get('mailerSMTPHost');
        $port = $this->session->get('mailerSMTPPort');

        if (!empty($host) && !empty($port)) {
            $username = $this->session->get('mailerSMTPUsername');
            $password = $this->session->get('mailerSMTPPassword');
            $auth = (!empty($username) && !empty($password));

            $this->IsSMTP();
            $this->Host       = $host;      // SMTP server example
            $this->SMTPDebug  = 0;          // enables SMTP debug information (for testing)
            $this->SMTPAuth   = $auth;      // enable SMTP authentication
            $this->Port       = $port;      // set the SMTP port for the server
            $this->Username   = $username;  // SMTP account username example
            $this->Password   = $password;  // SMTP account password example
            $this->Helo       = parse_url($this->session->get('absoluteURL'), PHP_URL_HOST);

            $encryption = $this->session->get('mailerSMTPSecure');
            if ($encryption == 'auto') {
                // Automatically applies the required type of SMTP security based on the port used.
                if ($port == 465) {
                    $this->SMTPSecure = 'ssl';
                } elseif ($port == 587) {
                    $this->SMTPSecure = 'tls';
                } else {
                    $this->SMTPAutoTLS = true;
                }
            } elseif ($encryption == 'none') {
                // Disables encryption as well as PHPMailer's opportunistic TLS setting.
                $this->SMTPSecure = false;
                $this->SMTPAutoTLS = false;
            } else {
                // Explicitly use the selected type of encryption.
                $this->SMTPSecure = $encryption;
            }
        }
    }

    protected function emailBodyStripTags($body, $button = [])
    {
        $body = preg_replace('#<br\s*/?>#i', "\n", $body);
        $body = str_replace(['</p>', '</div>'], "\n\n", $body);
        $body = preg_replace("#\<a.+href\=[\"|\'](.+)[\"|\'].*\>.*\<\/a\>#U", '$1', $body);
        $body = strip_tags($body, '<a>');

        // Add the button link manually to text-only emails
        if (!empty($button['url']) && !empty(!empty($button['text']))) {
            $body .= "\n\n".(string)$button['text'].': '.(string)$button['url']."\n\n";
        }

        return $body;
    }
}
