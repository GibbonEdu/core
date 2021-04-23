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

namespace Gibbon\Domain\CrowdAssessment;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * CrowdAssessDiscuss
 *
 * @version v22
 * @since   v22
 */
class CrowdAssessDiscussGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCrowdAssessDiscuss';
    private static $primaryKey = 'gibbonCrowdAssessDiscussID';
    private static $searchableColumns = [];

    public function selectDiscussionByHomeworkID($gibbonPlannerEntryHomeworkID)
    {
        $data = ['gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID];
        $sql = 'SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName, category FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID';

        return $this->db()->select($sql, $data);
    }
}
