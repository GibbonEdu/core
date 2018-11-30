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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class HouseGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonHouse';

    private static $searchableColumns = ['name', 'nameShort'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryHouses(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonHouseID', 'name', 'nameShort', 'logo'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentHouseCountByYearGroup(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonYearGroup.gibbonYearGroupID',
                'gibbonYearGroup.name as yearGroupName',
                'gibbonHouse.name AS house',
                'gibbonHouse.gibbonHouseID',
                "count(gibbonStudentEnrolment.gibbonPersonID) AS total",
                "count(CASE WHEN gibbonPerson.gender='M' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalMale",
                "count(CASE WHEN gibbonPerson.gender='F' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalFemale",
            ])
            ->leftJoin('gibbonPerson', "gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID
                        AND gibbonPerson.status='Full'
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)")
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->groupBy(['gibbonYearGroup.gibbonYearGroupID', 'gibbonHouse.gibbonHouseID'])
            ->having('total > 0')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->bindValue('today', date('Y-m-d'));

        return $this->runQuery($query, $criteria);
    }
}
