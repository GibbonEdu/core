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

namespace Gibbon\Domain\System;

use Gibbon\Contracts\Database\Connection;

/**
 * Notification Gateway
 *
 * Provides a data access layer for the gibbonNotification table
 *
 * @version v14
 * @since   v14
 */
class NotificationGateway
{
    protected $pdo;

    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    /* NOTIFICATIONS */
    public function selectNotification($gibbonNotificationID)
    {
        $data = array('gibbonNotificationID' => $gibbonNotificationID);
        $sql = "SELECT * FROM gibbonNotification WHERE gibbonNotificationID=:gibbonNotificationID";

        return $this->pdo->select($sql, $data);
    }

    public function selectNotificationByStatus($data, $status = 'New')
    {
        $data['status'] = $status;
        $sql = "SELECT * FROM gibbonNotification WHERE gibbonPersonID=:gibbonPersonID AND text=:text AND actionLink=:actionLink AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName) AND status=:status";

        return $this->pdo->select($sql, $data);
    }

    public function updateNotificationCount($gibbonNotificationID, $count)
    {
        $data = array('gibbonNotificationID' => $gibbonNotificationID, 'count' => $count);
        $sql = "UPDATE gibbonNotification SET count=:count, timestamp=now() WHERE gibbonNotificationID=:gibbonNotificationID";

        return $this->pdo->update($sql, $data);
    }

    public function insertNotification($data)
    {
        $sql = 'INSERT INTO gibbonNotification SET gibbonPersonID=:gibbonPersonID, gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:moduleName), text=:text, actionLink=:actionLink, timestamp=now()';

        return $this->pdo->insert($sql, $data);
    }

    /* NOTIFICATION EVENTS */
    public function selectNotificationEventByID($gibbonNotificationEventID)
    {
        $data = array('gibbonNotificationEventID' => $gibbonNotificationEventID);
        $sql = "SELECT * FROM gibbonNotificationEvent WHERE gibbonNotificationEventID=:gibbonNotificationEventID";

        return $this->pdo->select($sql, $data);
    }

    public function selectNotificationEventByName($moduleName, $event)
    {
        $data = array('moduleName' => $moduleName, 'event' => $event);
        $sql = "SELECT gibbonNotificationEvent.*
                FROM gibbonNotificationEvent
                JOIN gibbonModule ON (gibbonNotificationEvent.moduleName=gibbonModule.name)
                WHERE gibbonNotificationEvent.moduleName=:moduleName
                AND gibbonNotificationEvent.event=:event
                AND gibbonModule.active='Y'";

        return $this->pdo->select($sql, $data);
    }

    public function selectAllNotificationEvents()
    {
        $sql = "SELECT gibbonNotificationEvent.*, COUNT(gibbonNotificationListenerID) as listenerCount FROM gibbonNotificationEvent JOIN gibbonModule ON (gibbonNotificationEvent.moduleName=gibbonModule.name) LEFT JOIN gibbonNotificationListener ON (gibbonNotificationEvent.gibbonNotificationEventID=gibbonNotificationListener.gibbonNotificationEventID) WHERE gibbonModule.active='Y' GROUP BY gibbonNotificationEvent.gibbonNotificationEventID ORDER BY gibbonModule.name, gibbonNotificationEvent.event";

        return $this->pdo->select($sql);
    }

    public function updateNotificationEvent($update)
    {
        $data = array('gibbonNotificationEventID' => $update['gibbonNotificationEventID'], 'active' => $update['active']);
        $sql = "UPDATE gibbonNotificationEvent SET active=:active WHERE gibbonNotificationEventID=:gibbonNotificationEventID";

        return $this->pdo->update($sql, $data);
    }

    /* NOTIFICATION LISTENERS */
    public function selectNotificationListener($gibbonNotificationListenerID)
    {
        $data = array('gibbonNotificationListenerID' => $gibbonNotificationListenerID);
        $sql = "SELECT * FROM gibbonNotificationListener WHERE gibbonNotificationListenerID=:gibbonNotificationListenerID";

        return $this->pdo->select($sql, $data);
    }

    public function selectAllNotificationListeners($gibbonNotificationEventID, $groupByPerson = true)
    {
        $data = array('gibbonNotificationEventID' => $gibbonNotificationEventID);
        $sql = "SELECT gibbonNotificationListener.*, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.title, gibbonPerson.receiveNotificationEmails
                FROM gibbonNotificationListener
                JOIN gibbonNotificationEvent ON (gibbonNotificationListener.gibbonNotificationEventID=gibbonNotificationEvent.gibbonNotificationEventID)
                JOIN gibbonPerson ON (gibbonNotificationListener.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID OR FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                JOIN gibbonPermission ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID)
                JOIN gibbonAction ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                WHERE gibbonNotificationListener.gibbonNotificationEventID=:gibbonNotificationEventID
                AND gibbonNotificationEvent.actionName=gibbonAction.name";

        if ($groupByPerson) {
            $sql .= " GROUP BY gibbonNotificationListener.gibbonPersonID";
        } else {
            $sql .= " GROUP BY gibbonNotificationListener.gibbonNotificationListenerID";
        }

        return $this->pdo->select($sql, $data);
    }

    public function selectNotificationListenersByScope($gibbonNotificationEventID, $scopes = array())
    {
        $data = array('gibbonNotificationEventID' => $gibbonNotificationEventID);
        $sql = "SELECT DISTINCT gibbonPersonID FROM gibbonNotificationListener WHERE gibbonNotificationEventID=:gibbonNotificationEventID";

        if (is_array($scopes) && count($scopes) > 0) {
            $sql .= " AND (scopeType='All' ";
            $i = 0;
            foreach ($scopes as $scope) {
                $data['scopeType'.$i] = $scope['type'];
                $data['scopeTypeID'.$i] = $scope['id'];
                $sql .= " OR (scopeType=:scopeType{$i} AND scopeID=:scopeTypeID{$i})";
                $i++;
            }
            $sql .= ")";
        } else {
            $sql .= " AND scopeType='All'";
        }

        return $this->pdo->select($sql, $data);
    }

    public function insertNotificationListener($data)
    {
        $sql = 'INSERT INTO gibbonNotificationListener SET gibbonNotificationEventID=:gibbonNotificationEventID, gibbonPersonID=:gibbonPersonID, scopeType=:scopeType, scopeID=:scopeID';

        return $this->pdo->insert($sql, $data);
    }

    public function deleteNotificationListener($gibbonNotificationListenerID)
    {
        $data = array('gibbonNotificationListenerID' => $gibbonNotificationListenerID);
        $sql = 'DELETE FROM gibbonNotificationListener WHERE gibbonNotificationListenerID=:gibbonNotificationListenerID';

        return $this->pdo->delete($sql, $data);
    }

    /* NOTIFICATION PREFERENCES */
    public function getNotificationPreference($gibbonPersonID)
    {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT email, receiveNotificationEmails FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID AND receiveNotificationEmails='Y' AND NOT email=''";

        return $this->pdo->selectOne($sql, $data);
    }
}
