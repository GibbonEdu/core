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
 * Raises an event and collects recipients. Looks for matching event listeners, then pushes resulting notifications to a sender.
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

    /**
     * Create a new notification event which must correlate to an event type defined in gibbonNotificationEvents.
     *
     * @param  string  $moduleName
     * @param  string  $event
     */
    public function __construct($moduleName, $event)
    {
        $this->moduleName = $moduleName;
        $this->event = $event;
    }

    /**
     * Defines the body text and link of the notification, added to the notifications page and optionally emailed to recipients.
     *
     * @param  string  $text
     * @param  string  $actionLink
     */
    public function setNotificationText($text, $actionLink)
    {
        $this->text = $text;
        $this->actionLink = $actionLink;
    }

    /**
     * Add a scopeType => scopeID pair to the list. This defines which filters will match when looking for event listeners.
     * Eg: a scopeType of gibbonYearGroupID will only match listeners for that specific year group ID.
     *
     * @param  string  $type
     * @param  int     $id
     */
    public function addScope($type, $id)
    {
        $this->scopes[$type] = $id;
    }
 
    /**
     * Adds a recipient to the list. Avoids duplicates by checking presence in the the array. 
     *
     * @param  int|string  $gibbonPersonID
     */
    public function addRecipient($gibbonPersonID)
    {
        $gibbonPersonID = intval($gibbonPersonID);

        if (in_array($gibbonPersonID, $this->recipients) == false) {
            $this->recipients[] = $gibbonPersonID;
        }
    }

    /**
     * Gets the current recipient count for this event. If called after pushNotifications() it will all include listener count.
     *
     * @return  int
     */
    public function getRecipientCount()
    {
        return (isset($this->recipients) && is_array($this->recipients))? count($this->recipients) : 0;
    }

    /**
     * Adds event listeners to the recipients list, then pushes a notification for each recipient to the notification sender.
     *
     * @param   NotificationGateway  $gateway
     * @param   NotificationSender   $sender
     * @return  int|bool Final recipient count, false on failure
     */
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

    /**
     * Get the event row from the database
     *
     * @param   NotificationGateway  $gateway
     * @return  array Datbase row, null on failure
     */
    protected function getEventDetails(NotificationGateway $gateway)
    {
        $result = $gateway->selectNotificationEventByName($this->moduleName, $this->event);

        return ($result && $result->rowCount() == 1)? $result->fetch() : null;
    }

    /**
     * Finds all listeners for this event and adds them as recipients. The returned set of listeners are
     * filtered by the event scopes.
     *
     * @param    NotificationGateway  $gateway
     * @param    int                  $gibbonNotificationEventID
     * @param    array                $scopes
     * @return int Listener count
     */
    protected function addEventListeners(NotificationGateway $gateway, $gibbonNotificationEventID, $scopes)
    {
        $result = $gateway->selectNotificationEventListenersByScope($gibbonNotificationEventID, $scopes);

        if ($result && $result->rowCount() > 0) {
            while ($listener = $result->fetch()) {
                $this->addRecipient($listener['gibbonPersonID']);
            }
        }

        return $result->rowCount();
    }
}
