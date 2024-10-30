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

namespace Gibbon\Domain\System;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Session Gateway
 *
 * @version v23
 * @since   v23
 */
class SessionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSession';
    private static $primaryKey = 'gibbonSessionID';

    /**
     * Queries the list of sessions.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActiveSessions(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSessionID', 'gibbonSession.timestampCreated', 'gibbonSession.sessionStatus', 'gibbonSession.timestampModified', 'gibbonPerson.gibbonPersonID', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.username',  'gibbonPerson.lastIPAddress', 'gibbonRole.category AS roleCategory', "CONCAT(gibbonModule.name, ': ', SUBSTRING_INDEX(gibbonAction.name, '_', 1)) AS actionName"
            ])
            ->leftJoin('gibbonPerson', 'gibbonSession.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->leftJoin('gibbonAction', 'gibbonAction.gibbonActionID=gibbonSession.gibbonActionID')
            ->leftJoin('gibbonModule', 'gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID')
            ->where('gibbonSession.gibbonPersonID IS NOT NULL');

        return $this->runQuery($query, $criteria);
    }

    public function updateSessionAction($gibbonSessionID, $actionName, $moduleName, $gibbonPersonID)
    {
        $data = ['gibbonSessionID' => $gibbonSessionID, 'gibbonPersonID' => $gibbonPersonID, 'actionName' => $actionName, 'moduleName' => $moduleName, 'timestampCreated' => date('Y-m-d H:i:s'), 'timestampModified' => date('Y-m-d H:i:s')];
        $sql = "INSERT INTO gibbonSession (gibbonSessionID, gibbonPersonID, gibbonActionID, timestampCreated, timestampModified) VALUES (:gibbonSessionID, :gibbonPersonID, (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID) WHERE gibbonModule.name = :moduleName AND FIND_IN_SET(:actionName, gibbonAction.URLList) AND :actionName <> '' LIMIT 1), :timestampCreated, :timestampModified) ON DUPLICATE KEY UPDATE gibbonActionID=VALUES(gibbonActionID), timestampModified=:timestampModified";

        return $this->db()->update($sql, $data);
    }

    public function updateSessionStatus($gibbonSessionID, $gibbonPersonID, $sessionStatus)
    {
        $data = ['gibbonSessionID' => $gibbonSessionID, 'gibbonPersonID' => $gibbonPersonID, 'sessionStatus' => $sessionStatus, 'timestampCreated' => date('Y-m-d H:i:s'), 'timestampModified' => date('Y-m-d H:i:s')];
        $sql = "INSERT INTO gibbonSession (gibbonSessionID, gibbonPersonID, sessionStatus, timestampCreated, timestampModified) VALUES (:gibbonSessionID, :gibbonPersonID, :sessionStatus, :timestampCreated, :timestampModified) ON DUPLICATE KEY UPDATE sessionStatus=:sessionStatus, timestampModified=:timestampModified";

        return $this->db()->update($sql, $data);
    }

    public function updateSessionData($gibbonSessionID, $sessionData)
    {
        $data = ['gibbonSessionID' => $gibbonSessionID, 'sessionData' => $sessionData, 'timestampCreated' => date('Y-m-d H:i:s'), 'timestampModified' => date('Y-m-d H:i:s')];
        $sql = "INSERT INTO gibbonSession (gibbonSessionID, sessionData, timestampCreated, timestampModified) VALUES (:gibbonSessionID, :sessionData, :timestampCreated, :timestampModified) ON DUPLICATE KEY UPDATE sessionData=:sessionData";

        return $this->db()->update($sql, $data);
    }

    public function deleteExpiredSessions($maxLifetime)
    {
        $data = ['maxLifetime' => $maxLifetime];
        $sql = "DELETE FROM gibbonSession WHERE TIMESTAMPDIFF(SECOND, timestampModified, CURRENT_TIMESTAMP) > :maxLifetime";

        return $this->db()->delete($sql, $data);
    }

    public function logoutAllNonAdministratorUsers()
    {
        $sql = "UPDATE gibbonSession 
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonSession.gibbonPersonID)
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                SET gibbonSession.gibbonPersonID=NULL, gibbonSession.gibbonActionID=NULL, gibbonSession.sessionStatus=NULL
                WHERE gibbonRole.name <> 'Administrator'";

        return $this->db()->update($sql);
    }
}
