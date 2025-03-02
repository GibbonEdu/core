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

namespace Gibbon\Domain\Interventions;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * Referral Gateway
 *
 * @version v29
 * @since   v29
 */
class INReferralGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonINReferral';
    private static $primaryKey = 'gibbonINReferralID';

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReferrals(QueryCriteria $criteria, $gibbonSchoolYearID = null, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonINReferral.gibbonINReferralID', 
                'gibbonINReferral.gibbonPersonIDStudent', 
                'gibbonINReferral.gibbonPersonIDCreator', 
                'gibbonINReferral.name', 
                'gibbonINReferral.description', 
                'gibbonINReferral.status', 
                'gibbonINReferral.eligibilityDecision', 
                'gibbonINReferral.dateCreated', 
                'gibbonINReferral.timestampCreated',
                'student.surname', 
                'student.preferredName',
                'creator.title as creatorTitle', 
                'creator.surname as creatorSurname', 
                'creator.preferredName as creatorPreferredName',
                'gibbonFormGroup.name as formGroup'
            ])
            ->innerJoin('gibbonPerson AS student', 'student.gibbonPersonID=gibbonINReferral.gibbonPersonIDStudent')
            ->innerJoin('gibbonPerson AS creator', 'creator.gibbonPersonID=gibbonINReferral.gibbonPersonIDCreator')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID');

        if (!empty($gibbonSchoolYearID)) {
            $query->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
                  ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonINReferral.gibbonPersonIDCreator = :gibbonPersonID')
                  ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonIDStudent) {
                return $query
                    ->where('gibbonINReferral.gibbonPersonIDStudent = :gibbonPersonIDStudent')
                    ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);
            },
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonINReferral.status = :status')
                    ->bindValue('status', $status);
            },
            'eligibilityDecision' => function ($query, $eligibilityDecision) {
                return $query
                    ->where('gibbonINReferral.eligibilityDecision = :eligibilityDecision')
                    ->bindValue('eligibilityDecision', $eligibilityDecision);
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

    /**
     * Get referrals by student ID
     *
     * @param int $gibbonPersonIDStudent
     * @return array
     */
    public function selectReferralsByStudent($gibbonPersonIDStudent)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonINReferral.gibbonINReferralID', 
                'gibbonINReferral.name', 
                'gibbonINReferral.description', 
                'gibbonINReferral.status', 
                'gibbonINReferral.eligibilityDecision', 
                'gibbonINReferral.dateCreated'
            ])
            ->where('gibbonINReferral.gibbonPersonIDStudent = :gibbonPersonIDStudent')
            ->bindValue('gibbonPersonIDStudent', $gibbonPersonIDStudent)
            ->orderBy(['dateCreated DESC']);

        return $this->runSelect($query);
    }

    /**
     * Get referral by ID
     *
     * @param int $gibbonINReferralID
     * @return array
     */
    public function getByID($gibbonINReferralID)
    {
        $data = ['gibbonINReferralID' => $gibbonINReferralID];
        $sql = "SELECT gibbonINReferral.*, 
                student.gibbonPersonID, student.surname, student.preferredName, 
                creator.title AS creatorTitle, creator.surname AS creatorSurname, creator.preferredName AS creatorPreferredName
                FROM gibbonINReferral
                JOIN gibbonPerson AS student ON (student.gibbonPersonID=gibbonINReferral.gibbonPersonIDStudent)
                JOIN gibbonPerson AS creator ON (creator.gibbonPersonID=gibbonINReferral.gibbonPersonIDCreator)
                WHERE gibbonINReferral.gibbonINReferralID=:gibbonINReferralID";

        return $this->db()->selectOne($sql, $data);
    }
}
