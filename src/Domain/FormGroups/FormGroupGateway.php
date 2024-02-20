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

namespace Gibbon\Domain\FormGroups;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * FormGroup Gateway
 *
 * @version v16
 * @since   v16
 */
class FormGroupGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonFormGroup';
    private static $primaryKey = 'gibbonFormGroupID';
    private static $searchableColumns = [];

    public function queryFormGroups(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSchoolYear.sequenceNumber',
                'gibbonSchoolYear.gibbonSchoolYearID',
                'gibbonFormGroup.gibbonFormGroupID',
                'gibbonSchoolYear.name as yearName',
                'gibbonFormGroup.name',
                'gibbonFormGroup.nameShort',
                'gibbonFormGroup.gibbonPersonIDTutor',
                'gibbonFormGroup.gibbonPersonIDTutor2',
                'gibbonFormGroup.gibbonPersonIDTutor3',
                'gibbonSpace.name AS space',
                'gibbonFormGroup.website',
                "LENGTH(gibbonFormGroup.name) as sortOrder"

            ])
            ->innerJoin('gibbonSchoolYear', 'gibbonFormGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('gibbonSpace', 'gibbonFormGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID')
            ->where('gibbonSchoolYear.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectFormGroupsBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name, gibbonFormGroup.nameShort, gibbonFormGroup.gibbonSpaceID, gibbonSpace.name AS space, gibbonFormGroup.website, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, COUNT(DISTINCT students.gibbonPersonID) as students, (SELECT MAX(sequenceNumber) FROM gibbonYearGroup JOIN gibbonStudentEnrolment ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID) WHERE gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) as sequenceNumber
                FROM gibbonFormGroup
                LEFT JOIN (
                    SELECT gibbonStudentEnrolment.gibbonPersonID, gibbonStudentEnrolment.gibbonFormGroupID FROM gibbonStudentEnrolment
                    JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)
                    ORDER BY gibbonStudentEnrolment.gibbonYearGroupID
                ) AS students ON (students.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                LEFT JOIN gibbonSpace ON (gibbonFormGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
                WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonFormGroup.gibbonFormGroupID
                ORDER BY LENGTH(gibbonFormGroup.name), gibbonFormGroup.name";

        return $this->db()->select($sql, $data);
    }

    public function selectFormGroupsBySchoolYearMyChildren($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name, gibbonFormGroup.nameShort, gibbonSpace.name AS space, gibbonFormGroup.website, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, COUNT(DISTINCT students.gibbonPersonID) as students, (SELECT MAX(sequenceNumber) FROM gibbonYearGroup JOIN gibbonStudentEnrolment ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID) WHERE gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) as sequenceNumber
                FROM gibbonFormGroup
                JOIN (
                    SELECT gibbonStudentEnrolment.gibbonPersonID, gibbonStudentEnrolment.gibbonFormGroupID FROM gibbonStudentEnrolment
                    JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    WHERE status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)
                    ORDER BY gibbonStudentEnrolment.gibbonYearGroupID
                ) AS students ON (students.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=students.gibbonPersonID)
                JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                LEFT JOIN gibbonSpace ON (gibbonFormGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID)
                WHERE gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                GROUP BY gibbonFormGroup.gibbonFormGroupID
                ORDER BY LENGTH(gibbonFormGroup.name), gibbonFormGroup.name";

        return $this->db()->select($sql, $data);
    }

    public function selectTutorsByFormGroup($gibbonFormGroupID)
    {
        $data = array('gibbonFormGroupID' => $gibbonFormGroupID);
        $sql = "SELECT gibbonPersonID, title, surname, preferredName, email, status
                FROM gibbonFormGroup
                LEFT JOIN gibbonPerson ON ((gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor OR gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor2 OR gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor3) AND gibbonPerson.status='Full')
                WHERE gibbonFormGroup.gibbonFormGroupID=:gibbonFormGroupID
                ORDER BY gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor DESC, gibbonPersonID=gibbonFormGroup.gibbonPersonIDTutor2 DESC";

        return $this->db()->select($sql, $data);
    }

    public function selectFormGroupsByTutor($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT gibbonFormGroup.*, gibbonSpace.name as spaceName
                FROM gibbonFormGroup
                LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonFormGroup.gibbonSpaceID)
                WHERE (gibbonFormGroup.gibbonPersonIDTutor = :gibbonPersonID
                    OR gibbonFormGroup.gibbonPersonIDTutor2 = :gibbonPersonID
                    OR gibbonFormGroup.gibbonPersonIDTutor3 = :gibbonPersonID)
                AND gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current' LIMIT 1)
                ORDER BY gibbonFormGroup.nameShort";

        return $this->db()->select($sql, $data);
    }

    public function selectFormGroups()
    {
        $sql = "SELECT gibbonFormGroupID as value, name, gibbonSchoolYearID FROM gibbonFormGroup ORDER BY gibbonSchoolYearID, name";

        return $this->db()->select($sql);
    }

    public function getFormGroupByID($gibbonFormGroupID)
    {
        $data = array('gibbonFormGroupID' => $gibbonFormGroupID);
        $sql = "SELECT *
                FROM gibbonFormGroup
                WHERE gibbonFormGroupID=:gibbonFormGroupID";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Take a form group, and return the next one, or false if none.
     *
     * @version v17
     * @since   v17
     *
     * @param int $gibbonFormGroupID
     *
     * @return int|false
     */
    public function getNextFormGroupID($gibbonFormGroupID)
    {
        $sql = 'SELECT gibbonFormGroupIDNext FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
        return $this->db()->selectOne($sql, [
            'gibbonFormGroupID' => $gibbonFormGroupID,
        ]);
    }
}
