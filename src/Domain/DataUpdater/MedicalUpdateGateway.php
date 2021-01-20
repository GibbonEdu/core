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

namespace Gibbon\Domain\DataUpdater;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v16
 * @since   v16
 */
class MedicalUpdateGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonPersonMedicalUpdate';
    private static $primaryKey = 'gibbonPersonMedicalUpdateID';

    private static $searchableColumns = [''];

    private static $scrubbableKey = 'gibbonPersonID';
    private static $scrubbableColumns = ['bloodType' => '','longTermMedication' => '','longTermMedicationDetails' => '','tetanusWithin10Years' => '','comment' => ''];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryDataUpdates(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPersonMedicalUpdateID', 'gibbonPersonMedicalUpdate.status', 'gibbonPersonMedicalUpdate.timestamp', 'target.preferredName', 'target.surname', 'updater.title as updaterTitle', 'updater.preferredName as updaterPreferredName', 'updater.surname as updaterSurname'
            ])
            ->leftJoin('gibbonPerson AS target', 'target.gibbonPersonID=gibbonPersonMedicalUpdate.gibbonPersonID')
            ->leftJoin('gibbonPerson AS updater', 'updater.gibbonPersonID=gibbonPersonMedicalUpdate.gibbonPersonIDUpdater')
            ->where('gibbonPersonMedicalUpdate.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectMedicalConditionUpdatesByID($gibbonPersonMedicalUpdateID)
    {
        $data = array('gibbonPersonMedicalUpdateID' => $gibbonPersonMedicalUpdateID);
        $sql = "SELECT gibbonPersonMedicalConditionUpdate.*, gibbonAlertLevel.name AS risk, (CASE WHEN gibbonMedicalCondition.gibbonMedicalConditionID IS NOT NULL THEN gibbonMedicalCondition.name ELSE gibbonPersonMedicalConditionUpdate.name END) as name 
                FROM gibbonPersonMedicalConditionUpdate
                JOIN gibbonAlertLevel ON (gibbonPersonMedicalConditionUpdate.gibbonAlertLevelID=gibbonAlertLevel.gibbonAlertLevelID)
                LEFT JOIN gibbonMedicalCondition ON (gibbonMedicalCondition.gibbonMedicalConditionID=gibbonPersonMedicalConditionUpdate.name)
                WHERE gibbonPersonMedicalConditionUpdate.gibbonPersonMedicalUpdateID=:gibbonPersonMedicalUpdateID 
                ORDER BY gibbonPersonMedicalConditionUpdate.name";

        return $this->db()->select($sql, $data);
    }
}
