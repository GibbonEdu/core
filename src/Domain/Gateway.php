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

namespace Gibbon\Domain;

use Gibbon\sqlConnection;

/**
 * Gateway
 *
 * @version v16
 * @since   v16
 */
abstract class Gateway
{
    protected $pdo;

    public function __construct(sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }    

    // DATA MANIPULATION
    protected function doInsert($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    protected function doUpdate($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);

        return $result->rowCount() > 0;
    }

    protected function doDelete($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);
        
        return $result->rowCount() > 0;
    }

    protected function doCopy($sql, $data = array())
    {
        $result = $this->pdo->executeQuery($data, $sql);
        
        return $result->rowCount() > 0;
    }

    // DATA SELECT
    protected function doSelect($sql, $data = array())
    {
        return $this->pdo->executeQuery($data, $sql);
    }
    
    protected function doFetch($sql, $data = array())
    {
        return $this->pdo->executeQuery($data, $sql)->fetch();
    }

    protected function doCount($sql, $data = array())
    {
        return $this->pdo->executeQuery($data, $sql)->fetchColumn(0);
    }
}