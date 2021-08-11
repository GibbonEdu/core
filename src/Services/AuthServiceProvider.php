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

namespace Gibbon\Services;

use Google_Client;
use Google_Service_Calendar;
use Gibbon\Domain\System\SettingGateway;
use League\OAuth2\Client\Provider\GenericProvider;
use League\Container\ServiceProvider\AbstractServiceProvider;

/**
 * Authentication API Services
 *
 * @version v23
 * @since   v23
 */
class AuthServiceProvider extends AbstractServiceProvider
{
    /**
     * The provides array is a way to let the container know that a service
     * is provided by this service provider. Every service that is registered
     * via this service provider must have an alias added to this array or
     * it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        'Google_Client',
        'Google_Service_Calendar',
        'Microsoft_Auth',
        'Generic_Auth',
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $container = $this->getContainer();

        $container->share(Google_Client::class, function () {
            $session = $this->getContainer()->get('session');
            $settingGateway = $this->getContainer()->get(SettingGateway::class);

            $ssoSettings = $settingGateway->getSettingByScope('System Admin', 'ssoGoogle');
            $ssoSettings = json_decode($ssoSettings, true);

            try {
                // Setup the Client
                $client = new Google_Client();
                $client->setApplicationName($ssoSettings['clientName']);
                $client->setScopes(array('email', 'profile', 'https://www.googleapis.com/auth/calendar'));
                $client->setClientId($ssoSettings['clientID']);
                $client->setClientSecret($ssoSettings['clientSecret']);
                $client->setRedirectUri($session->get('absoluteURL').'/lib/google/index.php');
                $client->setDeveloperKey($ssoSettings['developerKey']);
                $client->setAccessType('offline');
                $client->setState(time());

                if (!$session->has('googleAPIAccessToken')) {
                    return $client;
                }

                $client->setAccessToken($session->get('googleAPIAccessToken'));
                
                if ($client->isAccessTokenExpired()) {
                    // Re-establish the Client and get a new token
                    if ($session->exists('googleAPIRefreshToken')) {
                        $client->refreshToken($session->get('googleAPIRefreshToken'));
                        $session->set('googleAPIAccessToken', $client->getAccessToken());
                    } else {
                        return null;
                    }
                }
            } catch (\InvalidArgumentException $e) {
                return null;
            } catch (\Google_Service_Exception $e) {
                return null;
            }

            return $client;
        });

        $container->share(Google_Service_Calendar::class, function () {
            $client = $this->getContainer()->get(Google_Client::class);

            return $client ? new Google_Service_Calendar($client) : null;
        });


        $container->share('Microsoft_Auth', function () {
            $session = $this->getContainer()->get('session');
            $settingGateway = $this->getContainer()->get(SettingGateway::class);

            $ssoSettings = $settingGateway->getSettingByScope('System Admin', 'ssoMicrosoft');
            $ssoSettings = json_decode($ssoSettings, true);

            return new GenericProvider([
                'clientId'                  => $ssoSettings['clientID'],
                'clientSecret'              => $ssoSettings['clientSecret'],
                'redirectUri'               => $session->get('absoluteURL').'/lib/google/microsoft.php',
                'urlAuthorize'              => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                'urlAccessToken'            => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                'urlResourceOwnerDetails'   => 'https://outlook.office.com/api/v1.0/me',
                'scopes'                    => 'openid profile offline_access email user.read.all calendars.read calendars.read.shared',
            ]);
        });

        $container->share('Generic_Auth', function () {
            $session = $this->getContainer()->get('session');
            $settingGateway = $this->getContainer()->get(SettingGateway::class);

            $ssoSettings = $settingGateway->getSettingByScope('System Admin', 'ssoOther');
            $ssoSettings = json_decode($ssoSettings, true);

            return new GenericProvider([
                'clientId'                  => $ssoSettings['clientID'],
                'clientSecret'              => $ssoSettings['clientSecret'],
                'redirectUri'               => $session->get('absoluteURL').'/lib/google/other.php',
                'urlAuthorize'              => $ssoSettings['authorizeEndpoint'],
                'urlAccessToken'            => $ssoSettings['tokenEndpoint'],
                'urlResourceOwnerDetails'   => $ssoSettings['userEndpoint'],
            ]);
        });

    }
}
