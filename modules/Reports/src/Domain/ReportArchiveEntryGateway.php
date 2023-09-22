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

namespace Gibbon\Module\Reports\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ReportArchiveEntryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportArchiveEntry';
    private static $primaryKey = 'gibbonReportArchiveEntryID';
    private static $searchableColumns = ['gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.username'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryArchiveByReport(QueryCriteria $criteria, $gibbonReportID, $gibbonYearGroupID = '', $gibbonFormGroupID = '', $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.gibbonReportArchiveEntryID', 'gibbonReportArchiveEntry.gibbonReportID', 'gibbonReportArchiveEntry.gibbonYearGroupID', 'gibbonReportArchiveEntry.gibbonFormGroupID', 'gibbonReportArchiveEntry.filePath', 'gibbonReportArchiveEntry.status', 'gibbonReportArchiveEntry.timestampModified', 'gibbonReportArchiveEntry.timestampSent', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonReportArchiveEntry.timestampAccessed', 'parent.title as parentTitle', 'parent.preferredName as parentPreferredName', 'parent.surname as parentSurname'])
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonReportArchiveEntry.gibbonPersonID')
            ->leftJoin('gibbonPerson as parent', 'gibbonReportArchiveEntry.gibbonPersonIDAccessed=parent.gibbonPersonID')
            ->leftJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->where("gibbonReportArchiveEntry.type='Single'")
            ->where('(gibbonReportArchiveEntry.gibbonReportID=:gibbonReportID OR gibbonReportArchiveEntry.reportIdentifier=:gibbonReportID)')
            ->bindValue('gibbonReportID', $gibbonReportID);

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        if (!empty($gibbonYearGroupID)) {
            $query->where('gibbonReportArchiveEntry.gibbonYearGroupID=:gibbonYearGroupID')
                  ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
        }
        if (!empty($gibbonFormGroupID) && $gibbonFormGroupID != 'All') {
            $query->where('gibbonReportArchiveEntry.gibbonFormGroupID=:gibbonFormGroupID')
                  ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryArchiveByReportIdentifier(QueryCriteria $criteria, $gibbonSchoolYearID, $reportIdentifier, $gibbonYearGroupID = '', $gibbonFormGroupID = '', $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.gibbonReportArchiveEntryID', 'gibbonReportArchiveEntry.gibbonReportID', 'gibbonReportArchiveEntry.reportIdentifier', 'gibbonReportArchiveEntry.gibbonYearGroupID', 'gibbonReportArchiveEntry.gibbonFormGroupID', 'gibbonReportArchiveEntry.filePath', 'gibbonYearGroup.sequenceNumber'])
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportArchiveEntry.gibbonFormGroupID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportArchiveEntry.gibbonYearGroupID')
            ->where('gibbonReportArchiveEntry.reportIdentifier=:reportIdentifier')
            ->bindValue('reportIdentifier', $reportIdentifier)
            ->where('gibbonReportArchiveEntry.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        if (!empty($gibbonFormGroupID)) {
            $query->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonReportArchiveEntry.timestampAccessed', 'parent.title as parentTitle', 'parent.preferredName as parentPreferredName', 'parent.surname as parentSurname'])
                  ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonReportArchiveEntry.gibbonPersonID')
                  ->leftJoin('gibbonPerson as parent', 'gibbonReportArchiveEntry.gibbonPersonIDAccessed=parent.gibbonPersonID')
                  ->where("gibbonReportArchiveEntry.type='Single'");

            if ($gibbonFormGroupID != 'All') {
                $query->where('gibbonReportArchiveEntry.gibbonFormGroupID=:gibbonFormGroupID')
                  ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            }
        } elseif (!empty($gibbonYearGroupID)) {
            $query->cols(['gibbonFormGroup.name AS name', 'COUNT(DISTINCT gibbonReportArchiveEntry.gibbonPersonID) as count', "COUNT(DISTINCT CASE WHEN gibbonReportArchiveEntry.gibbonPersonIDAccessed IS NOT NULL THEN gibbonReportArchiveEntry.gibbonReportArchiveEntryID END) as readCount"])
                  ->where("gibbonReportArchiveEntry.type='Single'")
                  ->where('gibbonReportArchiveEntry.gibbonYearGroupID=:gibbonYearGroupID')
                  ->bindValue('gibbonYearGroupID', $gibbonYearGroupID)
                  ->groupBy(['gibbonReportArchiveEntry.gibbonFormGroupID']);
        } else {
            $query->cols(['gibbonFormGroup.name AS name', 'COUNT(DISTINCT gibbonReportArchiveEntry.gibbonReportArchiveEntryID) AS count'])
                  ->groupBy(['gibbonReportArchiveEntry.gibbonYearGroupID', 'gibbonReportArchiveEntry.gibbonFormGroupID']);
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryArchiveReportsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.gibbonReportArchiveEntryID', 'gibbonReportArchiveEntry.reportIdentifier', 'MAX(gibbonReportArchiveEntry.timestampModified) as timestampModified', 'gibbonReport.gibbonReportID', 'gibbonReport.name', 'gibbonReportingCycle.sequenceNumber as sequenceNumber', "COUNT(DISTINCT gibbonReportArchiveEntry.gibbonReportArchiveEntryID) AS totalCount", "COUNT(DISTINCT CASE WHEN gibbonReportArchiveEntry.gibbonPersonIDAccessed IS NOT NULL THEN gibbonReportArchiveEntry.gibbonReportArchiveEntryID END) as readCount"])
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->leftJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->leftJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReport.gibbonReportingCycleID')
            ->where('gibbonReportArchiveEntry.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['gibbonReportArchiveEntry.reportIdentifier']);

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonReport.active = :active')
                    ->bindValue('active', $active);
            },
            'reportID' => function ($query, $reportID) {
                return $query
                    ->where('gibbonReport.gibbonReportID > 0');
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryArchiveBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonYearGroupID = '', $gibbonFormGroupID = '', $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from('gibbonStudentEnrolment')
            ->cols(['gibbonReportArchiveEntry.gibbonReportArchiveEntryID', 'gibbonReportArchiveEntry.gibbonReportID', 'gibbonReportArchiveEntry.gibbonYearGroupID', 'gibbonReportArchiveEntry.gibbonFormGroupID', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonPerson.status', 'gibbonPerson.dateStart', 'gibbonPerson.dateEnd', "'Student' as roleCategory", "MAX(gibbonStudentEnrolment.gibbonStudentEnrolmentID) as gibbonStudentEnrolmentID", 'MAX(gibbonYearGroup.nameShort) as yearGroup', 'MAX(gibbonFormGroup.nameShort) as formGroup', "COUNT(DISTINCT gibbonReportArchiveEntry.gibbonReportArchiveEntryID) as count"])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonReportArchiveEntry', 'gibbonReportArchiveEntry.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->where("gibbonReportArchiveEntry.type='Single'")
            ->groupBy(['gibbonPerson.gibbonPersonID', 'gibbonReportArchiveEntry.gibbonPersonID']);

        if ($criteria->hasFilter('all')) {
            $query->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
                    ->where("gibbonRole.category='Student'");
        } else {
            $query->where('gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
                  ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
                  ->where("gibbonPerson.status <> 'Left'");
        }

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        if (!empty($gibbonYearGroupID)) {
            $query->where('gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID')
                  ->where('gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
                  ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
        }
        if (!empty($gibbonFormGroupID)) {
            $query->where('gibbonStudentEnrolment.gibbonFormGroupID=:gibbonFormGroupID')
                  ->where('gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
                  ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
        }

        $criteria->addFilterRules([
            // 'active' => function ($query, $active) {
            //     return $query
            //         ->where('gibbonReport.active = :active')
            //         ->bindValue('active', $active);
            // },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryArchiveByStudent(QueryCriteria $criteria, $gibbonPersonID, $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonSchoolYear.name as schoolYear', 'gibbonSchoolYear.sequenceNumber', 'gibbonReportArchiveEntry.gibbonReportArchiveEntryID', 'gibbonReportArchiveEntry.status', 'gibbonReportArchiveEntry.timestampModified', 'gibbonReportArchiveEntry.reportIdentifier', 'gibbonReportArchiveEntry.gibbonReportID', 'gibbonReportArchiveEntry.gibbonYearGroupID', 'gibbonReportArchiveEntry.gibbonFormGroupID', 'gibbonPerson.gibbonPersonID', 'gibbonYearGroup.nameShort as yearGroup', 'gibbonFormGroup.nameShort as formGroup', 'gibbonReport.name as reportName', 'gibbonReportArchiveEntry.timestampAccessed', 'parent.title as parentTitle', 'parent.preferredName as parentPreferredName', 'parent.surname as parentSurname'])
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->innerJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonReportArchiveEntry.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonReportArchiveEntry.gibbonPersonID')
            ->leftJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportArchiveEntry.gibbonYearGroupID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportArchiveEntry.gibbonFormGroupID')
            ->leftJoin('gibbonPerson as parent', 'gibbonReportArchiveEntry.gibbonPersonIDAccessed=parent.gibbonPersonID')
            ->where("gibbonReportArchiveEntry.type='Single'")
            ->where('gibbonReportArchiveEntry.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        return $this->runQuery($query, $criteria);
    }

    public function selectParentArchiveAccessByReportingCycle($gibbonReportingCycleID)
    {
        $gibbonReportingCycleIDList = is_array($gibbonReportingCycleID)? implode(',', $gibbonReportingCycleID) : $gibbonReportingCycleID;

        $query = $this
            ->newSelect()
            ->cols(['parent.gibbonPersonID', 'parent.surname', 'parent.preferredName', 'parent.email'])
            ->from($this->getTableName())
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->innerJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->innerJoin('gibbonPerson as student', 'student.gibbonPersonID=gibbonReportArchiveEntry.gibbonPersonID')
            ->innerJoin('gibbonFamilyChild', 'gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID')
            ->innerJoin('gibbonFamilyAdult', 'gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID AND gibbonFamilyAdult.contactPriority=1')
            ->innerJoin('gibbonPerson as parent', 'parent.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID')
            ->where('FIND_IN_SET(gibbonReport.gibbonReportingCycleID, :gibbonReportingCycleIDList)')
            ->bindValue('gibbonReportingCycleIDList', $gibbonReportingCycleIDList)
            ->where("gibbonReportArchiveEntry.gibbonSchoolYearID=gibbonReport.gibbonSchoolYearID")
            ->where("gibbonReportArchiveEntry.type='Single'")
            ->where("gibbonReportArchiveEntry.status='Final'")
            ->where("gibbonReportArchive.viewableParents='Y'")
            ->where("gibbonFamilyAdult.childDataAccess='Y'")
            ->where("student.status='Full'")
            ->where("parent.status='Full'")
            ->where("parent.email<>''")
            ->where("parent.receiveNotificationEmails='Y'")
            ->where("(gibbonReport.accessDate IS NULL OR gibbonReport.accessDate <= :currentDateTime)")
            ->bindValue('currentDateTime', date('Y-m-d H:i:s'));

        return $this->runSelect($query);
    }

    public function getRecentArchiveEntryByReport($gibbonReportID, $type, $contextID, $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.*'])
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->leftJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->where('gibbonReportArchiveEntry.gibbonReportID=:gibbonReportID')
            ->bindValue('gibbonReportID', $gibbonReportID)
            ->where('gibbonReportArchiveEntry.type=:type')
            ->bindValue('type', $type)
            ->orderBy(['gibbonReportArchiveEntry.timestampModified DESC'])
            ->limit(1);

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        if ($type == 'Batch') {
            $query->where('gibbonReportArchiveEntry.gibbonYearGroupID=:contextID')
                  ->bindValue('contextID', $contextID);
        } else {
            $query->where('gibbonReportArchiveEntry.gibbonPersonID=:contextID')
                  ->bindValue('contextID', $contextID);
        }

        return $this->runSelect($query)->fetch();
    }

    public function getRecentArchiveEntryByReportIdentifier($gibbonSchoolYearID, $reportIdentifier, $type, $contextID, $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.*'])
            ->innerJoin('gibbonReportArchive', 'gibbonReportArchive.gibbonReportArchiveID=gibbonReportArchiveEntry.gibbonReportArchiveID')
            ->leftJoin('gibbonReport', 'gibbonReport.gibbonReportID=gibbonReportArchiveEntry.gibbonReportID')
            ->where('gibbonReportArchiveEntry.reportIdentifier=:reportIdentifier')
            ->bindValue('reportIdentifier', $reportIdentifier)
            ->where('gibbonReportArchiveEntry.type=:type')
            ->bindValue('type', $type)
            ->where('gibbonReportArchiveEntry.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->orderBy(['gibbonReportArchiveEntry.timestampModified DESC'])
            ->limit(1);

        $query = $this->applyArchiveAccessLogic($query, $roleCategory, $viewDraft, $viewPast);

        if ($type == 'Batch') {
            $query->where('gibbonReportArchiveEntry.gibbonYearGroupID=:contextID')
                  ->bindValue('contextID', $contextID);
        } else {
            $query->where('gibbonReportArchiveEntry.gibbonPersonID=:contextID')
                  ->bindValue('contextID', $contextID);
        }

        return $this->runSelect($query)->fetch();
    }

    public function selectArchiveEntriesByReportIdentifier($gibbonSchoolYearID, $reportIdentifier)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.gibbonPersonID as groupBy', 'gibbonReportArchiveEntry.*'])
            ->where('gibbonReportArchiveEntry.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonReportArchiveEntry.reportIdentifier=:reportIdentifier')
            ->bindValue('reportIdentifier', $reportIdentifier)
            ->where("gibbonReportArchiveEntry.type='Single'");

        return $this->runSelect($query);
    }

    public function selectArchiveEntryByAccessToken($gibbonReportArchiveEntryID, $accessToken)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols(['gibbonReportArchiveEntry.*'])
            ->where('CURRENT_TIMESTAMP <= gibbonReportArchiveEntry.timestampAccessExpiry')
            ->where('gibbonReportArchiveEntry.gibbonReportArchiveEntryID=:gibbonReportArchiveEntryID')
            ->bindValue('gibbonReportArchiveEntryID', $gibbonReportArchiveEntryID)
            ->where('gibbonReportArchiveEntry.accessToken=:accessToken')
            ->bindValue('accessToken', $accessToken)
            ->where("gibbonReportArchiveEntry.type='Single'")
            ->where("gibbonReportArchiveEntry.status='Final'");

        return $this->runSelect($query);
    }

    protected function applyArchiveAccessLogic(&$query, $roleCategory = 'Other', $viewDraft = false, $viewPast = false)
    {
        if (!$viewDraft) {
            $query->where("gibbonReportArchiveEntry.status='Final'")
                  ->where("(gibbonReport.gibbonReportID IS NULL OR gibbonReport.accessDate <= :currentDateTime)")
                  ->bindValue('currentDateTime', date('Y-m-d H:i:s'));
        }

        if (!$viewPast) {
            $query->where("gibbonReportArchiveEntry.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')");
        }

        if ($roleCategory == 'Staff') {
            $query->where("gibbonReportArchive.viewableStaff='Y'");
        } elseif ($roleCategory == 'Student') {
            $query->where("gibbonReportArchive.viewableStudents='Y'");
        } elseif ($roleCategory == 'Parent') {
            $query->where("gibbonReportArchive.viewableParents='Y'");
        } else {
            $query->where("gibbonReportArchive.viewableOther='Y'");
        }

        return $query;
    }
}
