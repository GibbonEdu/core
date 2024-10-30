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

namespace Gibbon\Domain;

use Gibbon\Contracts\Database\Connection;

/**
 * Gateway
 *
 * @version v16
 * @since   v16
 */
abstract class Gateway
{
    /**
     * The internal PDO connection.
     * 
     * @var Connection
     */
    private $db;

    /**
     * Create a new gateway instance using the supplied database connection.
     * 
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }
    
    /**
     * Inheriting classes can get the current database connection.
     *
     * @return Connection
     */
    protected function db()
    {
        return $this->db;
    }
}
