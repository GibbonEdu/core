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

namespace Gibbon\Domain\DataUpdater;

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
class FamilyUpdateGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByFamily;

    private static $tableName = 'gibbonFamilyUpdate';
    private static $primaryKey = 'gibbonFamilyUpdateID';

    private static $searchableColumns = ['gibbonFamily.name', 'gibbonFamily.nameAddress'];
    
    private static $scrubbableKey = 'gibbonFamilyID';
    private static $scrubbableColumns = ['nameAddress' => '', 'homeAddress' => '', 'homeAddressDistrict' => '', 'homeAddressCountry' => '', 'status' => 'Other', 'languageHomePrimary' => '', 'languageHomeSecondary' => ''];
    
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
                'gibbonFamilyUpdateID', 'gibbonFamilyUpdate.status', 'gibbonFamilyUpdate.timestamp', 'gibbonFamily.name as familyName',  'gibbonFamily.gibbonFamilyID', 'updater.gibbonPersonID as gibbonPersonIDUpdater', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname'
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
    public function queryFamilyUpdaterHistory(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonYearGroupIDList, $requiredUpdatesByType)
    {
        $gibbonYearGroupIDList = is_array($gibbonYearGroupIDList)? implode(',', $gibbonYearGroupIDList) : $gibbonYearGroupIDList;

        $query = $this
            ->newQuery()
            ->from('gibbonFamily')
            ->cols([
                'gibbonFamily.gibbonFamilyID', 
                'gibbonFamily.name as familyName', 
                'MAX(gibbonFamilyUpdate.timestamp) as familyUpdate', 
                "MAX(IFNULL(gibbonPerson.dateEnd, NOW())) as latestEndDate",
                'gibbonFamilyUpdate.gibbonFamilyUpdateID'
            ])
            ->innerJoin('gibbonFamilyChild', 'gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonFamilyChild.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonFamilyUpdate', 'gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=CURRENT_DATE)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=CURRENT_DATE)')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)')
            ->bindValue('gibbonYearGroupIDList', $gibbonYearGroupIDList)
            ->groupBy(['gibbonFamily.gibbonFamilyID'])
            ->having('latestEndDate >= NOW()');

        $criteria->addFilterRules([
            'cutoff' => function ($query, $cutoffDate) use ($requiredUpdatesByType) {
                $havingCutoff = "(gibbonFamilyUpdateID IS NULL OR familyUpdate < :cutoffDate)";

                if (in_array('Personal', $requiredUpdatesByType)) {
                    $query->cols([
                        "MAX(IFNULL(studentUpdate.timestamp, '0000-00-00')) as earliestStudentUpdate", 
                        "MAX(IFNULL(adultUpdate.timestamp, '0000-00-00')) as earliestAdultUpdate"
                    ])
                    ->leftJoin('gibbonPersonUpdate AS studentUpdate', 'studentUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID')
                    ->leftJoin('gibbonFamilyAdult', 'gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
                    ->leftJoin('gibbonPerson AS adult', "adult.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID AND adult.status='Full'")
                    ->leftJoin('gibbonPersonUpdate AS adultUpdate', 'adultUpdate.gibbonPersonID=adult.gibbonPersonID');
                    $havingCutoff .= " OR (earliestStudentUpdate < :cutoffDate) OR (earliestAdultUpdate < :cutoffDate)";
                }

                if (in_array('Medical', $requiredUpdatesByType)) {
                    $query->cols([
                        "MAX(IFNULL(medicalUpdate.timestamp, '0000-00-00')) as earliestMedicalUpdate", 
                    ])
                    ->leftJoin('gibbonPersonMedicalUpdate AS medicalUpdate', 'medicalUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID');
                    $havingCutoff .= " OR (earliestMedicalUpdate < :cutoffDate)";
                }

                $query->having($havingCutoff)
                    ->bindValue('cutoffDate', $cutoffDate);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectFamilyAdultUpdatesByFamily($gibbonFamilyIDList)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;
        $data = array('gibbonFamilyIDList' => $gibbonFamilyIDList);
        $sql = "SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.status, MAX(gibbonPersonUpdate.timestamp) as personalUpdate, (CASE WHEN gibbonFamilyAdult.contactEmail='Y' THEN gibbonPerson.email ELSE '' END) as email
            FROM gibbonFamilyAdult
            JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
            LEFT JOIN gibbonPersonUpdate ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyAdult.gibbonFamilyID, :gibbonFamilyIDList) 
            AND gibbonPerson.status='Full'
            GROUP BY gibbonFamilyAdult.gibbonPersonID 
            ORDER BY gibbonFamilyAdult.contactPriority ASC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectFamilyChildUpdatesByFamily($gibbonFamilyIDList, $gibbonSchoolYearID)
    {
        $gibbonFamilyIDList = is_array($gibbonFamilyIDList) ? implode(',', $gibbonFamilyIDList) : $gibbonFamilyIDList;
        $data = array('gibbonFamilyIDList' => $gibbonFamilyIDList, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonFamilyChild.gibbonFamilyID, '' as title, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.status, gibbonFormGroup.nameShort as formGroup, MAX(gibbonPersonUpdate.timestamp) as personalUpdate, MAX(gibbonPersonMedicalUpdate.timestamp) as medicalUpdate, gibbonPerson.dateStart AS dateStart
            FROM gibbonFamilyChild
            JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
            JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
            LEFT JOIN gibbonPersonUpdate ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID)
            LEFT JOIN gibbonPersonMedicalUpdate ON (gibbonPersonMedicalUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonFamilyID, :gibbonFamilyIDList) 
            AND gibbonPerson.status='Full'
            AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            GROUP BY gibbonFamilyChild.gibbonPersonID 
            ORDER BY gibbonYearGroup.sequenceNumber, gibbonFormGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }
}
