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
 * Investigations Gateway
 *
 * @version v19
 * @since   v19
 */
class INInvestigationContributionGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonINInvestigationContribution';
    private static $primaryKey = 'gibbonINInvestigationContributionID';

    private static $searchableColumns = [];

    private static $scrubbableKey = ['gibbonPersonIDStudent', 'gibbonINInvestigation', 'gibbonINInvestigationID'];
    private static $scrubbableColumns = ['cognition'=> null,'memory'=> null,'selfManagement'=> null,'attention'=> null,'socialInteraction'=> null,'communication'=> null,'comment'=> null];

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonINInvestigationID
     * @return DataSet
     */
    public function queryContributionsByInvestigation(QueryCriteria $criteria, $gibbonINInvestigationID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigationContribution.*',
                'surname',
                'preferredName',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class'
            ])
            ->innerJoin('gibbonPerson','gibbonINInvestigationContribution.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonINInvestigationContribution.gibbonCourseClassPersonID=gibbonCourseClassPerson.gibbonCourseClassPersonID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where('gibbonINInvestigationID=:gibbonINInvestigationID')
            ->bindValue('gibbonINInvestigationID', $gibbonINInvestigationID);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @param int $gibbonPersonID
     * @param string $status
     * @return DataSet
     */
    public function queryContributionsByPerson(QueryCriteria $criteria, $gibbonPersonID, $status = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigationContribution.*',
                'gibbonINInvestigation.gibbonPersonIDStudent',
                'student.surname',
                'student.preferredName',
                'gibbonRollGroup.nameShort AS rollGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
                'date',
                'type',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class'
            ])
            ->innerJoin('gibbonINInvestigation', 'gibbonINInvestigationContribution.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonPerson AS creator', 'gibbonINInvestigation.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonINInvestigationContribution.gibbonCourseClassPersonID=gibbonCourseClassPerson.gibbonCourseClassPersonID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where('gibbonINInvestigationContribution.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        if (!empty($status)) {
            $query->where('gibbonINInvestigationContribution.status=:status')
            ->bindValue('status', $status);
        }

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param int $gibbonINInvestigationContributionID
     * @return array
     */
    public function getContributionByID($gibbonINInvestigationContributionID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINInvestigationContribution.*',
                'student.surname',
                'student.preferredName',
                'gibbonRollGroup.nameShort AS rollGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
                'date',
                'type',
                'gibbonCourse.nameShort AS course',
                'gibbonCourseClass.nameShort AS class'
            ])
            ->innerJoin('gibbonINInvestigation', 'gibbonINInvestigationContribution.gibbonINInvestigationID=gibbonINInvestigation.gibbonINInvestigationID')
            ->innerJoin('gibbonPerson AS student', 'gibbonINInvestigation.gibbonPersonIDStudent=student.gibbonPersonID')
            ->innerJoin('gibbonPerson AS creator', 'gibbonINInvestigation.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonINInvestigation.gibbonSchoolYearID')
            ->innerJoin('gibbonRollGroup', 'gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID')
            ->leftJoin('gibbonCourseClassPerson', 'gibbonINInvestigationContribution.gibbonCourseClassPersonID=gibbonCourseClassPerson.gibbonCourseClassPersonID')
            ->leftJoin('gibbonCourseClass', 'gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID')
            ->leftJoin('gibbonCourse', 'gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID')
            ->where('gibbonINInvestigationContribution.gibbonINInvestigationContributionID=:gibbonINInvestigationContributionID')
            ->bindValue('gibbonINInvestigationContributionID', $gibbonINInvestigationContributionID);

        return $this->runSelect($query)->fetch();
    }

    /**
     * @param string $gibbonINInvestigationID
     * @return array
     */
    public function getInvestigationCompletion($gibbonINInvestigationID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                "COUNT(DISTINCT CASE WHEN status = 'Complete' THEN gibbonINInvestigationContributionID END) AS complete",
                "COUNT(DISTINCT gibbonINInvestigationContributionID) AS total"
            ])
            ->where('gibbonINInvestigationID=:gibbonINInvestigationID')
            ->bindValue('gibbonINInvestigationID', $gibbonINInvestigationID);

        return $this->runSelect($query)->fetch();
    }

    /**
     * @param QueryCriteria $criteria
     * @return array
     */
    public function queryInvestigationStatistics(QueryCriteria $criteria, $gibbonINInvestigationID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                '*'
            ])
            ->where('gibbonINInvestigationID=:gibbonINInvestigationID')
            ->bindValue('gibbonINInvestigationID', $gibbonINInvestigationID);

        $results = $this->runQuery($query, $criteria);

        //Turn data into statistical table
        $strands = getInvestigationCriteriaStrands(true);
        $count = 0 ;
        for ($i = 0; $i < count($strands); $i++) {
            $strands[$i]['data'] = array();
            $criteria = getInvestigationCriteriaArray($strands[$i]['nameHuman']);
            foreach ($criteria as $criterion) {
                $strands[$i]['data'][$criterion] = 0;
                foreach ($results as $result) {
                    $resultData = @unserialize($result[$strands[$i]['name']]);
                    if (is_array($resultData)) {
                        foreach ($resultData AS $resultDatum) {
                            if ($resultDatum == $criterion) {
                                $strands[$i]['data'][$criterion] ++;
                            }
                        }
                    }
                    else {
                        $resultData = $result[$strands[$i]['name']];
                        if ($resultData == $criterion) {
                            $strands[$i]['data'][$criterion] ++;
                        }
                    }
                }
            }
        }

        return $strands;
    }
}
