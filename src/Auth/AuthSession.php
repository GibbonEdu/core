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

namespace Gibbon\Auth;

use Gibbon\Contracts\Services\Session;
use Aura\Auth\Session\SegmentInterface;
use Aura\Auth\Session\SessionInterface;

/**
 * Session wrapper for Aura/Auth SessionInterface
 *
 * @version  v23
 * @since    v23
 */
class AuthSession implements SessionInterface, SegmentInterface
{
    /**
     * @var Gibbon\Contracts\Services\Session
     */
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     *
     * Starts a session.
     *
     */
    public function start()
    {
        return true;
    }

    /**
     *
     * Resumes a previously-existing session.
     *
     */
    public function resume()
    {
        if (session_id() !== '') {
            return true;
        }

        if (isset($_COOKIE[session_name()])) {
            return $this->start();
        }

        return false;
    }

    /**
     *
     * Gets a value from the segment.
     *
     * @param mixed $key A key for the segment value.
     *
     * @param mixed $alt Return this value if the segment key does not exist.
     *
     * @return mixed
     *
     */
    public function get($key, $alt = null)
    {
        return $this->session->get($key, $alt);
    }

    /**
     *
     * Sets a value in the segment.
     *
     * @param mixed $key The key in the segment.
     *
     * @param mixed $val The value to set.
     *
     */
    public function set($key, $value)
    {
        $this->session->set($key, $value);
    }

    /**
     *
     * Regenerates the session ID.
     *
     */
    public function regenerateId()
    {
        return session_status() === PHP_SESSION_ACTIVE 
            ? session_regenerate_id()
            : false;
    }
}
