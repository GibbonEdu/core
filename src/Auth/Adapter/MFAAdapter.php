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
        $this->userGateway = $this->getContainer()->get(UserGateway::class);
        
        // Validate that the username and password are both present
        $this->checkInput($input);

        // Get basic user data needed to verify login access
        $userData = $this->getUserData($input);

        // Verify the password provided
        $this->verifyPassword($input, $userData);

        return parent::verifyLogin($userData);
    }
    
    
    
    public function hasToken() : bool
    {
        return isset($_GET['token']);
    }
    
    

    
    
}
