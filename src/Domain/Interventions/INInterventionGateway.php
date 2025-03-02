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
 * Intervention Gateway
 *
 * @version v29
 * @since   v29
 */
class INInterventionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINIntervention';
    private static $primaryKey = 'gibbonINInterventionID';
    private static $searchableColumns = ['gibbonINIntervention.name', 'student.surname', 'student.preferredName'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryInterventions(QueryCriteria $criteria, $gibbonSchoolYearID = null, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINIntervention.gibbonINInterventionID',
                'gibbonINIntervention.name',
                'gibbonINIntervention.description',
                'gibbonINIntervention.status',
                'gibbonINIntervention.formTutorDecision',
                'gibbonINIntervention.outcomeDecision',
                'gibbonINIntervention.timestampCreated',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'formGroup.gibbonFormGroupID',
                'formGroup.name AS formGroupName',
                'yearGroup.gibbonYearGroupID',
                'yearGroup.name AS yearGroupName',
                'creator.title AS creatorTitle',
                'creator.surname AS creatorSurname',
                'creator.preferredName AS creatorPreferredName',
                'formTutor.title AS formTutorTitle',
                'formTutor.surname AS formTutorSurname',
                'formTutor.preferredName AS formTutorPreferredName'
            ])
            ->innerJoin('gibbonPerson AS student', 'student.gibbonPersonID=gibbonINIntervention.gibbonPersonIDStudent')
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINIntervention.gibbonPersonIDCreator')
            ->leftJoin('gibbonPerson AS formTutor', 'formTutor.gibbonPersonID=gibbonINIntervention.gibbonPersonIDFormTutor')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup AS formGroup', 'formGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('gibbonYearGroup AS yearGroup', 'yearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if ($gibbonPersonID) {
            $query->where('gibbonINIntervention.gibbonPersonIDCreator = :gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'gibbonPersonID' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('student.gibbonPersonID = :studentPersonID')
                    ->bindValue('studentPersonID', $gibbonPersonID);
            },
            'gibbonFormGroupID' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('formGroup.gibbonFormGroupID = :gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },
            'gibbonYearGroupID' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('yearGroup.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINIntervention.status = :status')
                    ->bindValue('status', $status);
            }
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get intervention by ID
     * 
     * @param int $gibbonINInterventionID
     * @return array
     */
    public function getInterventionByID($gibbonINInterventionID)
    {
        $data = ['gibbonINInterventionID' => $gibbonINInterventionID];
        $sql = "SELECT gibbonINIntervention.*, 
                student.gibbonPersonID, student.surname, student.preferredName, 
                creator.title AS creatorTitle, creator.surname AS creatorSurname, creator.preferredName AS creatorPreferredName,
                formTutor.title AS formTutorTitle, formTutor.surname AS formTutorSurname, formTutor.preferredName AS formTutorPreferredName
                FROM gibbonINIntervention
                JOIN gibbonPerson AS student ON (student.gibbonPersonID=gibbonINIntervention.gibbonPersonIDStudent)
                JOIN gibbonPerson AS creator ON (creator.gibbonPersonID=gibbonINIntervention.gibbonPersonIDCreator)
                LEFT JOIN gibbonPerson AS formTutor ON (formTutor.gibbonPersonID=gibbonINIntervention.gibbonPersonIDFormTutor)
                WHERE gibbonINIntervention.gibbonINInterventionID=:gibbonINInterventionID";

        return $this->db()->selectOne($sql, $data);
    }
}
