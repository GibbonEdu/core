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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class FacilityGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSpace';
    private static $primaryKey = 'gibbonSpaceID';

    private static $searchableColumns = ['name', 'type'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFacilities(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSpaceID', 'name', 'type', 'active', 'capacity', 'computer', 'computerStudent', 'projector', 'tv', 'dvd', 'hifi', 'speakers', 'iwb', 'phoneInternal', 'phoneExternal'
            ]);
        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonSpace.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectFacilityInfoByName($gibbonSpaceNameList)
    {
        $gibbonSpaceNameList = is_array($gibbonSpaceNameList) ? implode(',', $gibbonSpaceNameList) : $gibbonSpaceNameList;

        $data = ['gibbonSpaceNameList' => $gibbonSpaceNameList];
        $sql = "SELECT * FROM gibbonSpace 
                WHERE FIND_IN_SET(name, :gibbonSpaceNameList) 
                ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectFacilityInUseByDateAndTime($gibbonSpaceID, $date, $timeStart, $timeEnd)
    {
        $data = ['gibbonSpaceID' => $gibbonSpaceID, 'date' => $date, 'timeStart' => $timeStart, 'timeEnd' => $timeEnd];
        $sql = "(
                SELECT CONCAT(gibbonPerson.preferredName, ' ', gibbonPerson.surname) AS name
                FROM gibbonTTSpaceBooking 
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID = gibbonTTSpaceBooking.gibbonPersonID )
                WHERE foreignKey='gibbonSpaceID' 
                AND foreignKeyID=:gibbonSpaceID 
                AND date=:date 
                AND (
                    (gibbonTTSpaceBooking.timeStart >= :timeStart AND gibbonTTSpaceBooking.timeStart < :timeEnd)
                    OR (:timeStart >= gibbonTTSpaceBooking.timeStart AND :timeStart < gibbonTTSpaceBooking.timeEnd)
                    OR (:timeStart = gibbonTTSpaceBooking.timeStart AND :timeEnd = gibbonTTSpaceBooking.timeEnd)
                )
            )
            UNION ALL
            (
                SELECT CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                FROM gibbonTTSpaceChange
                JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTDayRowClassID=gibbonTTSpaceChange.gibbonTTDayRowClassID)
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                WHERE gibbonTTSpaceChange.date=:date
                AND gibbonTTSpaceChange.gibbonSpaceID=:gibbonSpaceID
                AND (
                    (gibbonTTColumnRow.timeStart >= :timeStart AND gibbonTTColumnRow.timeStart < :timeEnd)
                    OR (:timeStart >= gibbonTTColumnRow.timeStart AND :timeStart < gibbonTTColumnRow.timeEnd)
                    OR (:timeStart = gibbonTTColumnRow.timeStart AND :timeEnd = gibbonTTColumnRow.timeEnd)
                )
            )
            UNION ALL
            (
                SELECT CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                FROM gibbonTTDayRowClass
                JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
                JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID)
                JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                WHERE gibbonTTDayDate.date=:date
                AND gibbonTTDayRowClass.gibbonSpaceID=:gibbonSpaceID
                AND (
                    (gibbonTTColumnRow.timeStart >= :timeStart AND gibbonTTColumnRow.timeStart < :timeEnd)
                    OR (:timeStart >= gibbonTTColumnRow.timeStart AND :timeStart < gibbonTTColumnRow.timeEnd)
                    OR (:timeStart = gibbonTTColumnRow.timeStart AND :timeEnd = gibbonTTColumnRow.timeEnd)
                )
                AND (SELECT gibbonTTSpaceChangeID FROM gibbonTTSpaceChange AS roomReleased JOIN gibbonTTDayRowClass AS roomTT ON (roomTT.gibbonTTDayRowClassID=roomReleased.gibbonTTDayRowClassID) WHERE roomReleased.date=:date AND roomReleased.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND roomTT.gibbonSpaceID=:gibbonSpaceID LIMIT 1) IS NULL
            )";

        return $this->db()->select($sql, $data);
    }
}
