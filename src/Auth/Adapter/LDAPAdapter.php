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
use Aura\Auth\Exception as AuraException;
use Gibbon\Domain\User\UserGateway;
use Aura\Auth\AuthFactory;

/**
 * Generic OAuth2 adapter for Aura/Auth 
 *
 * @version  v23
 * @since    v23
 */
class LDAPAdapter extends AuthenticationAdapter
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
     * Attempts to connect to the LDAP server using the provided credentials. Exceptions are thrown
     * if any credentials are not valid.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     * 
     * 
     * 
     *
     */
    public function login(array $input)
    {
        $this->userGateway = $this->getContainer()->get(UserGateway::class);

        // Validate that the username and password are both present
        
        $authFactory = $this->getAuthFactory();
        $auth = $authFactory->newInstance();
        $ldapAdapter = $authFactory->newLdapAdapter(
            'ip address', //TODO: GET THESE FROM SETTINGS
            '%s@'.'bind domain', //TODO: GET THESE FROM SETTINGS
            [LDAP_OPT_PROTOCOL_VERSION => 3]
        );
        $loginService = $authFactory->newLoginService($ldapAdapter);
        try {
            $loginService->login($auth, array(
                'username' => $input['username'],
                'password' => $input['password']
            ));
        } catch (AuraException\BindFailed $e) {
            throw new Exception\LDAPBindFailed;
        }
        
        // Get basic user data needed to verify login access
        $userData = $this->getUserData($input);
        return parent::verifyLogin($userData);
    }



    private function getAuthFactory()
    {
        return $this->getContainer()->get(AuthFactory::class);
    }
}
