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

namespace Gibbon\Session;

use SessionHandler;
use SessionHandlerInterface;
use Gibbon\Domain\System\SessionGateway;

/**
 * NativeSessionHandler Class
 *
 * @version v23
 * @since   v23
 */
class NativeSessionHandler extends SessionHandler implements SessionHandlerInterface
{
    use SessionEncryption;

    /**
     * @var Gibbon\Domain\System\SessionGateway
     */
    protected $sessionGateway;

    /**
     * @var string
     */
    private $key;

    /**
     * @var bool
     */
    private $encrypted;

    /**
     * Create a session handler that extends the built-in PHP session handler and
     * adds optional encryption if an encryption key is provided.
     *
     * @param string|null $key
     */
    public function __construct(SessionGateway $sessionGateway, string $key = null)
    {
        $this->sessionGateway = $sessionGateway;
        $this->key = $key;
        $this->encrypted = !empty($key) && function_exists('openssl_encrypt');
    }

    /**
     * Overrides the SessionHandler read method, with the additional option to handle
     * session data encryption.
     *
     * @param string $id
     * @return string the session data or an empty string
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
        $data = parent::read($id);

        if ($this->encrypted) {
            return $this->decrypt($data, $this->key);
        }

        return $data;
    }

    /**
     * Overrides the SessionHandler write method, with the additional option to handle
     * session data encryption.
     *
     * @param string $id
     * @param string $data
     * @return bool true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function write($id, $data)
    {
        if ($this->encrypted) {
            $data = $this->encrypt($data, $this->key);
        }

        return parent::write($id, $data);
    }

    /**
     * Implements the SessionHandlerInterface
     *
     * @param int $max_lifetime
     * @return bool true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime)
    {
        $this->sessionGateway->deleteExpiredSessions($max_lifetime);

        return parent::gc($max_lifetime);
    }
}
