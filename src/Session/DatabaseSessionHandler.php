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

use SessionHandlerInterface;
use Gibbon\Domain\System\SessionGateway;

/**
 * DatabaseSessionHandler Class
 *
 * @version v23
 * @since   v23
 */
class DatabaseSessionHandler implements SessionHandlerInterface
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
     * Handles session read/write methods using a database connection rather than
     * the built-in PHP session handler.
     *
     * @param Connection $connection
     * @param string|null $key
     */
    public function __construct(SessionGateway $sessionGateway, string $key = null)
    {
        $this->sessionGateway = $sessionGateway;
        $this->key = $key;
        $this->encrypted = !empty($key) && function_exists('openssl_encrypt');
    }

    /**
     * Implements the SessionHandlerInterface
     *
     * @param string $path
     * @param string $name
     * @return bool true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function open($path, $name)
    {
        return true;
    }

    /**
     * Implements the SessionHandlerInterface
     *
     * @return bool true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function close()
    {
        return true;
    }

    /**
     * Implements the SessionHandlerInterface
     *
     * @param string $id
     * @return bool true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function destroy($id)
    {
        return $this->sessionGateway->deleteWhere(['gibbonSessionID' => $id]) !== false;
    }

    /**
     * Implements the SessionHandlerInterface
     *
     * @param string $id
     * @return string the session data or an empty string
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
        $session = $this->sessionGateway->getByID($id);
        $sessionData = (string)($session['sessionData'] ?? '');
        
        if ($this->encrypted) {
            $sessionData = $this->decrypt($sessionData, $this->key);
        }

        return $sessionData;
    }

    /**
     * Implements the SessionHandlerInterface
     *
     * @param string $id
     * @param string $data
     * @return bool true for success or false for failure
     */
    #[\ReturnTypeWillChange]
    public function write($id, $sessionData)
    {
        if ($this->encrypted) {
            $sessionData = $this->encrypt($sessionData, $this->key);
        }

        return $this->sessionGateway->updateSessionData($id, $sessionData) !== false;
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
        return $this->sessionGateway->deleteExpiredSessions($max_lifetime) !== false;
    }
}
