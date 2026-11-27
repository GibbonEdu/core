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
use Gibbon\Auth\Exception;
use Gibbon\Auth\Passkey\PasskeyService;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SessionGateway;
use Gibbon\Domain\User\UserGateway;

/**
 * Passwordless login using a WebAuthn passkey.
 */
class PasskeyAdapter extends AuthenticationAdapter
{
    public function login(array $input)
    {
        $this->session = $this->getContainer()->get(Session::class);
        $this->sessionGateway = $this->getContainer()->get(SessionGateway::class);
        $this->userGateway = $this->getContainer()->get(UserGateway::class);

        $assertion = json_decode($_POST['passkeyAssertion'] ?? '', true);
        if (!is_array($assertion)) {
            throw new Exception\PasskeyInvalid;
        }

        try {
            $gibbonPersonID = $this->getContainer()
                ->get(PasskeyService::class)
                ->verifyAssertion($assertion, $_POST['passkeyNonce'] ?? '');
        } catch (\Throwable $e) {
            throw new Exception\PasskeyInvalid;
        }

        $result = $this->userGateway->selectLoginDetailsByID($gibbonPersonID);
        if ($result->rowCount() != 1) {
            throw new Exception\DatabaseLoginError;
        }

        $userData = $result->fetch();

        // A passkey is a possession + user-verification factor, so it satisfies MFA on its own.
        $this->verifyUserAccess($userData);
        $this->verifySchoolYearAccess($userData);
        $this->updateUserData($userData);

        return $this->getSafeUserData($userData);
    }
}
