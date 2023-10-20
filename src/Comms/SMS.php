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

use Gibbon\Comms\Drivers\MailDriver;
use Gibbon\Comms\Drivers\OneWaySMSDriver;
use Gibbon\Comms\Drivers\UnknownDriver;
use Gibbon\Contracts\Comms\SMS as SMSInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Client;
use Matthewbdaly\SMS\Drivers\Twilio;
use Matthewbdaly\SMS\Drivers\Nexmo;
use Matthewbdaly\SMS\Drivers\Clockwork;
use Matthewbdaly\SMS\Drivers\TextLocal;
use Matthewbdaly\SMS\Exceptions\ClientException;
use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;


/**
 * Factory class to create a fully configured SMS client based on the chosen gateway.
 * 
 * @version v17
 * @since   v17
 */
class SMS implements SMSInterface
{
    protected $client;

    protected $driver;

    protected $to;

    protected $from;

    protected $content;

    protected $batchSize;

    protected $errors = [];

    public function __construct(array $config)
    {
        try {
            switch ($config['smsGateway']) {
                case 'OneWaySMS':
                    $this->batchSize = 10;
                    $this->driver = new OneWaySMSDriver($config);
                    break;

                case 'Twilio':
                    $this->driver = new Twilio(new GuzzleClient(), new Response(), [
                        'account_id' => $config['smsUsername'],
                        'api_token' => $config['smsPassword'],
                    ]);
                    break;

                case 'Nexmo':
                    $this->driver = new Nexmo(new GuzzleClient(), new Response(), [
                        'api_key' => $config['smsUsername'],
                        'api_secret' => $config['smsPassword'],
                    ]);
                    break;

                case 'Clockwork':
                    $this->driver = new Clockwork(new GuzzleClient(), new Response(), [
                        'api_key' => $config['smsUsername'],
                    ]);
                    break;

                case 'TextLocal':
                    $this->batchSize = 10;
                    $this->driver = new TextLocal(new GuzzleClient(), new Response(), [
                        'api_key' => $config['smsUsername'],
                    ]);
                    break;

                case 'Mail to SMS':
                    $this->driver = new MailDriver($config['smsMailer'], [
                        'domain' => $config['smsUsername'],
                    ]);
                    break;

                default:
                    throw new DriverNotConfiguredException();
            }
        } catch (DriverNotConfiguredException $e) {
            $this->driver = new UnknownDriver();
        }

        $this->client = new Client($this->driver);

        $this->to = [];
        $this->from($config['smsSenderID']);
    }

    /**
     * Get the SMS driver name.
     *
     * @return string
     */
    public function getDriver() : string
    {
        return $this->client->getDriver();
    }

    /**
     * Get the SMS credit balance, if supported by the driver.
     *
     * @return float
     */
    public function getCreditBalance() : float
    {
        return method_exists($this->driver, 'getCreditBalance')
            ? $this->driver->getCreditBalance()
            : 0;
    }

    /**
     * Return the list of any failed recipients or exceptions generated during sending.
     *
     * @return array
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Set the message recipient(s).
     *
     * @param string|array $to
     */
    public function to($to)
    {
        $this->to = array_merge($this->to, is_array($to) ? $to : [$to]);

        return $this;
    }

    /**
     * Set the message sender name.
     *
     * @param string $from
     */
    public function from(string $from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Set the message content.
     *
     * @param string $from
     */
    public function content(string $content)
    {
        $this->content = stripslashes(strip_tags($content));

        return $this;
    }

    /**
     * Send the message to one or more recipients.
     *
     * @param array $to The recipient array.
     *
     * @return array Array of successful recipients.
     */
    public function send(array $recipients = []) : array
    {
        $sent = [];
        $recipients += array_merge($this->to, $recipients);

        try {

            // Split the messages into comma-separated batches, if supported by the driver.
            if (!empty($this->batchSize)) {
                $recipients = array_map(function ($phoneNumbers) {
                    return implode(',', $phoneNumbers);
                }, array_chunk($recipients, $this->batchSize));
            }

            foreach ($recipients as $recipient) {
                $message = [
                    'to'      => $recipient,
                    'from'    => $this->from,
                    'content' => $this->content,
                ];

                if ($this->client->send($message)) {
                    $sent[] = $recipient;
                } else {
                    $this->errors[] = 'SMS failed to send to '.$recipient;
                }
            }
        } catch (ClientException $e) {
            $this->errors[] = $e->__toString();
        }

        return $sent;
    }
}
