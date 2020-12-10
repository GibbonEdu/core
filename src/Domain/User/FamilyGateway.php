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

namespace Gibbon\Domain\User;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByFamily;

/**
 * @version v16
 * @since   v16
 */
class FamilyGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByFamily;

    private static $tableName = 'gibbonFamily';
    private static $primaryKey = 'gibbonFamilyID';

    private static $searchableColumns = ['name'];

    private static $scrubbableKey = 'gibbonFamilyID';
    private static $scrubbableColumns = ['nameAddress' => '', 'homeAddress' => '', 'homeAddressDistrict' => '', 'homeAddressCountry' => '', 'status' => 'Other', 'languageHomePrimary' => '', 'languageHomeSecondary' => ''];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFamilies(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFamilyID', 'name', 'status'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryFamiliesByStudent(QueryCriteria $criteria, $gibbonPersonID)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;

        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFamily.gibbonFamilyID', 'gibbonFamily.*', "GROUP_CONCAT(DISTINCT gibbonFamilyChild.gibbonPersonID SEPARATOR ',') as gibbonPersonIDList"
            ])
            ->innerJoin('gibbonFamilyChild', 'gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->where('FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :gibbonPersonIDList)')
            ->bindValue('gibbonPersonIDList', $gibbonPersonIDList)
            ->groupBy(['gibbonFamily.gibbonFamilyID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryFamiliesByAdult(QueryCriteria $criteria, $gibbonPersonID)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;

        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFamily.gibbonFamilyID', 'gibbonFamily.*', "GROUP_CONCAT(DISTINCT gibbonFamilyAdult.gibbonPersonID SEPARATOR ',') as gibbonPersonIDList"
            ])
            ->innerJoin('gibbonFamilyAdult', 'gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->where('FIND_IN_SET(gibbonFamilyAdult.gibbonPersonID, :gibbonPersonIDList)')
            ->bindValue('gibbonPersonIDList', $gibbonPersonIDList)
            ->groupBy(['gibbonFamily.gibbonFamilyID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectFamiliesWithActiveStudents($gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFamily.gibbonFamilyID', 'gibbonFamily.name', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonRollGroup.nameShort as rollGroup', 'gibbonRollGroup.gibbonRollGroupID', 'gibbonYearGroup.gibbonYearGroupID'
            ])
            ->innerJoin('gibbonFamilyChild', 'gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonFamilyChild.gibbonPersonID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', 'gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->orderBy(['gibbonYearGroup.sequenceNumber', 'gibbonRollGroup.nameShort', 'gibbonPerson.surname', 'gibbonPerson.preferredName']);

        return $this->runSelect($query);
    }

    public function selectAdultsByFamily($gibbonFamilyIDList, $allFields = false)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;

        $query = $this
            ->newSelect()
            ->cols($allFields
                ? ['gibbonFamilyAdult.gibbonFamilyID', 'gibbonFamilyAdult.*', 'gibbonPerson.*']
                : ['gibbonFamilyAdult.gibbonFamilyID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.status', 'gibbonPerson.email'])
            ->from('gibbonFamilyAdult')
            ->innerJoin('gibbonPerson', 'gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('FIND_IN_SET(gibbonFamilyAdult.gibbonFamilyID, :gibbonFamilyIDList)')
            ->bindValue('gibbonFamilyIDList', $gibbonFamilyIDList)
            ->orderBy(['gibbonPerson.surname', 'gibbonPerson.preferredName']);

        return $this->runSelect($query);
    }

    public function selectChildrenByFamily($gibbonFamilyIDList, $allFields = false)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;

        $query = $this
            ->newSelect()
            ->cols($allFields
                ? ['gibbonFamilyChild.gibbonFamilyID', 'gibbonFamilyChild.*', 'gibbonPerson.*']
                : ['gibbonFamilyChild.gibbonFamilyID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.status', 'gibbonPerson.email'])
            ->from('gibbonFamilyChild')
            ->innerJoin('gibbonPerson', 'gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('FIND_IN_SET(gibbonFamilyChild.gibbonFamilyID, :gibbonFamilyIDList)')
            ->bindValue('gibbonFamilyIDList', $gibbonFamilyIDList)
            ->orderBy(['gibbonPerson.surname', 'gibbonPerson.preferredName']);

        return $this->runSelect($query);
    }

    public function selectFamilyAdultsByStudent($gibbonPersonID, $allUsers = false)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;
        $data = array('gibbonPersonIDList' => $gibbonPersonIDList);
        $sql = "SELECT gibbonFamilyChild.gibbonPersonID, gibbonFamilyAdult.gibbonFamilyID, gibbonPerson.*, gibbonFamilyAdult.childDataAccess, gibbonFamilyAdult.contactEmail, gibbonFamilyAdult.contactCall
            FROM gibbonFamilyChild
            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
            JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :gibbonPersonIDList)";

        if (!$allUsers) $sql .= " AND gibbonPerson.status='Full'";

        $sql .= " ORDER BY gibbonFamilyAdult.contactPriority, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectFamiliesByStudent($gibbonPersonID)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;
        $data = array('gibbonPersonIDList' => $gibbonPersonIDList);
        $sql = "SELECT gibbonFamilyChild.gibbonPersonID, gibbonFamily.*
            FROM gibbonFamilyChild
            JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :gibbonPersonIDList)
            ORDER BY gibbonFamily.name";

        return $this->db()->select($sql, $data);
    }
}
