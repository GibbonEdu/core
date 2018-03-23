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

namespace Gibbon\SchoolAdmin\Domain;

use Gibbon\sqlConnection;
use Gibbon\Domain\Gateway;
use Gibbon\Domain\QueryFilters;
use Gibbon\Domain\ResultSet;

/**
 * School Year Gateway
 *
 * Provides a data access layer for the gibbonSchoolYear table
 *
 * @version v16
 * @since   v16
 */
class SchoolYearGateway extends Gateway
{
    protected static $tableName = 'gibbonSchoolYear';

    public function getSchoolYearList(QueryFilters $filters)
    {
        $data = array();
        $sql = "SELECT gibbonSchoolYearID, name, sequenceNumber, firstDay, lastDay, status FROM gibbonSchoolYear";
        $sql = $filters->applyFilters($sql);

        $result = $this->pdo->executeQuery($data, $sql);

        return ResultSet::createFromResults($filters, $result, $this->countAll());
    }

    public function countAll()
    {
        return $this->pdo->executeQuery(array(), "SELECT COUNT(*) FROM gibbonSchoolYear")->fetchColumn(0);
    }
}