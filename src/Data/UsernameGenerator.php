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

namespace Gibbon\Data;

use Gibbon\sqlConnection;

/**
 * Helper class to generate a username based on a supplied format. Guarantees the uniqueness of the returned username.
 *
 * @version v15
 * @since   v15
 */
class UsernameGenerator
{
    const MAX_LENGTH = 20;
    const ILLEGAL_CHARS = " '-";

    protected $pdo;

    protected $tokens = array();

    protected $defaultFormat = '[preferredName:1][surname]';
    protected $loopCount = 0;

    /**
     * Class constructor with database dependancy injection.
     * @version  v15
     * @since    v15
     * @param    sqlConnection  $pdo
     */
    public function __construct(sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Adds a string token that's replaced with the corresponding value when processing the username format.
     * @version  v15
     * @since    v15
     * @param    string  $name
     * @param    string  $value
     */
    public function addToken($name, $value)
    {
        if (empty($name)) {
            throw new InvalidArgumentException();
        }

        $this->tokens[$name] = array(
            'type'  => 'string',
            'value' => mb_strtolower($value),
        );
    }

    /**
     * Adds a numeric token with a starting value and size that's incremented each time it's generated.
     * @version  v15
     * @since    v15
     * @param    string  $name
     * @param    string|int  $value
     * @param    int  $size
     * @param    int  $increment
     * @param    function  $callback (optional) A function to be called after a numberic token is incremented.
     */
    public function addNumericToken($name, $value, $size, $increment, $callback = null)
    {
        if (empty($name)) {
            throw new InvalidArgumentException();
        }

        $this->tokens[$name] = array(
            'type'      => 'numeric',
            'value'     => intval($value),
            'size'      => intval($size),
            'increment' => intval($increment),
            'callback'  => $callback,
        );
    }

    /**
     * Get token data by name.
     * @version  v15
     * @since    v15
     * @param    string  $name
     * @return   array Token data
     */
    public function getToken($name)
    {
        return (isset($this->tokens[$name]))? $this->tokens[$name] : false;
    }

    /**
     * Generates a username based on the provided gibbonRoleID, using the 'isDefault' format if none exists for that role.
     * @version  v15
     * @since    v15
     * @param    string|int  $gibbonRoleID
     * @return   string  Unique username
     */
    public function generateByRole($gibbonRoleID)
    {
        $usernameFormat = '';

        // Get the username format data by gibbonRoleID
        $data = array('gibbonRoleID' => $gibbonRoleID);
        $sql = "SELECT * FROM gibbonUsernameFormat WHERE (FIND_IN_SET(:gibbonRoleID, gibbonRoleIDList)) OR isDefault='Y' ORDER BY FIND_IN_SET(:gibbonRoleID, gibbonRoleIDList) DESC LIMIT 1";
        $result = $this->pdo->executeQuery($data, $sql);

        if ($result->rowCount() > 0) {
            $row = $result->fetch(0);

            $usernameFormat = $row['format'];

            // Add a numeric token with a callback to update the database value when generated.
            if ($row['isNumeric'] == 'Y') {
                $pdo = $this->pdo;
                $callback = function($number) use (&$pdo, &$row) {
                    $data = array('gibbonUsernameFormatID' => $row['gibbonUsernameFormatID'], 'numericValue' => $number);
                    $sql = "UPDATE gibbonUsernameFormat SET numericValue=:numericValue WHERE gibbonUsernameFormatID=:gibbonUsernameFormatID";
                    $result = $pdo->executeQuery($data, $sql);
                };

                $this->addNumericToken('number', $row['numericValue'], $row['numericSize'], $row['numericIncrement'], $callback);
            }
        }

        return $this->generate($usernameFormat);
    }

    /**
     * Generates a username based on the provided string format, replacing tokens with their corresponding values.
     * @version  v15
     * @since    v15
     * @param    string  $format
     * @return   string  Unique username
     */
    public function generate($format)
    {
        $username = $format;

        if (empty($username)) {
            $username = $this->defaultFormat;
        }

        // Split the format string into tokens
        $formatTokens = array();
        preg_match_all('/[\[]+([^\]]*)[\]]+/u', $format, $formatTokens);

        if (!empty($formatTokens[1])) {
            foreach ($formatTokens[1] as $fullToken) {

                // Split the full token name and assign params
                list($name, $length) = array_pad(explode(':', $fullToken), 2, false);

                // Only continue with valid tokens
                $token = $this->getToken($name);
                if (empty($token)) continue;

                // Handle the token based on type
                if ($token['type'] == 'numeric') {
                    $value = $this->incrementNumericToken($name);
                } else {
                    $value = (!empty($length))? substr($token['value'], 0, $length) : $token['value'];
                }

                $username = str_replace('['.$fullToken.']', $value, $username);
            }
        }

        // Remove illegal characters
        str_replace(str_split(self::ILLEGAL_CHARS), '', $username);

        // Limit to max length for database
        $username = mb_substr($username, 0, self::MAX_LENGTH);

        if ($this->isUsernameUnique($username) == false) {
            if (stripos($format, '[number]') === false) {
                $format .= '[number]';
            }

            // Add a numeric token for incrementing possible usernames
            if ($this->getToken('number') == false) {
                $this->addNumericToken('number', 0, 1, 1);
            }

            // Prevent infinite loops
            if (++$this->loopCount > 1000) {
                return 'usernamefailed';
            }

            return $this->generate($format);
        }

        return $username;
    }

    /**
     * Checks a username against the database for uniqueness.
     * @version  v15
     * @since    v15
     * @param    string  $username
     * @return   bool True if unique
     */
    public function isUsernameUnique($username)
    {
        $data = array('username' => $username);
        $sql = "SELECT gibbonPersonID from gibbonPerson WHERE username=:username OR username=LOWER(:username)";
        $result = $this->pdo->executeQuery($data, $sql);

        return ($result->rowCount() == 0);
    }

    /**
     * Increment a numeric token value and optionally invoke a callback with the value as a single parameter.
     * @version  v15
     * @since    v15
     * @param    string  $name
     * @return   string  The incremented value
     */
    protected function incrementNumericToken($name)
    {
        $number = $this->getToken($name);

        if (empty($number) || $number['type'] != 'numeric') {
            throw new InvalidArgumentException();
        }

        // Increment value and format result
        $number['value'] = str_pad(intval($number['value']) + $number['increment'], $number['size'], '0', STR_PAD_LEFT);

        // Is there a callback? Then try to run it
        if (!empty($number['callback']) && is_callable($number['callback'])) {
            call_user_func($number['callback'], $number['value']);
        }

        // Store the updated token value
        $this->tokens[$name] = $number;

        return $number['value'];
    }
}
