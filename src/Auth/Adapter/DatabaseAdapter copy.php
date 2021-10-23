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

use Gibbon\Domain\User\UserGateway;
use Aura\Auth\Adapter\AbstractAdapter;
use Aura\Auth\Verifier\VerifierInterface;

/**
 * Default database adapter for Aura/Auth 
 *
 * @version  v23
 * @since    v23
 */
class DatabaseAdapter extends AbstractAdapter
{
    /**
     * @var Gibbon\Domain\User\UserGateway
     */
    protected $userGateway;

    /**
     * @var Aura\Auth\Verifier\VerifierInterface
     */
    protected $verifier;

    /**
     * Constructor
     *
     * @param UserGateway $userGateway
     * @param VerifierInterface $verifier
     */
    public function __construct(UserGateway $userGateway, VerifierInterface $verifier)
    {
        $this->userGateway = $userGateway;
        $this->verifier = $verifier;
    }

    /**
     *
     * Verifies a set of credentials against the database.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     */
    public function login(array $input)
    {
        return array(null, null);
    }
}
