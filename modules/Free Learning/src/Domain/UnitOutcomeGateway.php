<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Module\FreeLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class UnitOutcomeGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningUnitOutcome';
    private static $primaryKey = 'freeLearningUnitOutcomeID';

    public function queryOutcomesByStudent($criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols(['gibbonOutcome.gibbonOutcomeID', 'gibbonOutcome.scope', 'gibbonOutcome.category', 'gibbonOutcome.name', 'gibbonDepartment.name AS department', 'count(DISTINCT freeLearningUnit.name) AS status', 'GROUP_CONCAT(DISTINCT freeLearningUnit.name ORDER BY freeLearningUnit.name SEPARATOR \', \') AS units'])
            ->from('gibbonOutcome')
            ->leftJoin('gibbonDepartment','gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID')
            ->innerJoin('gibbonYearGroup', "gibbonOutcome.gibbonYearGroupIDList LIKE CONCAT('%', gibbonYearGroup.gibbonYearGroupID, '%')")
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonPerson', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('freeLearningUnitOutcome', 'freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID')
            ->leftJoin('freeLearningUnit', 'freeLearningUnitOutcome.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->leftJoin('freeLearningUnitStudent', "freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND freeLearningUnitStudent.gibbonPersonIDStudent=:gibbonPersonID AND freeLearningUnitStudent.status IN ('Complete - Approved','Exempt')")
            ->where('gibbonPerson.status=\'Full\'')
            ->where("gibbonOutcome.active='Y'")
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['gibbonOutcome.gibbonOutcomeID']);

        return $this->runQuery($query, $criteria);
    }

    public function queryRecommendedUnitsByStudent($criteria, $gibbonPersonID, $gibbonSchoolYearID, $outcomesNotMet)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols(['freeLearningUnitStudent.status', "GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList", 'freeLearningUnit.freeLearningUnitID', 'freeLearningUnit.*', 'gibbonYearGroup.sequenceNumber AS sn1', 'gibbonYearGroup2.sequenceNumber AS sn2'])
            ->from('freeLearningUnit')
            ->leftJoin('freeLearningUnitPrerequisite','freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID')
            ->innerJoin('freeLearningUnitOutcome', "freeLearningUnitOutcome.freeLearningUnitID=freeLearningUnit.freeLearningUnitID")
            ->leftJoin('freeLearningUnitStudent','freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->leftJoin('gibbonYearGroup','freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonStudentEnrolment', "gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID")
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->innerJoin('gibbonYearGroup AS gibbonYearGroup2', "gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID")
            ->where("status IS NULL")
            ->where("active='Y'")
            ->where('gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber')
            ->groupBy(['freeLearningUnit.freeLearningUnitID'])
            ->orderBy(['RAND()'])
            ->limit(['0', '3']);

            if (count($outcomesNotMet) > 0) {
                $outcomesNotMet = "'".implode("','", $outcomesNotMet)."'";
                $query->where("NOT freeLearningUnitOutcome.gibbonOutcomeID IN (:outcomesNotMet)")
                    ->bindValue('outcomesNotMet', $outcomesNotMet);
            }

        return $this->runQuery($query, $criteria);
    }

    public function selectOutcomesByUnit($freeLearningUnitID)
    {
        $data = ['freeLearningUnitID' => $freeLearningUnitID];
        $sql = "SELECT freeLearningUnitOutcome.*, scope, name, nameShort, category, gibbonYearGroupIDList FROM freeLearningUnitOutcome JOIN gibbonOutcome ON (freeLearningUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y' ORDER BY sequenceNumber";

        return $this->db()->select($sql, $data);
    }
}
