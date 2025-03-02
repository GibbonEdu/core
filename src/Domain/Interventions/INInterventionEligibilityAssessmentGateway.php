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

namespace Gibbon\Domain\Interventions;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Intervention Eligibility Assessment Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionEligibilityAssessmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINInterventionEligibilityAssessment';
    private static $primaryKey = 'gibbonINInterventionEligibilityAssessmentID';
    private static $searchableColumns = [''];
    
    /**
     * Get an eligibility assessment by intervention ID
     *
     * @param int $gibbonINInterventionID
     * @return array|null
     */
    public function getByInterventionID($gibbonINInterventionID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.gibbonINInterventionEligibilityAssessmentID',
                'gibbonINInterventionEligibilityAssessment.gibbonINInterventionID',
                'gibbonINInterventionEligibilityAssessment.gibbonPersonIDStudent',
                'gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator',
                'gibbonINInterventionEligibilityAssessment.status',
                'gibbonINInterventionEligibilityAssessment.eligibilityDecision',
                'gibbonINInterventionEligibilityAssessment.notes',
                'gibbonINInterventionEligibilityAssessment.timestampCreated',
                'gibbonPerson.title',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName'
            ])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator')
            ->where('gibbonINInterventionEligibilityAssessment.gibbonINInterventionID=:gibbonINInterventionID')
            ->bindValue('gibbonINInterventionID', $gibbonINInterventionID);

        return $this->runSelect($query)->fetch();
    }
    
    /**
     * Get all eligibility assessments for a student
     *
     * @param int $gibbonPersonIDStudent
     * @return array
     */
    public function getByStudentID($gibbonPersonIDStudent)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.gibbonINInterventionEligibilityAssessmentID',
                'gibbonINInterventionEligibilityAssessment.gibbonINInterventionID',
                'gibbonINInterventionEligibilityAssessment.gibbonPersonIDStudent',
                'gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator',
                'gibbonINInterventionEligibilityAssessment.status',
                'gibbonINInterventionEligibilityAssessment.eligibilityDecision',
                'gibbonINInterventionEligibilityAssessment.notes',
                'gibbonINInterventionEligibilityAssessment.timestampCreated',
                'gibbonINIntervention.name as interventionName',
                'gibbonPerson.title',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName'
            ])
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonINInterventionEligibilityAssessment.gibbonPersonIDCreator')
            ->leftJoin('gibbonINIntervention', 'gibbonINIntervention.gibbonINInterventionID=gibbonINInterventionEligibilityAssessment.gibbonINInterventionID')
            ->where('gibbonINInterventionEligibilityAssessment.gibbonPersonIDStudent=:gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);

        return $this->runSelect($query)->toArray();
    }
    
    /**
     * Query eligibility assessments with criteria
     *
     * @param QueryCriteria $criteria
     * @param string $gibbonSchoolYearID
     * @return DataSet
     */
    public function queryEligibilityAssessments(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInterventionEligibilityAssessment.gibbonINInterventionEligibilityAssessmentID',
                'gibbonINInterventionEligibilityAssessment.gibbonINInterventionID',
                'gibbonINInterventionEligibilityAssessment.status',
                'gibbonINInterventionEligibilityAssessment.eligibilityDecision',
                'gibbonINInterventionEligibilityAssessment.timestampCreated',
                'gibbonINIntervention.name as interventionName',
                'p.gibbonPersonID',
                'p.title',
                'p.surname',
                'p.preferredName',
                'fg.name as formGroup',
                'yg.name as yearGroup'
            ])
            ->innerJoin('gibbonINIntervention', 'gibbonINIntervention.gibbonINInterventionID=gibbonINInterventionEligibilityAssessment.gibbonINInterventionID')
            ->innerJoin('gibbonPerson as p', 'p.gibbonPersonID=gibbonINInterventionEligibilityAssessment.gibbonPersonIDStudent')
            ->leftJoin('gibbonStudentEnrolment as se', 'p.gibbonPersonID=se.gibbonPersonID AND se.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup as fg', 'se.gibbonFormGroupID=fg.gibbonFormGroupID')
            ->leftJoin('gibbonYearGroup as yg', 'se.gibbonYearGroupID=yg.gibbonYearGroupID')
            ->where('p.status="Full"')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'gibbonPersonID' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('p.gibbonPersonID = :gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'gibbonFormGroupID' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('fg.gibbonFormGroupID = :gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },
            'gibbonYearGroupID' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('yg.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINInterventionEligibilityAssessment.status = :status')
                    ->bindValue('status', $status);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }
}
