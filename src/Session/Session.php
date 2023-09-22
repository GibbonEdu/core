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
use Gibbon\Contracts\Services\Session as SessionInterface;

/**
 * Session Class
 *
 * @version	v23
 * @since	v12
 */
class Session implements SessionInterface
{
    /**
     * @var string
     */
    private	$guid;

    /**
     * Construct
     *
     * @param string $guid
     *   The guid of the session.
     * @param string $address
     *   Optional string of the current address.
     * @param string $module
     *   Optional string name of the current module.
     * @param string $action
     *   Optional string of the current action.
     */
    public function __construct(
        string $guid,
        string $address = '',
        string $module = '',
        string $action = ''
    )
    {
        // Backwards compatibility for external modules.
        $this->guid = $guid;

        // Set session variables.
        $this->set('guid', $guid);
        $this->set('address', $address);
        $this->set('module', $module);
        $this->set('action', $action);
    }

    public function setGuid(string $_guid)
    {
        $this->guid = $_guid;
    }

    /**
     * Checks if one or more keys exist.
     *
     * @param  string|array  $keys
     * @return bool
     */
    public function exists($keys)
    {
        $keys = is_array($keys)? $keys : [$keys];
        $exists = !empty($keys);

        foreach ($keys as $key) {
            $exists &= isset($_SESSION[$this->guid][$key]);
        }

        return $exists;
    }

    /**
     * Checks if one or more keys are present and not null.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($keys)
    {
        $keys = is_array($keys)? $keys : [$keys];
        $has = !empty($keys);

        foreach ($keys as $key) {
            $has &= !empty($_SESSION[$this->guid][$key]);
        }

        return $has;
    }

    /**
     * Get an item from the session.
     *
     * @param	string	$key
     * @param	mixed	$default Define a value to return if the variable is empty
     *
     * @return	mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            // Fetch a value from multi-dimensional array with an array of keys
            $retrieve = function($array, $keys, $default) {
                foreach($keys as $key) {
                    if (!isset($array[$key])) return $default;
                    $array = $array[$key];
                }
                return $array;
            };

            return $retrieve($_SESSION[$this->guid], $key, $default);
        }

        return isset($_SESSION[$this->guid][$key])? $_SESSION[$this->guid][$key] : $default;
    }

    /**
     * Set a key / value pair or array of key / value pairs in the session.
     *
     * @param	string	$key
     * @param	mixed	$value
     */
    public function set($key, $value = null)
    {
        $keyValuePairs = is_array($key)? $key : [$key => $value];

        foreach ($keyValuePairs as $key => $value) {
            $_SESSION[$this->guid][$key] = $value ;
        }
    }

    /**
     * Remove an item from the session, returning its value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function remove($key)
    {
        $value = $this->get($key);
        unset($_SESSION[$this->guid][$key]);

        return $value;
    }

    /**
     * Remove one or many items from the session.
     *
     * @param  string|array  $keys
     */
    public function forget($keys)
    {
        $keys = is_array($keys)? $keys : [$keys];

        foreach ($keys as $key) {
            $this->remove($key);
        }
    }
}
