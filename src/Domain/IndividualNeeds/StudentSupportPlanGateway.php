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

namespace Gibbon\Domain\IndividualNeeds;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * Student Support Plan Gateway
 *
 * @version v31
 * @since   v31
 */
class StudentSupportPlanGateway extends QueryableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonStudentSupportPlan';
    private static $primaryKey = 'gibbonStudentSupportPlanID';
    private static $searchableColumns = ['name', 'description'];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['name' => '', 'description' => null, 'filePath' => ''];

    /**
     * @param QueryCriteria $criteria
     * @param string        $gibbonPersonID
     * @return \Gibbon\Domain\DataSet
     */
    public function queryPlansByStudent(QueryCriteria $criteria, string $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStudentSupportPlan.gibbonStudentSupportPlanID',
                'gibbonStudentSupportPlan.gibbonPersonID',
                'gibbonStudentSupportPlan.gibbonSchoolYearID',
                'gibbonStudentSupportPlan.active',
                'gibbonStudentSupportPlan.type',
                'gibbonStudentSupportPlan.filePath',
                'gibbonStudentSupportPlan.name',
                'gibbonStudentSupportPlan.description',
                'gibbonStudentSupportPlan.viewableStaff',
                'gibbonStudentSupportPlan.viewableParents',
                'gibbonStudentSupportPlan.timestampCreated',
                'gibbonStudentSupportPlan.timestampModified',
                'gibbonStudentSupportPlan.gibbonPersonIDCreated',
                'gibbonStudentSupportPlan.gibbonPersonIDModified',
                'gibbonSchoolYear.name AS schoolYear',
                'gibbonSchoolYear.sequenceNumber',
            ])
            ->innerJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonStudentSupportPlan.gibbonSchoolYearID')
            ->where('gibbonStudentSupportPlan.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->orderBy(['gibbonSchoolYear.sequenceNumber DESC', 'gibbonStudentSupportPlan.timestampCreated ASC']);

        $criteria->addFilterRules([
            'viewableStaff' => function ($query, $value) {
                return $query->where("gibbonStudentSupportPlan.viewableStaff='Y'");
            },
            'viewableParents' => function ($query, $value) {
                return $query->where("gibbonStudentSupportPlan.viewableParents='Y'");
            },
            'active' => function ($query, $value) {
                return $query->where("gibbonStudentSupportPlan.active='Y'");
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
}
