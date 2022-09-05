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

namespace Gibbon\Domain\DataUpdater;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * @version v22
 * @since   v22
 */
class StaffUpdateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffUpdate';
    private static $primaryKey = 'gibbonStaffUpdateID';

    private static $searchableColumns = [''];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDataUpdates(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffUpdateID', 'gibbonStaffUpdate.status', 'gibbonStaffUpdate.timestamp', 'person.title', 'person.preferredName', 'person.surname', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname'
            ])
            ->leftJoin('gibbonStaff', 'gibbonStaff.gibbonStaffID=gibbonStaffUpdate.gibbonStaffID')
            ->leftJoin('gibbonPerson AS person', 'person.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->leftJoin('gibbonPerson AS updater', 'updater.gibbonPersonID=gibbonStaffUpdate.gibbonPersonIDUpdater')
            ->where('gibbonStaffUpdate.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }
}
