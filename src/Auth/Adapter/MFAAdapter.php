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

use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\AuthenticationAdapter;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Contracts\Services\Session;
use Aura\Auth\Exception as AuraException;
use Gibbon\Auth\Exception\DatabaseLoginError;

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

        // Check that the MFA token is valid
        $tfa = new \RobThree\Auth\TwoFactorAuth('Gibbon'); 
        if ($tfa->verifyCode($userData['mfaSecret'], $this->getToken()) !== true) {
            $this->session->forget(['mfaToken', 'mfaTokenPass', 'mfaNonce']);
            throw new Exception\MFATokenInvalid;
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
    protected function checkInput($input)
    {
        if (!$this->session->has('mfaToken')) {
            throw new Exception\DatabaseLoginError;
        }

        if (empty($this->getToken())) {
            throw new Exception\DatabaseLoginError;
        }
    }

    /**
     * Undocumented function
     *
     * @param array $input
     * @return void
     * 
     * @throws Exception\DatabaseLoginError
     */
    protected function getUserData(array $input)
    {
        if (empty($this->userGateway)) {
            throw new Exception\DatabaseLoginError;
        }

        $mfaRequest = $this->userGateway->selectBy(['mfaToken' => $this->session->get('mfaToken')], ['username']);

        if ($mfaRequest->rowCount() != 1) {
            throw new Exception\DatabaseLoginError;
        }

        // Get the user with an mfaToken that matches the one provided
        $userDataCheck = $mfaRequest->fetch();

        if (empty($userDataCheck) || empty($userDataCheck['username'])) {
            throw new Exception\DatabaseLoginError;
        }

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

        // Validate the selected users password against the hashed one saved in the session
        if (empty($userData['passwordStrong']) || empty($userData['passwordStrongSalt'])) {
            throw new Exception\DatabaseLoginError;
        }

        $mfaTokenPass = $this->session->get('mfaTokenPass');
        $mfaDatabasePass = hash('sha256', $userData['mfaSecret'].$userData['passwordStrong']);

        // Check that this session value matches the password in the database
        if ($mfaTokenPass != $mfaDatabasePass) {
            $this->updateFailCount($userData, $userData['failCount'] + 1);
            throw new AuraException\PasswordIncorrect;
        }

        return $userData;
    }
    
}
