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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\SharedUserLogic;

/**
 * @version v17
 * @since   v17
 */
class StudentReportGateway extends QueryableGateway
{
    use TableAware;
    use SharedUserLogic;

    private static $tableName = 'gibbonStudentEnrolment';
    private static $searchableColumns = [];


    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStudentDetails(QueryCriteria $criteria, $gibbonPersonID)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID) ? implode(',', $gibbonPersonID) : $gibbonPersonID;

        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.*', 'gibbonPersonMedical.*',
                "(SELECT timestamp FROM gibbonPersonUpdate WHERE gibbonPersonID=gibbonPerson.gibbonPersonID AND status='Complete' ORDER BY timestamp DESC LIMIT 1) as lastPersonalUpdate",
                "(SELECT timestamp FROM gibbonPersonMedicalUpdate WHERE gibbonPersonID=gibbonPerson.gibbonPersonID AND status='Complete' ORDER BY timestamp DESC LIMIT 1) as lastMedicalUpdate",
                'gibbonPerson.gibbonPersonID'
            ])
            ->leftJoin('gibbonPersonMedical', 'gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDList)')
            ->bindValue('gibbonPersonIDList', $gibbonPersonIDList);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentTransport(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.transport', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.address1', 'gibbonPerson.address1District', 'gibbonPerson.address1Country', 'gibbonFormGroup.nameShort as formGroup',
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'));


        return $this->runQuery($query, $criteria);
    }

    public function queryStudentCountByFormGroup(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonFormGroup')
            ->cols([
                'gibbonFormGroup.name as formGroup',
                'gibbonYearGroup.sequenceNumber',
                'FORMAT(AVG((TO_DAYS(NOW())-TO_DAYS(gibbonPerson.dob)))/365.242199, 1) as meanAge',
                "count(DISTINCT gibbonPerson.gibbonPersonID) AS total",
                "count(CASE WHEN gibbonPerson.gender='M' THEN gibbonPerson.gibbonPersonID END) as totalMale",
                "count(CASE WHEN gibbonPerson.gender='F' THEN gibbonPerson.gibbonPersonID END) as totalFemale",
                "count(CASE WHEN gibbonPerson.gender='Other' THEN gibbonPerson.gibbonPersonID END) as totalOther",
                "count(CASE WHEN gibbonPerson.gender='Unspecified' THEN gibbonPerson.gibbonPersonID END) as totalUnspecified",
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->where('gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonFormGroup.gibbonFormGroupID']);

        if (!$criteria->hasFilter('from')) {
            $query->where("gibbonPerson.status='Full'");
        }

        $criteria->addFilterRules([
            'from' => function ($query, $date) {
                return $query
                    ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:date)')
                    ->bindValue('date', $date);
            },
            'to' => function ($query, $date) {
                return $query
                    ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:date)')
                    ->bindValue('date', $date);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentPrivacyChoices(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.privacy', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonFormGroup.nameShort as formGroup',
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("(gibbonPerson.privacy <> '' AND gibbonPerson.privacy IS NOT NULL)")
            ->where("gibbonPerson.status = 'Full'")
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'));

        return $this->runQuery($query, $criteria);
    }

    public function queryStudentStatusBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $status = 'Full', $dateFrom = null, $dateTo = null, $ignoreEnrolment = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonStudentEnrolmentID', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username', 'officialName', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonFormGroup.nameShort AS formGroup', 'gibbonStudentEnrolment.rollOrder', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', 'gibbonPerson.status', 'gibbonPerson.lastSchool', 'gibbonPerson.departureReason', 'gibbonPerson.nextSchool', "'Student' as roleCategory"
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->leftJoin('gibbonSchoolYear AS currentSchoolYear', 'currentSchoolYear.gibbonSchoolYearID = gibbonStudentEnrolment.gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if ($ignoreEnrolment) {
            $query->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
                  ->where("gibbonRole.category='Student'");
        } else {
            $query->where("gibbonStudentEnrolment.gibbonStudentEnrolmentID IS NOT NULL")
                  ->where('gibbonPerson.status = :status')
                  ->bindValue('status', $status);
        }

        if (!empty($dateFrom) && !empty($dateTo)) {
            $query->where($status == 'Full'
                ? 'gibbonPerson.dateStart BETWEEN :dateFrom AND :dateTo'
                : 'gibbonPerson.dateEnd BETWEEN :dateFrom AND :dateTo')
            ->bindValue('dateFrom', $dateFrom)
            ->bindValue('dateTo', $dateTo);
        }

        if ($status == 'Full' && empty($dateFrom)) {
            // This ensures the new student list for the current year excludes any students who were enrolled in the previous year
            $query->cols(['(
                SELECT COUNT(*) FROM gibbonStudentEnrolment AS pastEnrolment WHERE pastEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND pastEnrolment.gibbonSchoolYearID=(
                    SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE sequenceNumber=(
                        SELECT MAX(sequenceNumber) FROM gibbonSchoolYear WHERE sequenceNumber < currentSchoolYear.sequenceNumber
                        )
                    )
                ) AS pastEnrolmentCount'])
                ->having('pastEnrolmentCount = 0');
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectStudentCountByYearGroup($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d')];
        $sql = "SELECT gibbonYearGroup.nameShort as yearGroup, count(DISTINCT gibbonStudentEnrolmentID) as studentCount
                FROM gibbonStudentEnrolment
                JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                WHERE gibbonPerson.status='Full'
                AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (dateStart IS NULL OR dateStart<=:today)
                AND (dateEnd IS NULL OR dateEnd>=:today)
                GROUP BY gibbonYearGroup.gibbonYearGroupID
                ORDER BY gibbonYearGroup.sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
