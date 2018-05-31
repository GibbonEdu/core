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

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class FirstAidGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFirstAid';

    private static $searchableColumns = [''];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFirstAidBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFirstAidID', 'gibbonFirstAid.date', 'gibbonFirstAid.timeIn', 'gibbonFirstAid.timeOut', 'gibbonFirstAid.description', 'gibbonFirstAid.actionTaken', 'gibbonFirstAid.followUp', 'gibbonFirstAid.date', 'patient.surname AS surnamePatient', 'patient.preferredName AS preferredNamePatient', 'gibbonFirstAid.gibbonPersonIDPatient', 'gibbonRollGroup.name as rollGroup', 'firstAider.title', 'firstAider.surname AS surnameFirstAider', 'firstAider.preferredName AS preferredNameFirstAider'
            ])
            ->innerJoin('gibbonPerson AS patient', 'gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonPerson AS firstAider', 'gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonFirstAid.gibbonPersonIDPatient = :gibbonPersonID')
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
        ]);

        return $this->runQuery($query, $criteria);
    }
}
