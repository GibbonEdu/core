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

namespace Gibbon\Domain\Behaviour;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Behaviour Gateway
 *
 * @version v17
 * @since   v17
 */
class BehaviourGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonBehaviour';

    private static $searchableColumns = [];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryBehaviourBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDCreator = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonBehaviour.gibbonBehaviourID',
                'gibbonBehaviour.type',
                'gibbonBehaviour.descriptor',
                'gibbonBehaviour.level',
                'gibbonBehaviour.date',
                'gibbonBehaviour.timestamp',
                'gibbonBehaviour.comment',
                'gibbonBehaviour.followup',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonRollGroup.nameShort AS rollGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
            ])
            ->innerJoin('gibbonPerson AS student', 'gibbonBehaviour.gibbonPersonID=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonBehaviour.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonBehaviour.gibbonSchoolYearID');

        if (!empty($gibbonPersonIDCreator)) {
            $query->where('gibbonBehaviour.gibbonPersonIDCreator = :gibbonPersonIDCreator')
                ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
        }

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonBehaviour.gibbonPersonID = :gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'rollGroup' => function ($query, $gibbonRollGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonRollGroupID = :gibbonRollGroupID')
                    ->bindValue('gibbonRollGroupID', $gibbonRollGroupID);
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
            'type' => function ($query, $type) {
                return $query
                    ->where('gibbonBehaviour.type = :type')
                    ->bindValue('type', $type);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
}
