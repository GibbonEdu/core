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

class MentorGroupPersonGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'freeLearningMentorGroupPerson';
    private static $primaryKey = 'freeLearningMentorGroupPersonID';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname'];

    public function selectGroupPeopleByRole($freeLearningMentorGroupID, $role)
    {
        $data = ['freeLearningMentorGroupID' => $freeLearningMentorGroupID, 'role' => $role];
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, username, gibbonRole.category as roleCategory, freeLearningMentorGroupPersonID
                FROM freeLearningMentorGroupPerson
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=freeLearningMentorGroupPerson.gibbonPersonID)
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                WHERE freeLearningMentorGroupID=:freeLearningMentorGroupID
                AND freeLearningMentorGroupPerson.role=:role
                ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    /**
     * Get a list of mentors based on manually assigned groups or groups that match a custom field value of this student.
     *
     * @param string $gibbonPersonID
     * @return object Result
     */
    public function selectMentorsByStudent($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "(SELECT freeLearningMentorGroup.name as groupName, LPAD(mentor.gibbonPersonID, 10, '0') as gibbonPersonID, mentor.title, mentor.surname, mentor.preferredName
                FROM freeLearningMentorGroupPerson AS studentGroupPerson
                JOIN freeLearningMentorGroup ON (freeLearningMentorGroup.freeLearningMentorGroupID=studentGroupPerson.freeLearningMentorGroupID)
                JOIN freeLearningMentorGroupPerson AS mentorGroupPerson ON (mentorGroupPerson.freeLearningMentorGroupID=freeLearningMentorGroup.freeLearningMentorGroupID)
                JOIN gibbonPerson AS mentor ON (mentor.gibbonPersonID=mentorGroupPerson.gibbonPersonID)
                WHERE freeLearningMentorGroup.assignment='Manual'
                AND studentGroupPerson.gibbonPersonID=:gibbonPersonID
                AND studentGroupPerson.role='Student'
                AND mentorGroupPerson.role='Mentor'
                AND mentor.status='Full'
            ) UNION ALL (
                SELECT freeLearningMentorGroup.name as groupName, LPAD(mentor.gibbonPersonID, 10, '0') as gibbonPersonID, mentor.title, mentor.surname, mentor.preferredName
                FROM freeLearningMentorGroup
                JOIN freeLearningMentorGroupPerson AS mentorGroupPerson ON (freeLearningMentorGroup.freeLearningMentorGroupID=mentorGroupPerson.freeLearningMentorGroupID)
                JOIN gibbonPerson AS mentor ON (mentor.gibbonPersonID=mentorGroupPerson.gibbonPersonID)
                JOIN gibbonPerson AS student ON (student.gibbonPersonID=:gibbonPersonID)
                WHERE freeLearningMentorGroup.assignment='Automatic'
                AND mentorGroupPerson.role='Mentor'
                AND mentor.status='Full'
                AND ( (student.fields LIKE CONCAT('%\"', LPAD(freeLearningMentorGroup.gibbonCustomFieldID, 4, '0'), '\":\"', freeLearningMentorGroup.fieldValue, '\"%')) OR (student.fields LIKE CONCAT('%\"', LPAD(freeLearningMentorGroup.gibbonCustomFieldID, 3, '0'), '\":\"', freeLearningMentorGroup.fieldValue, '\"%') ))
            ) ORDER BY groupName, surname, preferredName";

        return $this->db()->select($sql, $data);
    }

}
