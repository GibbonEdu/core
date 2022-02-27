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

namespace Gibbon\Auth\Adapter;

use Gibbon\Http\Url;
use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\AuthenticationAdapter;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\User\UserGateway;

/**
 * Generic OAuth2 adapter for Aura/Auth 
 *
 * @version  v23
 * @since    v23
 */
class OAuthGenericAdapter extends AuthenticationAdapter implements OAuthAdapterInterface
{
    /**
     * Constructor
     *
     * 
     */
    public function __construct()
    {
       
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
     * @throws \Aura\Auth\Exception\UsernameMissing
     *
     */
    public function login(array $input)
    {
        $session = $this->container->get(Session::class);
        $session->forget('oAuthMethod');

        if (isset($_GET['error'])) {
            throw new Exception\OAuthLoginError($_GET['error_description']);
        }

        if (!$this->hasOAuthCode()) {
            throw new Exception\OAuthLoginError('Missing code');
        }

        // Try to get an access token (using the authorization code grant)
        $oauthProvider = $this->getProvider();
        $accessToken = $oauthProvider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        $refreshToken = $accessToken->getRefreshToken();

        if (empty($accessToken)) {
            throw new Exception\OAuthLoginError('Missing access token');
        }

        $session->set('genericAPIAccessToken', $accessToken);

        // Check OAuth2 state with saved state, to mitigate CSRF attack
        if (empty($_GET['state']) || ($_GET['state'] !== $session->get('oAuthStateGeneric'))) {
            throw new Exception\OAuthLoginError('Invalid OAuth2 redirect state');
        }

        $session->forget('oAuthStateGeneric');

        // Use the token to retrieve user info from the client
        $resourceOwner = $oauthProvider->getResourceOwner($accessToken);

        $user = $resourceOwner->toArray();
        $email = $user['email'] ?? $user['emailAddress'] ?? $user['email-address'] ?? $user['email_address'];
        $_POST['usernameOAuth'] = $email;

        if (empty($email)) {
            $session->forget('genericAPIAccessToken');
            throw new Exception\OAuthLoginError;
        }

        // Get basic user data needed to verify login access
        $this->userGateway = $this->getContainer()->get(UserGateway::class);
        $userData = $this->getUserData(['username' => $email]);

        if (empty($userData)) {
            $session->forget('genericAPIAccessToken');
            throw new Exception\OAuthUserNotFound;
        }

        // If available, load school year and language from state passed back from OAuth redirect
        if ($session->has('oAuthOptions')) {
            list($gibbonSchoolYearID, $gibboni18nID) = array_pad(explode(':', $session->get('oAuthOptions')), 2, '');
            $_POST['gibbonSchoolYearID'] = $gibbonSchoolYearID;
            $_POST['gibboni18nID'] = $gibboni18nID;
            $session->forget('oAuthOptions');
        }

        // Update the refresh token for this user, if we received one
        if (!empty($refreshToken)) {
            $session->set('genericAPIRefreshToken', $refreshToken);
            $this->userGateway->update($userData['gibbonPersonID'], [
                'genericAPIRefreshToken' => $refreshToken,
            ]);
        }

        return parent::verifyLogin($userData);
    }

    public function hasOAuthCode() : bool
    {
        return isset($_GET['code']);
    }

    public function getAuthorizationUrl() : string
    {
        $oauthProvider = $this->getProvider();

        $authUrl = $oauthProvider->getAuthorizationUrl();
        $this->container->get(Session::class)->set('oAuthStateGeneric', $oauthProvider->getState());

        return $authUrl;
    }

    public function getRedirectUrl() : string
    {
        return Url::fromRoute('login')->withQueryParam('method', 'generic');
    }

    private function getProvider()
    {
        return $this->getContainer()->get('Generic_Auth');
    }
}
