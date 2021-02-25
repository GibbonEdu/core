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
class INGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonIN';
    private static $primaryKey = 'gibbonINID';

    private static $searchableColumns = ['preferredName', 'surname', 'username'];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['strategies' => '','targets' => '','notes' => ''];
    
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

    public function queryIndividualNeedsPersonDescriptors(QueryCriteria $criteria)
    {
      $query = $this
        ->newQuery()
        ->from('gibbonINPersonDescriptor')
        ->innerJoin('gibbonAlertLevel','gibbonAlertLevel.gibbonAlertLevelID = gibbonINPersonDescriptor.gibbonAlertLevelID')
        ->cols([
          'gibbonINPersonDescriptor.gibbonINPersonDescriptorID',
          'gibbonINPersonDescriptor.gibbonPersonID',
          'gibbonINPersonDescriptor.gibbonINDescriptorID',
          'gibbonINPersonDescriptor.gibbonAlertLevelID',
          'gibbonAlertLevel.gibbonAlertLevelID'
        ]);

      $criteria->addFilterRules([
        'gibbonPersonID' => function($query,$gibbonPersonID)
        {
          return $query
            ->where('gibbonINPersonDescriptor.gibbonPersonID = :gibbonPersonID')
            ->bindValue('gibbonPersonID',$gibbonPersonID);
        }
      ]);

      return $this->runQuery($query,$criteria);
    }

    public function queryIndividualNeedsDescriptors(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonINDescriptor')
            ->orderBy(['gibbonINDescriptor.sequenceNumber'])
            ->cols([
                'gibbonINDescriptorID', 'name', 'nameShort', 'description', 'sequenceNumber'
            ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryINCountsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonYearGroupID = '')
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from('gibbonStudentEnrolment')
            ->cols(['gibbonYearGroup.name as labelName',
                    'gibbonYearGroup.gibbonYearGroupID as labelID',
                    'COUNT(DISTINCT gibbonStudentEnrolment.gibbonPersonID) as studentCount',
                    'COUNT(DISTINCT gibbonINPersonDescriptor.gibbonPersonID) as inCount',
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonINPersonDescriptor', 'gibbonINPersonDescriptor.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where("gibbonPerson.status='Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)')
            ->bindValue('today', date('Y-m-d'))
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!empty($gibbonYearGroupID)) {
            // Grouped by Roll Groups within a Year Group
            $query->cols([
                'gibbonRollGroup.name as labelName',
                'gibbonRollGroup.gibbonRollGroupID as labelID',
                'COUNT(DISTINCT gibbonStudentEnrolment.gibbonPersonID) as studentCount',
                'COUNT(DISTINCT gibbonINPersonDescriptor.gibbonPersonID) as inCount',
            ])
            ->where('gibbonStudentEnrolment.gibbonYearGroupID = :gibbonYearGroupID')
            ->bindValue('gibbonYearGroupID', $gibbonYearGroupID)
            ->groupBy(['gibbonRollGroup.gibbonRollGroupID']);
        } else {
            // Grouped by Year Group
            $query->cols([
                'gibbonYearGroup.name as labelName',
                'gibbonYearGroup.gibbonYearGroupID as labelID',
                'COUNT(DISTINCT gibbonStudentEnrolment.gibbonPersonID) as studentCount',
                'COUNT(DISTINCT gibbonINPersonDescriptor.gibbonPersonID) as inCount',
            ])
            ->groupBy(['gibbonYearGroup.gibbonYearGroupID']);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryAlertLevels(QueryCriteria $criteria)
    {
      $query = $this
        ->newQuery()
        ->distinct()
        ->from('gibbonAlertLevel')
        ->orderBy(['sequenceNumber'])
        ->cols([
          'gibbonAlertLevel.gibbonAlertLevelID',
          'gibbonAlertLevel.name',
          'gibbonAlertLevel.nameShort',
          'gibbonAlertLevel.description',
          'gibbonAlertLevel.color',
          'gibbonAlertLevel.sequenceNumber'
        ]);

      return $this->runQuery($query,$criteria);
    }
}
