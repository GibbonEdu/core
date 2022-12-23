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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Staff Duty Person Gateway
 *
 * @version v25
 * @since   v25
 */
class StaffDutyPersonGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStaffDutyPerson';
    private static $primaryKey = 'gibbonStaffDutyPersonID';

    private static $searchableColumns = [''];

    /**
     * Queries the duty roster.
     *
     * @return DataSet
     */
    public function selectDutyRoster() {
        $query = $this
            ->newSelect()
            ->cols([
                'gibbonStaffDuty.gibbonStaffDutyID as groupBy', 'gibbonStaffDuty.gibbonStaffDutyID', 'gibbonStaffDutyPerson.gibbonStaffDutyPersonID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.title', 'gibbonPerson.image_240', 'gibbonDaysOfWeek.gibbonDaysOfWeekID', 'gibbonDaysOfWeek.name as weekdayName'
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonStaffDuty', 'gibbonStaffDuty.gibbonStaffDutyID=gibbonStaffDutyPerson.gibbonStaffDutyID')
            ->innerJoin('gibbonDaysOfWeek', 'gibbonDaysOfWeek.gibbonDaysOfWeekID=gibbonStaffDutyPerson.gibbonDaysOfWeekID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStaffDutyPerson.gibbonPersonID')
            ->where('gibbonPerson.status="Full"');

        return $this->runSelect($query);
    }
}
