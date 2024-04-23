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

namespace Gibbon\Auth\Adapter;

use Google_Client;
use Google_Service_Oauth2;
use Gibbon\Http\Url;
use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\AuthenticationAdapter;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\User\UserGateway;

/**
 * Google OAuth2 adapter for Aura/Auth 
 *
 * @version  v23
 * @since    v23
 */
class OAuthGoogleAdapter extends AuthenticationAdapter implements OAuthAdapterInterface
{
    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * Constructor
     *
     * 
     */
    public function __construct(Google_Client $client)
    {
        $this->client = $client;
    }
    
    /**
     * Verifies a set of credentials against the database. Exceptions are thrown
     * if any credentials are not valid.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     * 
     * @throws \Gibbon\Auth\Exception\OAuthLoginError
     * @throws \Gibbon\Auth\Exception\OAuthUserNotFound
     *
     */
    public function login(array $input)
    {
        $session = $this->container->get(Session::class);
        $session->forget('oAuthMethod');

        if (isset($_GET['error'])) {
            throw new Exception\OAuthLoginError($_GET['error']);
        }

        if (!$this->hasOAuthCode()) {
            throw new Exception\OAuthLoginError('Missing code');
        }

        // Exchange the code for the access token
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (empty($accessToken)) {
            throw new Exception\OAuthLoginError('Missing access token');
        }

        try {
            $this->client->setAccessToken($accessToken);
        } catch (\InvalidArgumentException | \UnexpectedValueException $e) {
            throw new Exception\OAuthLoginError($e->getCode().': '. $e->getMessage());
        }

        // Use the token to retrieve user info from the client
        $service = new Google_Service_Oauth2($this->client);
        $user = $service->userinfo->get();

        if (empty($user->email)) {
            $session->forget('googleAPIAccessToken');
            throw new Exception\OAuthLoginError;
        }

        // Get basic user data needed to verify login access
        $this->userGateway = $this->getContainer()->get(UserGateway::class);
        $userData = $this->getUserData(['username' => $user->email]);
        $_POST['usernameOAuth'] = $user->email;

        if (empty($userData)) {
            $session->forget('googleAPIAccessToken');
            throw new Exception\OAuthUserNotFound;
        }

        // If available, load school year and language from state passed back from OAuth redirect
        if ($session->has('oAuthOptions')) {
            list($gibbonSchoolYearID, $gibboni18nID) = array_pad(explode(':', $session->get('oAuthOptions')), 2, '');
            $_POST['gibbonSchoolYearID'] = $gibbonSchoolYearID;
            $_POST['gibboni18nID'] = $gibboni18nID;
            $session->forget('oAuthOptions');
        }

        $session->set('googleAPIAccessToken', $accessToken);
        $session->set('googleAPICalendarEnabled', mb_stripos($accessToken['scope'] ?? '', 'https://www.googleapis.com/auth/calendar.readonly') !== false);

        // Update the refresh token for this user, if we received one
        if (!empty($accessToken['refresh_token'])) {
            $session->set('googleAPIRefreshToken', $accessToken['refresh_token']);
            $this->userGateway->update($userData['gibbonPersonID'], [
                'googleAPIRefreshToken' => $accessToken['refresh_token'],
            ]);
        } else {
            $userToken = $this->userGateway->getByID($userData['gibbonPersonID'], ['googleAPIRefreshToken']);
            
            // No refresh token and none saved in gibbonPerson: force a re-authorization of this account
            if (empty($userToken['googleAPIRefreshToken'])) {
                $session->set('oAuthMethod', 'google');
                $this->client->setApprovalPrompt('force');
                $authUrl = $this->client->createAuthUrl();
                header('Location: ' . $authUrl);
                exit;
            }
        }

        return parent::verifyLogin($userData);
    }

    public function hasOAuthCode() : bool
    {
        return isset($_GET['code']);
    }

    public function getAuthorizationUrl() : string
    {
        return $this->client->createAuthUrl();
    }

    public function getRedirectUrl() : string
    {
        return Url::fromRoute('login')->withQueryParam('method', 'google');
    }
}
