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

use Matthewbdaly\SMS\Contracts\Driver;
use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;

/**
 * SMS driver for OneWaySMS: http://onewaysms.hk/
 *
 * @version v17
 * @since   v17
 */
class OneWaySMSDriver implements Driver
{
    /**
     * Message Endpoint.
     *
     * @var
     */
    protected $messageEndpoint;

    /**
     * Credit Endpoint.
     *
     * @var
     */
    protected $creditEndpoint;

    /**
     * URL Query Params.
     *
     * @var
     */
    private $urlParams = [];

    /**
     * Constructor.
     *
     * @param array  $config The configuration.
     * @throws DriverNotConfiguredException Driver not configured correctly.
     *
     * @return void
     */
    public function __construct(array $config)
    {
        if (empty($config['smsURL']) || empty($config['smsUsername']) || empty($config['smsPassword'])) {
            throw new DriverNotConfiguredException();
        }
        $this->messageEndpoint = trim($config['smsURL'], ' ?');
        $this->creditEndpoint = trim($config['smsURLCredit'] ?? '', ' ?');
        $this->urlParams = [
            'apiusername'  => $config['smsUsername'],
            'apipassword'  => $config['smsPassword'],
            'languagetype' => 1,
        ];
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver() : string
    {
        return 'OneWaySMS';
    }

    /**
     * Get endpoint domain.
     *
     * @return string
     */
    public function getEndpoint() : string
    {
        return $this->messageEndpoint;
    }

    /**
     * Send the SMS.
     *
     * @param array $message An array containing the message.
     *
     * @return boolean
     */
    public function sendRequest(array $message) : bool
    {
        try {
            // Prep the url parameters by stripping out invalid characters
            $urlParams = $this->urlParams + [
                'mobileno' => preg_replace('/[^0-9,]/', '', $message['to']),
                'senderid' => preg_replace('/[^a-zA-Z0-9]/', '', $message['from']),
                'message'  => stripslashes(strip_tags($message['content'])),
            ];

            // Fetch the result using a basic HTTP get request
            $url = $this->messageEndpoint.'?'.http_build_query($urlParams);
            $result = @file_get_contents($url);

            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the current credit balance using the smsURLCredit url endpoint.
     *
     * @return int
     */
    public function getCreditBalance() : float
    {
        $url = $this->creditEndpoint.'?'.http_build_query($this->urlParams);
        $result = @file_get_contents($url);

        return floatval($result);
    }
}
