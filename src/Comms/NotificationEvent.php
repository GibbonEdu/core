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

namespace Gibbon\Comms;

use Gibbon\sqlConnection;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;

/**
 * Notification Event
 *
 * Raises an event and looks for matching event listeners, then pushes resulting notifications to a sender.
 *
 * @version v14
 * @since   v14
 */
class NotificationEvent
{
    protected $moduleName;
    protected $event;
    protected $text;
    protected $actionLink;

    protected $scopes = array();
    protected $recipients = array();

    public function __construct($moduleName, $event)
    {
        $this->moduleName = $moduleName;
        $this->event = $event;
    }

    public function setNotificationText($text, $actionLink)
    {
        $this->text = $text;
        $this->actionLink = $actionLink;
    }

    public function addScope($type, $id)
    {
        $this->scopes[$type] = $id;
    }

    public function addRecipient($gibbonPersonID)
    {
        $gibbonPersonID = intval($gibbonPersonID);

        if (in_array($gibbonPersonID, $this->recipients) == false) {
            $this->recipients[] = $gibbonPersonID;
        }
    }

    public function getRecipientCount()
    {
        return (isset($this->recipients) && is_array($this->recipients))? count($this->recipients) : 0;
    }

    public function pushNotifications(NotificationGateway $gateway, NotificationSender $sender)
    {
        $eventDetails = $this->getEventDetails($gateway);

        if (empty($eventDetails) || $eventDetails['active'] == 'N') {
            return false;
        }

        $this->addEventListeners($gateway, $eventDetails['gibbonNotificationEventID'], $this->scopes);

        if ($this->getRecipientCount() == 0) {
            return false;
        }

        foreach ($this->recipients as $gibbonPersonID) {
            $sender->addNotification($gibbonPersonID, $this->text, $this->moduleName, $this->actionLink);
        }
        
        return $this->getRecipientCount();
    }

    protected function getEventDetails(NotificationGateway $gateway)
    {
        $result = $gateway->selectNotificationEventByName($this->moduleName, $this->event);

        return ($result && $result->rowCount() == 1)? $result->fetch() : null;
    }

    protected function addEventListeners(NotificationGateway $gateway, $gibbonNotificationEventID, $scopes)
    {
        $result = $gateway->selectNotificationEventListenersByScope($gibbonNotificationEventID, $scopes);

        if ($result && $result->rowCount() > 0) {
            while ($listener = $result->fetch()) {
                $this->addRecipient($listener['gibbonPersonID']);
            }
        }
    }
}
