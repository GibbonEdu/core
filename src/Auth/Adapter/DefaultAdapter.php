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

use Aura\Auth\Exception as AuraException;
use Aura\Auth\Verifier\VerifierInterface;
use Gibbon\Auth\Exception;
use Gibbon\Auth\Adapter\AuthenticationAdapter;
use Gibbon\Domain\User\UserGateway;

/**
 * Default database adapter for Aura/Auth
 *
 * @version  v23
 * @since    v23
 */
class DefaultAdapter extends AuthenticationAdapter
{
    /**
     * @var \Aura\Auth\Verifier\VerifierInterface
     */
    protected $verifier;

    /**
     * Constructor
     *
     * @param VerifierInterface $verifier
     */
    public function __construct(VerifierInterface $verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Verifies a set of credentials against the database. Exceptions are thrown
     * if any credentials are not valid.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     */
    public function login(array $input)
    {
        $this->userGateway = $this->getContainer()->get(UserGateway::class);

        // Validate that the username and password are both present
        $this->checkInput($input);

        // Get basic user data needed to verify login access
        $userData = $this->getUserData($input);

        // Verify the password provided
        $this->verifyPassword($input, $userData);

        return parent::verifyLogin($userData);
    }

    /**
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
        if (empty($input['username'])) {
            throw new AuraException\UsernameMissing;
        }

        if (empty($input['password'])) {
            throw new AuraException\PasswordMissing;
        }

        return true;
    }

    /**
     *
     * Verifies the password using the provided VerifierInterface.
     *
     * @param array $input The user input array.
     * @param array $userData The data from the database.
     *
     * @throws Aura\Auth\Exception\PasswordIncorrect
     * @throws Gibbon\Auth\Exception\DatabaseLoginError
     *
     */
    protected function verifyPassword($input, $userData)
    {
        if (empty($userData['passwordStrong']) || empty($userData['passwordStrongSalt'])) {
            throw new Exception\DatabaseLoginError;
        }

        if (empty($this->verifier)) {
            throw new Exception\DatabaseLoginError;
        }

        // Use the provided verifier to hash and compare passwords
        $verified = $this->verifier->verify(
            $userData['passwordStrongSalt'].$input['password'],
            $userData['passwordStrong'],
            []
        );

        if (!$verified) {
            $this->updateFailCount($userData, $userData['failCount'] + 1);
            throw new AuraException\PasswordIncorrect;
        }
    }
}
