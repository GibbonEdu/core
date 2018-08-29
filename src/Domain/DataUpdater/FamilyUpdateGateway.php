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

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class FamilyUpdateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFamilyUpdate';

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
                'gibbonFamilyUpdateID', 'gibbonFamilyUpdate.status', 'gibbonFamilyUpdate.timestamp', 'gibbonFamily.name as familyName', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname'
            ])
            ->leftJoin('gibbonFamily', 'gibbonFamily.gibbonFamilyID=gibbonFamilyUpdate.gibbonFamilyID')
            ->leftJoin('gibbonPerson AS updater', 'updater.gibbonPersonID=gibbonFamilyUpdate.gibbonPersonIDUpdater')
            ->where('gibbonFamilyUpdate.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFamilyUpdaterHistory(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonYearGroupIDList)
    {
        $gibbonYearGroupIDList = is_array($gibbonYearGroupIDList)? implode(',', $gibbonYearGroupIDList) : $gibbonYearGroupIDList;

        $query = $this
            ->newQuery()
            ->from('gibbonFamily')
            ->cols([
                'gibbonFamily.gibbonFamilyID', 
                'gibbonFamily.name as familyName', 
                'MAX(gibbonFamilyUpdate.timestamp) as familyUpdate', 
                "MIN(IFNULL(gibbonPerson.dateStart, '0000-00-00')) as earliestDateStart",
                "MAX(IFNULL(gibbonPerson.dateEnd, NOW())) as latestEndDate",
                'gibbonFamilyUpdate.gibbonFamilyUpdateID'
            ])
            ->innerJoin('gibbonFamilyChild', 'gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFamilyChild.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonFamilyUpdate', 'gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->where("gibbonPerson.status='Full'")
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)')
            ->bindValue('gibbonYearGroupIDList', $gibbonYearGroupIDList)
            ->groupBy(['gibbonFamily.gibbonFamilyID'])
            ->having('latestEndDate >= NOW()');

        $criteria->addFilterRules([
            'cutoff' => function ($query, $cutoffDate) {
                $query->having("(gibbonFamilyUpdateID IS NULL OR familyUpdate < :cutoffDate) AND (earliestDateStart < :cutoffDate)");
                $query->bindValue('cutoffDate', $cutoffDate);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectFamilyAdultUpdatesByFamily($gibbonFamilyIDList)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;
        $data = array('gibbonFamilyIDList' => $gibbonFamilyIDList);
        $sql = "SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.status, MAX(gibbonPersonUpdate.timestamp) as personalUpdate
            FROM gibbonFamilyAdult
            JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
            LEFT JOIN gibbonPersonUpdate ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyAdult.gibbonFamilyID, :gibbonFamilyIDList) 
            AND gibbonPerson.status='Full'
            GROUP BY gibbonFamilyAdult.gibbonPersonID 
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectFamilyChildUpdatesByFamily($gibbonFamilyIDList, $gibbonSchoolYearID)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;
        $data = array('gibbonFamilyIDList' => $gibbonFamilyIDList, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFamilyChild.gibbonFamilyID, '' as title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.status, gibbonRollGroup.nameShort as rollGroup, MAX(gibbonPersonUpdate.timestamp) as personalUpdate, MAX(gibbonPersonMedicalUpdate.timestamp) as medicalUpdate, gibbonPerson.dateStart AS dateStart
            FROM gibbonFamilyChild
            JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID)
            JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
            LEFT JOIN gibbonPersonUpdate ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID)
            LEFT JOIN gibbonPersonMedicalUpdate ON (gibbonPersonMedicalUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonFamilyID, :gibbonFamilyIDList) 
            AND gibbonPerson.status='Full'
            AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            GROUP BY gibbonFamilyChild.gibbonPersonID 
            ORDER BY gibbonYearGroup.sequenceNumber, gibbonRollGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }
}
