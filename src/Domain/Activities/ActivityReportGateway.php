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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class ActivityReportGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivity';

    private static $searchableColumns = ['gibbonActivity.name', 'gibbonActivity.type'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActivityEnrollmentSummary(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonActivity.gibbonActivityID', 'gibbonActivity.name', 'gibbonActivity.active', 'gibbonActivity.provider', 'gibbonActivity.registration', 'gibbonActivity.type', 'maxParticipants',
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status = 'Accepted' THEN gibbonActivityStudent.gibbonPersonID END) as enrolment",
                "COUNT(DISTINCT CASE WHEN gibbonActivityStudent.status <> 'Not Accepted' THEN gibbonActivityStudent.gibbonPersonID END) as registered",
            ])
            ->leftJoin('gibbonActivityStudent', 'gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID')
            ->leftJoin('gibbonPerson', "gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonPerson.status = 'Full'")
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'))
            ->where('gibbonActivity.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonActivity.gibbonActivityID']);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonActivity.active = :active')
                    ->bindValue('active', $active);
            },
            'registration' => function ($query, $registration) {
                return $query
                    ->where('gibbonActivity.registration = :registration')
                    ->bindValue('registration', $registration);
            },
            'enrolment' => function ($query, $enrolment) {
                if ($enrolment == 'less') $query->having('enrolment < gibbonActivity.maxParticipants AND gibbonActivity.maxParticipants > 0');
                if ($enrolment == 'full') $query->having('enrolment = gibbonActivity.maxParticipants AND gibbonActivity.maxParticipants > 0');
                if ($enrolment == 'greater') $query->having('enrolment > gibbonActivity.maxParticipants AND gibbonActivity.maxParticipants > 0');
                return $query;
            },
            'status' => function ($query, $status) {
                if ($status == 'waiting') $query->having('waiting > 0');
                if ($status == 'pending') $query->having('pending > 0');
                return $query;
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryActivitySpreadByRollGroup(QueryCriteria $criteria, $gibbonRollGroupID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                'gibbonPerson.gibbonPersonID', 'surname', 'preferredName', 'gibbonRollGroup.name as rollGroup'
            ])
            ->leftJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->leftJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->where('gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID')
            ->bindValue('gibbonRollGroupID', $gibbonRollGroupID);

        return $this->runQuery($query, $criteria);
    }
}
