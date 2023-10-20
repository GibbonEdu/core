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

class ReportGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReport';
    private static $primaryKey = 'gibbonReportID';
    private static $searchableColumns = ['gibbonReport.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryReportsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReport.gibbonReportID', 'gibbonReport.name', 'gibbonReport.active', 'gibbonReport.status', 'gibbonReport.timestampModified', 'gibbonReport.accessDate', 'gibbonReportTemplate.context',
            "(SELECT GROUP_CONCAT(gibbonYearGroup.nameShort separator ', ') FROM gibbonYearGroup WHERE FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList)) as yearGroups", "(SELECT MAX(timestampModified) FROM gibbonReportArchiveEntry WHERE gibbonReportArchiveEntry.gibbonReportID=gibbonReport.gibbonReportID AND type='Batch') as timestampGenerated"])
            ->innerJoin('gibbonReportTemplate', 'gibbonReportTemplate.gibbonReportTemplateID=gibbonReport.gibbonReportTemplateID')
            ->leftJoin('gibbonReportingCycle', 'gibbonReportingCycle.gibbonReportingCycleID=gibbonReport.gibbonReportingCycleID')
            ->where('gibbonReport.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonReport.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function queryYearGroupsByReport(QueryCriteria $criteria, $gibbonReportID, $viewDraft = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReport.gibbonReportID', 'gibbonYearGroup.gibbonYearGroupID', 'gibbonYearGroup.name', 'gibbonYearGroup.nameShort', 'gibbonYearGroup.sequenceNumber', 'gibbonReport.gibbonSchoolYearID', 'COUNT(DISTINCT gibbonReportArchiveEntry.gibbonPersonID) as count', "COUNT(DISTINCT CASE WHEN gibbonReportArchiveEntry.gibbonPersonIDAccessed IS NOT NULL THEN gibbonReportArchiveEntry.gibbonReportArchiveEntryID END) as readCount"])
            ->innerJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonReport.gibbonYearGroupIDList)')
            ->where('gibbonReport.gibbonReportID=:gibbonReportID')
            ->bindValue('gibbonReportID', $gibbonReportID)
            ->groupBy(['gibbonReport.gibbonReportID', 'gibbonYearGroup.gibbonYearGroupID']);

        if (!$viewDraft) {
            $query->leftJoin('gibbonReportArchiveEntry', "gibbonReportArchiveEntry.gibbonReportID=gibbonReport.gibbonReportID AND gibbonReportArchiveEntry.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonReportArchiveEntry.type='Single' AND gibbonReportArchiveEntry.status='Final'");
        } else {
            $query->leftJoin('gibbonReportArchiveEntry', "gibbonReportArchiveEntry.gibbonReportID=gibbonReport.gibbonReportID AND gibbonReportArchiveEntry.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID AND gibbonReportArchiveEntry.type='Single'");
        }

        return $this->runQuery($query, $criteria);
    }

    public function queryFormGroupsByReport(QueryCriteria $criteria, $gibbonReportID, $gibbonYearGroupID = '', $viewDraft = false)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReport.gibbonReportID', 'gibbonYearGroup.gibbonYearGroupID', 'gibbonFormGroup.gibbonFormGroupID', 'gibbonFormGroup.name', 'gibbonYearGroup.nameShort', 'gibbonYearGroup.sequenceNumber', 'gibbonReport.gibbonSchoolYearID', 'COUNT(DISTINCT gibbonReportArchiveEntry.gibbonPersonID) as count', "COUNT(DISTINCT CASE WHEN gibbonReportArchiveEntry.gibbonPersonIDAccessed IS NOT NULL THEN gibbonReportArchiveEntry.gibbonReportArchiveEntryID END) as readCount"])
            ->innerJoin('gibbonReportArchiveEntry', 'gibbonReportArchiveEntry.gibbonReportID=gibbonReport.gibbonReportID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonReportArchiveEntry.gibbonFormGroupID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonReportArchiveEntry.gibbonYearGroupID')
            ->where("gibbonReportArchiveEntry.type='Single'")
            ->where('(gibbonReport.gibbonReportID=:gibbonReportID OR gibbonReportArchiveEntry.reportIdentifier=:gibbonReportID)')
            ->bindValue('gibbonReportID', $gibbonReportID)
            ->where('gibbonYearGroup.gibbonYearGroupID=:gibbonYearGroupID')
            ->bindValue('gibbonYearGroupID', $gibbonYearGroupID)
            ->groupBy(['gibbonReport.gibbonReportID', 'gibbonFormGroup.gibbonFormGroupID']);

        if (!$viewDraft) {
            $query->where("gibbonReportArchiveEntry.status='Final'");
        }

        return $this->runQuery($query, $criteria);
    }

    public function selectActiveReportsBySchoolYear($gibbonSchoolYearID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonReportID as value, name FROM gibbonReport WHERE active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->db()->select($sql, $data);
    }

    public function getRunningReports($gibbonReportID = null, $gibbonYearGroupID = null)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonLog')
            ->cols(['gibbonLog.gibbonLogID', 'gibbonLog.serialisedArray'])
            ->where("gibbonLog.title='Background Process - GenerateReportProcess'")
            ->where("(gibbonLog.serialisedArray LIKE '%s:7:\"Running\";%' OR gibbonLog.serialisedArray LIKE '%s:7:\"Ready\";%')")
            ->orderBy(['gibbonLog.timestamp DESC']);

        $logs = $this->runSelect($query)->fetchAll();

        return array_filter(array_reduce($logs, function ($group, $item) {
            $item['data'] = unserialize($item['serialisedArray']) ?? [];
            $item['data']['processID'] = $item['gibbonLogID'];
            $item['gibbonReportID'] = $item['data']['data'][0];
            $item['gibbonYearGroupID'] = $item['data']['data'][1];

            $gibbonYearGroupIDList = is_array($item['gibbonYearGroupID'])? $item['gibbonYearGroupID'] : [$item['gibbonYearGroupID']];
            foreach ($gibbonYearGroupIDList as $gibbonYearGroupID) {
                $group[$item['gibbonReportID']][$gibbonYearGroupID] = $item['data'];
            }

            return $group;
        }, []));
    }
}
