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

namespace Gibbon\Domain\UserAdmin;

use Gibbon\Domain\Gateway;
use Gibbon\sqlConnection;
use Gibbon\Tables\DataFilters;
use Gibbon\Tables\DataSet;

/**
 * Person Gateway
 *
 * Provides a data access layer for the gibbonSchoolYear table
 *
 * @version v16
 * @since   v16
 */
class PersonGateway extends Gateway
{
    public function selectAll(array $params)
    {
        $totalRows = $this->pdo->executeQuery(array(), "SELECT COUNT(*) FROM gibbonPerson")->fetchColumn(0);
        $params['totalRows'] = $totalRows;

        $filters = DataFilters::createFromArray($params);

        $data = array();
        // $sql = "SELECT gibbonPerson.*, gibbonRole.name as primaryRole FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)";
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, CONCAT(gibbonPerson.surname, ', ', gibbonPerson.preferredName) as fullName, gibbonPerson.username, gibbonPerson.image_240, gibbonPerson.status, gibbonRole.name as primaryRole FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)";
        $sql = $this->applyDataFilters($sql, $filters);

        $result = $this->pdo->executeQuery($data, $sql);

        return DataSet::createFromResult($result)->setFilters($filters)->setTotalRows($totalRows);
    }
}