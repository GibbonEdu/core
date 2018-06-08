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
class PersonUpdateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonPersonUpdate';

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
                'gibbonPersonUpdateID', 'gibbonPersonUpdate.status', 'gibbonPersonUpdate.timestamp', 'target.preferredName', 'target.surname', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname'
            ])
            ->leftJoin('gibbonPerson AS target', 'target.gibbonPersonID=gibbonPersonUpdate.gibbonPersonID')
            ->leftJoin('gibbonPerson AS updater', 'updater.gibbonPersonID=gibbonPersonUpdate.gibbonPersonIDUpdater')
            ->where('gibbonPersonUpdate.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStudentUpdaterHistory(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDList)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.gibbonPersonID', 'gibbonRollGroup.name as rollGroupName', 
                'MAX(gibbonPersonUpdate.timestamp) as personalUpdate', 
                'MAX(gibbonPersonMedicalUpdate.timestamp) as medicalUpdate'
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonPersonUpdate', 'gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonPersonMedicalUpdate', 'gibbonPersonMedicalUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where("gibbonPerson.status = 'Full'")
            ->where("FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDList)")
            ->where("gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID")
            ->bindValue('gibbonPersonIDList', implode(',', $gibbonPersonIDList))
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonPerson.gibbonPersonID'])
            ;

        $criteria->addFilterRules([
            'cutoff' => function ($query, $cutoffDate) {
                $query->where(function($query) {
                    $query->where('(gibbonPersonUpdateID IS NULL OR gibbonPersonUpdate.timestamp < :cutoffDate)')
                          ->orWhere('(gibbonPersonMedicalUpdateID IS NULL OR gibbonPersonMedicalUpdate.timestamp < :cutoffDate)');
                });

                $query->bindValue('cutoffDate', $cutoffDate);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectParentEmailsByPersonID($gibbonPersonIDList)
    {
        $gibbonPersonIDList = is_array($gibbonPersonIDList) ? implode(',', $gibbonPersonIDList) : $gibbonPersonIDList;
        $data = array('gibbonPersonIDList' => $gibbonPersonIDList);
        $sql = "SELECT gibbonFamilyChild.gibbonPersonID, adult.email 
            FROM gibbonFamilyChild
            LEFT JOIN gibbonFamilyAdult ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
            LEFT JOIN gibbonPerson as adult ON (adult.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :gibbonPersonIDList)
            AND adult.status='Full' AND adult.email <> ''
            AND gibbonFamilyAdult.contactEmail<>'N' 
            AND gibbonFamilyAdult.childDataAccess='Y'
            ORDER BY gibbonFamilyAdult.contactPriority, adult.surname, adult.preferredName";

        return $this->db()->select($sql, $data);
    }
}
