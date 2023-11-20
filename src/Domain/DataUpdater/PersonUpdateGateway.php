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
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v16
 * @since   v16
 */
class PersonUpdateGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonPersonUpdate';
    private static $primaryKey = 'gibbonPersonUpdateID';

    private static $searchableColumns = ['target.surname', 'target.preferredName', 'target.username'];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['address1' => '','address1District' => '','address1Country' => '','address2' => '','address2District' => '','address2Country' => '','phone1Type' => '','phone1CountryCode' => '','phone1' => '','phone3Type' => '','phone3CountryCode' => '','phone3' => '','phone2Type' => '','phone2CountryCode' => '','phone2' => '','phone4Type' => '','phone4CountryCode' => '','phone4' => '','languageFirst' => '','languageSecond' => '','languageThird' => '','countryOfBirth' => '','ethnicity' => '','religion' => '','profession'=> null,'employer'=> null,'jobTitle'=> null,'emergency1Name'=> null,'emergency1Number1'=> null,'emergency1Number2'=> null,'emergency1Relationship'=> null,'emergency2Name'=> null,'emergency2Number1'=> null,'emergency2Number2'=> null,'emergency2Relationship'=> null,'vehicleRegistration' => '','fields' => ''];
    
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
                'gibbonPersonUpdateID', 'gibbonPersonUpdate.status', 'gibbonPersonUpdate.timestamp', 'target.preferredName', 'target.surname', 'target.gibbonPersonID as gibbonPersonIDTarget', 'updater.gibbonPersonID as gibbonPersonIDUpdater', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname', 'gibbonRole.category as roleCategory'
            ])
            ->leftJoin('gibbonPerson AS target', 'target.gibbonPersonID=gibbonPersonUpdate.gibbonPersonID')
            ->leftJoin('gibbonPerson AS updater', 'updater.gibbonPersonID=gibbonPersonUpdate.gibbonPersonIDUpdater')
            ->leftJoin('gibbonRole', 'gibbonRole.gibbonRoleID=target.gibbonRoleIDPrimary')
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
                'gibbonPerson.gibbonPersonID', 
                'gibbonPerson.surname', 
                'gibbonPerson.preferredName', 
                'gibbonPerson.gibbonPersonID', 
                'gibbonFormGroup.name as formGroupName', 
                'gibbonPersonUpdate.gibbonPersonUpdateID', 
                'gibbonPersonMedicalUpdate.gibbonPersonMedicalUpdateID', 
                "MAX(gibbonPersonUpdate.timestamp) as personalUpdate", 
                "MAX(gibbonPersonMedicalUpdate.timestamp) as medicalUpdate"
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
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
                $query->having("((gibbonPersonUpdateID IS NULL OR personalUpdate < :cutoffDate)
                    OR (gibbonPersonMedicalUpdateID IS NULL OR medicalUpdate < :cutoffDate))");
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
