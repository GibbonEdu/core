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

namespace Gibbon\Module\FreeLearning\Domain\System;

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;

/**
 * Notification Gateway
 *
 * Provides a data access layer for the gibbonNotification table
 *
 * @version v25
 * @since   v14
 */
class NotificationGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonNotification';
    private static $primaryKey = 'gibbonNotificationID';

    private static $searchableColumns = [];

    public function queryNotificationsByPerson(QueryCriteria $criteria, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonNotification.*', "(CASE WHEN gibbonModule.gibbonModuleID IS NOT NULL THEN gibbonModule.name ELSE 'System' END) AS source"
            ])
            ->innerJoin('gibbonModule', 'gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID')
            ->where("gibbonModule.name='Free Learning'")
            ->where("gibbonNotification.status='New'")
            ->where("gibbonNotification.text LIKE CONCAT('%','has added a comment to','%')")
            ->where('gibbonNotification.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function archiveCommentNotificationsByEnrolment($gibbonPersonID, $actionLink)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID, 'actionLink' => $actionLink);
        $sql = "UPDATE gibbonNotification SET status='Archived' WHERE gibbonPersonID=:gibbonPersonID AND actionLink=:actionLink";

        return $this->db()->select($sql, $data);
    }
}
