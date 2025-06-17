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

namespace Gibbon\Domain\Alerts;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * AlertGateway
 *
 * @version v30
 * @since   v30
 */

class AlertGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAlert';
    private static $primaryKey = 'gibbonAlertID';
    private static $searchableColumns = [];

    public function queryAlertsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonIDCreator = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonAlert.gibbonAlertID',
                'gibbonAlert.gibbonAlertLevelID',
                'gibbonAlert.context',
                'gibbonAlert.type',
                'gibbonAlert.status',
                'gibbonAlert.level',
                'gibbonAlert.dateStart',
                'gibbonAlert.dateEnd',
                'gibbonAlert.comment',
                'gibbonAlert.gibbonPersonIDCreator',
                'gibbonAlert.timestampCreated',
                'gibbonStudentEnrolment.gibbonFormGroupID',
                'gibbonStudentEnrolment.gibbonYearGroupID',
                'student.gibbonPersonID',
                'student.surname',
                'student.preferredName',
                'gibbonFormGroup.nameShort AS formGroup',
                'creator.title AS titleCreator',
                'creator.surname AS surnameCreator',
                'creator.preferredName AS preferredNameCreator',
            ])
            ->innerJoin('gibbonPerson AS student', 'gibbonAlert.gibbonPersonID=student.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'student.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS creator', 'gibbonAlert.gibbonPersonIDCreator=creator.gibbonPersonID')
            ->where('gibbonAlert.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=gibbonAlert.gibbonSchoolYearID');

        if (!empty($gibbonPersonIDCreator)) {
            $query->where('gibbonAlert.gibbonPersonIDCreator = :gibbonPersonIDCreator')
                ->bindValue('gibbonPersonIDCreator', $gibbonPersonIDCreator);
        }

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonAlert.gibbonPersonID = :gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },
            'formGroup' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonFormGroupID = :gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },
            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function getAlertEditAccess($gibbonAlertID, $gibbonPersonID)
    {
        $data = ['gibbonAlertID' => $gibbonAlertID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT (CASE WHEN gibbonPersonIDCreator = :gibbonPersonID THEN TRUE ELSE FALSE END) AS canEdit FROM gibbonAlert WHERE gibbonAlertID = :gibbonAlertID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getHighestWellbeingAlert($gibbonPersonID, $gibbonSchoolYearID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'status' => "Approved", 'type' => "Wellbeing"];
        $sql = 'SELECT * FROM gibbonAlert JOIN gibbonAlertLevel ON (gibbonAlert.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID) WHERE (gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status=:status AND type=:type) ORDER BY gibbonAlertLevel.sequenceNumber DESC';

        return $this->db()->selectOne($sql, $data);
    }
}
