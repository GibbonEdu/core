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
    private static $primaryKey = 'gibbonTTSpaceBookingID';

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
                'gibbonTTSpaceBookingID', 'date', 'timeStart', 'timeEnd', 'reason', 'gibbonSpace.name', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'foreignKey', 'foreignKeyID'
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
                'gibbonTTSpaceBookingID', 'date', 'timeStart', 'timeEnd', 'reason', 'gibbonLibraryItem.name', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'foreignKey', 'foreignKeyID'
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

    public function queryFacilityBookingsByDate($startDate, $endDate)
    {
        $data = array('startDate' => $startDate, 'endDate' => $endDate);
        $sql = "SELECT gibbonTTSpaceBookingID, date, timeStart, timeEnd, reason, gibbonSpace.name, gibbonSpace.gibbonSpaceID
            FROM gibbonTTSpaceBooking
                INNER JOIN gibbonSpace ON gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID
            WHERE
                foreignKey='gibbonSpaceID'
                AND date>=:startDate
                AND date<=:endDate";

        return $this->db()->select($sql, $data);

    }
}
