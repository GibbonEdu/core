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
 * @version v16
 * @since   v16
 */
class INGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonIN';

    private static $searchableColumns = ['preferredName', 'surname', 'username'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryINBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'gibbonINID', 'gibbonPerson.gibbonPersonID', 'preferredName', 'surname', 'gibbonYearGroup.nameShort AS yearGroup', 'gibbonRollGroup.nameShort AS rollGroup', 'dateStart', 'dateEnd', 'status'
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonIN.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->innerJoin('gibbonINPersonDescriptor', 'gibbonINPersonDescriptor.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'descriptor' => function ($query, $gibbonINDescriptorID) {
                return $query
                    ->where('gibbonINPersonDescriptor.gibbonINDescriptorID = :gibbonINDescriptorID')
                    ->bindValue('gibbonINDescriptorID', $gibbonINDescriptorID);
            },

            'alert' => function ($query, $gibbonAlertLevelID) {
                return $query
                    ->where('gibbonINPersonDescriptor.gibbonAlertLevelID = :gibbonAlertLevelID')
                    ->bindValue('gibbonAlertLevelID', $gibbonAlertLevelID);
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
