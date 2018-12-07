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

use Matthewbdaly\SMS\Client;
use GuzzleHttp\Psr7\Response;
use Matthewbdaly\SMS\Drivers\Nexmo;
use Matthewbdaly\SMS\Drivers\Twilio;
use GuzzleHttp\Client as GuzzleClient;
use Gibbon\Comms\Drivers\UnknownDriver;
use Gibbon\Comms\Drivers\OneWaySMSDriver;
use Gibbon\Contracts\Comms\SMS as SMSInterface;
use Matthewbdaly\SMS\Contracts\Client as ClientContract;
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

    public function __construct(array $config)
    {
        try {
            switch ($config['smsGateway']) {
                case 'OneWaySMS':
                    $this->driver = new OneWaySMSDriver($config);
                    $this->batchSize = 10;
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

                default:
                    throw new DriverNotConfiguredException();
            }
        } catch (DriverNotConfiguredException $e) {
            $this->driver = new UnknownDriver();
        }

        $this->client = new Client($this->driver);

        $this->to = [];
        $this->from($config['smsSender']);
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
     * @return boolean True on successful delivery.
     */
    public function send(array $recipients = []) : bool
    {
        $result = true;

        $recipients += array_merge($this->to, $recipients);

        // Split the messages into batches, if supported by the driver.
        if (!empty($this->batchSize)) {
            $recipients = array_chunk($recipients, $this->batchSize);
        }

        foreach ($recipients as $recipient) {
            $result &= $this->client->send([
                'to'      => $recipient,
                'from'    => $this->from,
                'content' => $this->content,
            ]);
        }

        return $result;
    }
}
