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

namespace Gibbon\Domain\Messenger;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * MessengerGateway
 *
 * @version v19
 * @since   v19
 */
class MessengerGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMessenger';
    private static $primaryKey = 'gibbonMessengerID';
    private static $searchableColumns = ['gibbonMessenger.subject', 'gibbonMessenger.body'];
    
    /**
     * Queries the list of messages for the Manage Messages page, optionally filtered for the current user.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryMessages(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonMessenger.gibbonMessengerID', 'gibbonMessenger.subject', 'gibbonMessenger.timestamp', 'gibbonMessenger.email', 'gibbonMessenger.messageWall', 'gibbonMessenger.sms', 'gibbonMessenger.messageWall_date1', 'gibbonMessenger.messageWall_date2', 'gibbonMessenger.messageWall_date3', 'gibbonMessenger.emailReceipt', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonRole.category', 
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonMessenger.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->where('gibbonMessenger.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonMessenger.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    public function getRecentMessageWallTimestamp()
    {
        $sql = "SELECT UNIX_TIMESTAMP(timestamp) FROM gibbonMessenger WHERE messageWall='Y' ORDER BY timestamp DESC LIMIT 1";

        return $this->db()->selectOne($sql);
    }

    public function getSendingMessages()
    {
        $query = $this
            ->newSelect()
            ->from('gibbonLog')
            ->cols(['gibbonLog.gibbonLogID', 'gibbonLog.serialisedArray'])
            ->where("gibbonLog.title='Background Process - MessageProcess'")
            ->where("(gibbonLog.serialisedArray LIKE '%s:7:\"Running\";%' OR gibbonLog.serialisedArray LIKE '%s:7:\"Ready\";%')")
            ->orderBy(['gibbonLog.timestamp DESC']);

        $logs = $this->runSelect($query)->fetchAll();

        return array_filter(array_reduce($logs, function ($group, $item) {
            $item['data'] = unserialize($item['serialisedArray']) ?? [];
            $gibbonMessengerID =  str_pad(($item['data']['data'][0] ?? 0), 12, '0', STR_PAD_LEFT);

            if (!empty($gibbonMessengerID)) {
                $group[$gibbonMessengerID] = $item['gibbonLogID'];
            }

            return $group;
        }, []));
    }
}
