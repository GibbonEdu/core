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
 * Username Generator
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
    protected $defaultFormat = '[preferredNameInitial][surname]';

    public function __construct(sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addToken($name, $value)
    {
        if (empty($name)) {
            throw new InvalidArgumentException();
        }

        $this->tokens[$name] = strtolower($value);
    }

    public function addTokens($array)
    {
        foreach ($array as $name => $value)
        {
            $this->addToken($name, $value);
        }
    }

    public function addNumericToken($name, $value, $size, $increment)
    {
        $number = str_pad($value + $increment, $size, '0', STR_PAD_LEFT);
        $this->addToken($name, $number);

        return $number;
    }

    public function generateByRole($gibbonRoleID)
    {
        $usernameFormat = '';

        $data = array('gibbonRoleID' => $gibbonRoleID);
        $sql = "SELECT * FROM gibbonUsernameFormat WHERE (FIND_IN_SET(:gibbonRoleID, gibbonRoleIDList)) OR isDefault='Y' ORDER BY FIND_IN_SET(:gibbonRoleID, gibbonRoleIDList) DESC LIMIT 1";

        $result = $this->pdo->executeQuery($data, $sql);

        if ($result->rowCount() > 0) {
            $row = $result->fetch(0);

            $usernameFormat = $row['format'];

            if ($row['isNumeric'] == 'Y') {
                $number = $this->addNumericToken('[number]', $row['numericValue'], $row['numericSize'], $row['numericIncrement']);

                $data = array('gibbonUsernameFormatID' => $row['gibbonUsernameFormatID'], 'numericValue' => $number);
                $sql = "UPDATE gibbonUsernameFormat SET numericValue=:numericValue WHERE gibbonUsernameFormatID=:gibbonUsernameFormatID";
                $result = $this->pdo->executeQuery($data, $sql);
            }
        }

        return $this->generate($usernameFormat);
    }

    public function generate($format)
    {
        $username = $format;

        if (empty($username)) {
            $username = $this->defaultFormat;
        }

        // Replace named tokens with values
        foreach ($this->tokens as $name => $value) {
            $username = str_replace($name, $value, $username);
        }

        // Remove illegal characters
        str_replace(str_split(self::ILLEGAL_CHARS), '', $username);

        // Limit to max length for database
        $username = substr($username, 0, self::MAX_LENGTH);

        return $username;
    }

    /**
     * Checks a username against the database for uniqueness
     * @version  v15
     * @since    v15
     * @param    string  $username
     * @return   bool
     */
    public function checkUniqueness($username)
    {
        $data = array('username' => $username);
        $sql = "SELECT gibbonPersonID from gibbonPerson WHERE username=:username OR username=LOWER(:username)";

        $result = $this->pdo->executeQuery($data, $sql);

        return ($result->rowCount() == 0);
    }
}
