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

namespace Gibbon\Domain\IndividualNeeds;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Investigations Gateway
 *
 * @version v17
 * @since   v17
 */
class INInvestigationGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINInvestigation';
    private static $primaryKey = 'gibbonINInvestigationID';

    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonSchoolYearID
     * @param int $gibbonPersonIDCreator
     * @return DataSet
     */
    public function queryInvestigations(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDCreator = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigation.*',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonRollGroup.nameShort AS rollGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator'
            ])
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInvestigation.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInvestigation.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID');

        if (!empty($gibbonPersonIDCreator)) {
            $query->where('gibbonINInvestigation.gibbonPersonIDCreator=:gibbonPersonIDCreator OR gibbonRollGroup.gibbonPersonIDTutor=:gibbonPersonIDCreator OR gibbonRollGroup.gibbonPersonIDTutor2=:gibbonPersonIDCreator OR gibbonRollGroup.gibbonPersonIDTutor3=:gibbonPersonIDCreator')
                ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
        }

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonINInvestigation.gibbonPersonIDStudent=:gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'rollGroup' => function ($query, $gibbonRollGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID')
                    ->bindValue('gibbonRollGroupID', $gibbonRollGroupID);
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINInvestigationID
     * @param int $gibbonSchoolYearID
     * @return DataSet
     */
    public function queryInvestigationsByID(QueryCriteria $criteria, $gibbonINInvestigationID, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigation.*',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonRollGroup.nameShort AS rollGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
                'gibbonRollGroup.gibbonPersonIDTutor',
                'gibbonRollGroup.gibbonPersonIDTutor2',
                'gibbonRollGroup.gibbonPersonIDTutor3',
                'gibbonYearGroup.gibbonPersonIDHOY'
            ])
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonINInvestigation.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonINInvestigation.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID')
            ->bindValue('gibbonINInvestigationID', $gibbonINInvestigationID)
            ->where('gibbonINInvestigation.gibbonINInvestigationID=:gibbonINInvestigationID');

        return $this->runQuery($query, $criteria);
    }
}
