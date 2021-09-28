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

namespace Gibbon\Session;

use SessionHandler;
use SessionHandlerInterface;

/**
 * EncryptedSessionHandler Class
 *
 * @version v23
 * @since   v23
 */
class EncryptedSessionHandler extends SessionHandler implements SessionHandlerInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var bool
     */
    private $encrypted;

    public function __construct(string $key = null)
    {
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
    public function write($id, $data)
    {
        if ($this->encrypted) {
            $data = $this->encrypt($data, $this->key);
        }

        return parent::write($id, $data);
    }


    /**
    * Decrypt AES 256
    *
    * @param string $edata
    * @param string $password
    * @return string decrypted data
    */
    private function decrypt($edata, $password) {
        $data = base64_decode($edata);
        $salt = substr($data, 0, 16);
        $ct = substr($data, 16);

        $rounds = 3; // depends on key length
        $data00 = $password.$salt;
        $hash = [];
        $hash[0] = hash('sha256', $data00, true);
        $result = $hash[0];
        for ($i = 1; $i < $rounds; $i++) {
            $hash[$i] = hash('sha256', $hash[$i - 1].$data00, true);
            $result .= $hash[$i];
        }
        $key = substr($result, 0, 32);
        $iv  = substr($result, 32,16);

        $decrypted = openssl_decrypt($ct, 'AES-256-CBC', $key, true, $iv);
        return is_string($decrypted) ? $decrypted : $edata;
    }

    /**
     * Encrypt AES 256
     *
     * @param string $data
     * @param string $password
     * @return string base64 encrypted data
     */
    private function encrypt($data, $password) {
        // Set a random salt
        $salt = openssl_random_pseudo_bytes(16);

        $salted = '';
        $dx = '';
        // Salt the key(32) and iv(16) = 48
        while (strlen($salted) < 48) {
            $dx = hash('sha256', $dx.$password.$salt, true);
            $salted .= $dx;
        }

        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);

        $encrypted_data = openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv);
        return is_string($encrypted_data) ? base64_encode($salt . $encrypted_data) : $data;
    }

}
