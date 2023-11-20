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

namespace Gibbon\Comms\Drivers;

use Gibbon\Contracts\Comms\Mailer;
use Matthewbdaly\SMS\Contracts\Driver;
use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;

/**
 * An SMS driver which works via a gateway supporting Mail to SMS.
 *
 * @version v17
 * @since   v17
 */
class MailDriver implements Driver
{
    /**
     * Mailer.
     *
     * @var
     */
    protected $mail;

    /**
     * Endpoint.
     *
     * @var
     */
    protected $endpoint;

    /**
     * Constructor.
     *
     * @param Mailer $mailer The Mailer instance.
     * @param array  $config The configuration.
     * @throws \Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException Driver not configured correctly.
     *
     * @return void
     */
    public function __construct(Mailer $mail, array $config)
    {
        $this->mail = $mail;
        if (! array_key_exists('domain', $config)) {
            throw new DriverNotConfiguredException();
        }
        $this->endpoint = trim($config['domain']. ' @');
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'Mail';
    }

    /**
     * Get endpoint domain.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Send the SMS.
     *
     * @param array $message An array containing the message.
     *
     * @return boolean
     */
    public function sendRequest(array $message): bool
    {
        try {
            $recipient = preg_replace('/[^0-9,]/', '', $message['to']) . "@" . $this->endpoint;
            $content = trim(stripslashes(strip_tags($message['content'])));

            $this->mail->SetFrom($message['from']);
            $this->mail->AddAddress($recipient);
            $this->mail->Subject = $content;
            $this->mail->Body = $content;

            return $this->mail->Send();
        } catch (\Exception $e) {
            return false;
        }
    }
}
