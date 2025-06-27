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

use Gibbon\Http\Url;
use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\AuthenticationAdapter;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

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
     * @throws \Gibbon\Auth\Exception\OAuthUserNotFound
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

        try {
            $accessToken = $oauthProvider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
            $refreshToken = $accessToken->getRefreshToken();
        } catch (\InvalidArgumentException | \UnexpectedValueException | IdentityProviderException $e) {
            throw new Exception\OAuthLoginError($e->getCode().': '. $e->getMessage());
        }

        if (empty($accessToken)) {
            throw new Exception\OAuthLoginError('Missing access token');
        }

        // Check OAuth2 state with saved state, to mitigate CSRF attack
        if (empty($_GET['state']) || ($_GET['state'] !== $session->get('oAuthStateGeneric'))) {
            throw new Exception\OAuthLoginError('Invalid OAuth2 redirect state');
        }

        $session->forget('oAuthStateGeneric');

        // Use the token to retrieve user info from the client
        $resourceOwner = $oauthProvider->getResourceOwner($accessToken);

        $user = $resourceOwner->toArray();
        $settingGateway = $this->getContainer()->get(SettingGateway::class);

        $ssoSettings = $settingGateway->getSettingByScope('System Admin', 'ssoOther');
        $ssoSettings = json_decode($ssoSettings, true);
        
        // If usernameAttribute is empty the Gibbon version less than v27
        if (empty($ssoSettings['usernameAttribute'])) {
            $usernameOAuth = $user['email'] ?? $user['emailAddress'] ?? $user['email-address'] ?? $user['email_address'];
        } else {
            $usernameOAuth = $user[$ssoSettings['usernameAttribute']];
        }
        
        $_POST['usernameOAuth'] = $usernameOAuth;

        if (empty($usernameOAuth)) {
            $session->forget('genericAPIAccessToken');
            throw new Exception\OAuthLoginError;
        }

        // Get basic user data needed to verify login access
        $this->userGateway = $this->getContainer()->get(UserGateway::class);
        $userData = $this->getUserData(['username' => $usernameOAuth]);

        if (empty($userData)) {
            $session->forget('genericAPIAccessToken');
            throw new Exception\OAuthUserNotFound;
        }

        $session->set('genericAPIAccessToken', $accessToken);

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
