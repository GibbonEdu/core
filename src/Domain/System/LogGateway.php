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

namespace Gibbon\Domain\System;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Log Gateway
 *
 * @version v17
 * @since   v17
 */
class LogGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonLog';

    private static $searchableColumns = ['title'];
    
    public function selectLogsByModuleAndTitle($moduleName, $title)
    {
        $data = array('moduleName' => $moduleName, 'title' => $title);
        $sql = "SELECT gibbonLog.title as groupBy, gibbonLog.*, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.title
                FROM gibbonLog 
                JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonLog.gibbonModuleID)
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonLog.gibbonPersonID) 
                WHERE gibbonModule.name=:moduleName
                AND gibbonLog.title LIKE :title
                ORDER BY gibbonLog.timestamp DESC";

        return $this->db()->select($sql, $data);
    }

    public function getLogByID($gibbonLogID)
    {
        $data = array('gibbonLogID' => $gibbonLogID);
        $sql = "SELECT gibbonLog.*, gibbonPerson.username, gibbonPerson.surname, gibbonPerson.preferredName 
                FROM gibbonLog
                LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonLog.gibbonPersonID) 
                WHERE gibbonLog.gibbonLogID=:gibbonLogID";

        return $this->db()->selectOne($sql, $data);
    }
}
