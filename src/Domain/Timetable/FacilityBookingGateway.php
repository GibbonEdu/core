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

namespace Gibbon\Domain\Timetable;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class FacilityBookingGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonTTSpaceBooking';

    private static $searchableColumns = [''];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFacilityBookings(QueryCriteria $criteria, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTSpaceBookingID', 'date', 'timeStart', 'timeEnd', 'gibbonSpace.name', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'foreignKey'
            ])
            ->innerJoin('gibbonSpace', 'gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID')
            ->innerJoin('gibbonPerson', 'gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where("foreignKey='gibbonSpaceID'")
            ->where('date >= :today')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonTTSpaceBooking.gibbonPersonID = :gibbonPersonID')
                  ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $query->unionAll()
            ->from($this->getTableName())
            ->cols([
                'gibbonTTSpaceBookingID', 'date', 'timeStart', 'timeEnd', 'gibbonLibraryItem.name', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'foreignKey'
            ])
            ->innerJoin('gibbonLibraryItem', 'gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID')
            ->innerJoin('gibbonPerson', 'gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where("foreignKey='gibbonLibraryItemID'")
            ->where('date >= :today')
            ->bindValue('today', date('Y-m-d'));

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonTTSpaceBooking.gibbonPersonID = :gibbonPersonID')
                  ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }
}
