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

use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\AuthenticationAdapter;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Contracts\Services\Session;
use Aura\Auth\Exception as AuraException;
use Gibbon\Auth\Exception\MFATokenInvalid;

/**
 * Multifactor Authentication Adapter
 *
 * @version  v24
 * @since    v24
 */
class MFAAdapter extends AuthenticationAdapter
{
    /**
     * Constructor
     *
     *
     */
    public function __construct()
    {

    }


    public function login(array $input)
    {
        $this->session = $this->getContainer()->get(Session::class);
        $this->userGateway = $this->getContainer()->get(UserGateway::class);

        // Validate that the username and password are both present
        $this->checkInput($input);

        // Get basic user data needed to verify login access
        $userData = $this->getUserData($input);
        $mfaCheck = @$this->userGateway->getByID($userData['gibbonPersonID'], ['mfaSecret']);

        // Check that the MFA token is valid
        $tfa = new \RobThree\Auth\TwoFactorAuth('Gibbon');
        if ($tfa->verifyCode($mfaCheck['mfaSecret'], $this->getToken()) !== true) {
            $this->session->forget(['mfaToken', 'mfaTokenPass', 'mfaNonce']);
            throw new MFATokenInvalid;
        }

        $userData['mfaLoginSuccess'] = $this->session->get('mfaToken');
        $userData['mfaLoginCode'] = $this->getToken();

        return parent::verifyLogin($userData);
    }

    public function getToken() : string
    {
        return $_POST['mfaCode'] ?? '';
    }

    /**
     *
     * Check the credential input for completeness.
     *
     * @param array $input
     *
     * @return bool
     *
     * @throws Aura\Auth\Exception\UsernameMissing
     * @throws Aura\Auth\Exception\PasswordMissing
     *
     */
    protected function checkInput($input): bool
    {
        if (!$this->session->has('mfaToken')) {
            throw new Exception\DatabaseLoginError;
        }

        if (empty($this->getToken())) {
            throw new Exception\DatabaseLoginError;
        }

        return true;
    }

    /**
     * Obtain user data from UserGateway.
     *
     * @param array $input
     * @return array  An array of user data.
     *
     * @throws Exception\DatabaseLoginError
     */
    protected function getUserData(array $input): array
    {
        if (empty($this->userGateway)) {
            throw new Exception\DatabaseLoginError;
        }

        $mfaRequest = $this->userGateway->selectBy(['mfaToken' => $this->session->get('mfaToken')], ['username', 'gibbonPersonID', 'mfaSecret']);

        if ($mfaRequest->rowCount() != 1) {
            throw new Exception\DatabaseLoginError;
        }

        // Get the user with an mfaToken that matches the one provided
        $userDataCheck = $mfaRequest->fetch();

        if (empty($userDataCheck) || empty($userDataCheck['username']) || empty($userDataCheck['mfaSecret'])) {
            throw new Exception\DatabaseLoginError;
        }

        //Unset the MFA Token Pass to prevent POST replay attacks
        $this->userGateway->update($userDataCheck['gibbonPersonID'], ['mfaToken' => NULL]);

        // Get the user login details and verify they exist and match only one user
        $userResult = $this->userGateway->selectLoginDetailsByUsername($userDataCheck['username'] ?? '');

        if ($userResult->rowCount() < 1) {
            throw new AuraException\UsernameNotFound;
        }

        if ($userResult->rowCount() > 1) {
            throw new AuraException\MultipleMatches;
        }

        $userData = $userResult->fetch();
        $_POST['gibbonPersonIDLoginAttempt'] = $userData['gibbonPersonID'] ?? null;

        $method = $this->session->get('mfaMethod');
        switch ($method) {
            case 'Gibbon\Auth\Adapter\OAuthGoogleAdapter':
                $password = $this->session->get('googleAPIAccessToken', [])['access_token'] ?? ''; break;
            case 'Gibbon\Auth\Adapter\OAuthMicrosoftAdapter':
                $password = $this->session->get('microsoftAPIAccessToken', [])['access_token'] ?? ''; break;
            case 'Gibbon\Auth\Adapter\OAuthGenericAdapter':
                $password = $this->session->get('genericAPIAccessToken', [])['access_token'] ?? ''; break;
            default:
                $password = $userData['passwordStrong'];
        }

        // Validate the selected users password/token against the hashed one saved in the session
        if (empty($password) || empty($userData['passwordStrong'])) {
            throw new Exception\DatabaseLoginError;
        }

        $mfaTokenPass = $this->session->get('mfaTokenPass');
        $mfaDatabasePass = hash('sha256', $userDataCheck['mfaSecret'].$password);

        // Check that this session value matches the password in the database
        if ($mfaTokenPass != $mfaDatabasePass) {
            $this->updateFailCount($userData, $userData['failCount'] + 1);
            throw new MFATokenInvalid;
        }

        return $userData;
    }

}
