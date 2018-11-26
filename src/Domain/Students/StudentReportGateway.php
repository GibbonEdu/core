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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * @version v17
 * @since   v17
 */
class StudentReportGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonStudentEnrolment';
    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStudentTransport(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.transport', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.address1', 'gibbonPerson.address1District', 'gibbonPerson.address1Country', 'gibbonRollGroup.nameShort as rollGroup',
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'));


        return $this->runQuery($query, $criteria);
    }

    public function queryStudentCountByRollGroup(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonRollGroup')
            ->cols([
                'gibbonRollGroup.name as rollGroup',
                'gibbonYearGroup.sequenceNumber',
                'FORMAT(AVG((TO_DAYS(NOW())-TO_DAYS(gibbonPerson.dob)))/365.242199, 1) as meanAge',
                "count(DISTINCT gibbonPerson.gibbonPersonID) AS total",
                "count(CASE WHEN gibbonPerson.gender='M' THEN gibbonPerson.gibbonPersonID END) as totalMale",
                "count(CASE WHEN gibbonPerson.gender='F' THEN gibbonPerson.gibbonPersonID END) as totalFemale",
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->where('gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonRollGroup.gibbonRollGroupID']);

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
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.privacy', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonRollGroup.nameShort as rollGroup',
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
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
}
