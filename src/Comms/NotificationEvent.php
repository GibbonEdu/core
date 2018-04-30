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

use Gibbon\Contracts\Database\Connection;
use Gibbon\session;
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

    protected $eventDetails;

    /**
     * Create a new notification event which correlates to an event type defined in gibbonNotificationEvents.
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
     * Defines the body text of the notification, added to the notifications page and optionally emailed to recipients.
     *
     * @param  string  $text
     */
    public function setNotificationText($text)
    {
        $this->text = $text;
    }

    /**
     * Sets the link that opens when the notification is viewed and archived.
     *
     * @param  string  $actionLink
     */
    public function setActionLink($actionLink)
    {
        $this->actionLink = $actionLink;
    }

    /**
     * Add a scopeType => scopeID pair to the list. This defines which filters will match when looking for event listeners.
     * Eg: a scopeType of gibbonYearGroupID will only match listeners for that specific year group ID.
     * Prevent duplicates using a type+id array key
     *
     * @param  string     $type
     * @param  int|array  $id
     */
    public function addScope($type, $id)
    {
        if (empty($type) || empty($id)) return;

        if (is_array($id)) {
            foreach ($id as $idSingle) {
                $arrayKey = $type.intval($idSingle);
                $this->scopes[$arrayKey] = array('type' => $type, 'id' => $idSingle);
            }
        } else {
            $arrayKey = $type.intval($id);
            $this->scopes[$arrayKey] = array('type' => $type, 'id' => $id);
        }
    }

    /**
     * Adds a recipient to the list. Avoids duplicates by checking presence in the the array.
     *
     * @param  int|string  $gibbonPersonID
     * @return bool
     */
    public function addRecipient($gibbonPersonID)
    {
        if (empty($gibbonPersonID)) return false;

        $gibbonPersonID = intval($gibbonPersonID);

        if (in_array($gibbonPersonID, $this->recipients) == false) {
            $this->recipients[] = $gibbonPersonID;
        }

        return true;
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
     * Collects and sends all notifications for this event, returning a send report array.
     *
     * @param   NotificationGateway  $gateway
     * @param   NotificationSender   $sender
     * @return  array Send report with success/fail counts.
     */
    public function sendNotifications(Connection $pdo, session $session)
    {
        $gateway = new NotificationGateway($pdo);
        $sender = new NotificationSender($gateway, $session);

        $this->pushNotifications($gateway, $sender);

        return $sender->sendNotifications();
    }

    /**
     * Adds event listeners to the recipients list, then pushes a notification for each recipient to the notification sender.
     * Does not perform the sending of notifications (can be used for bulk processing).
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
     * Get the event row from the database (lazy-load)
     *
     * @param   NotificationGateway  $gateway
     * @return  array Datbase row, null on failure
     */
    public function getEventDetails(NotificationGateway $gateway, $key = null)
    {
        if (empty($this->eventDetails)) {
            $result = $gateway->selectNotificationEventByName($this->moduleName, $this->event);
            $this->eventDetails = ($result && $result->rowCount() == 1)? $result->fetch() : null;
        }

        return (!empty($key) && isset($this->eventDetails[$key]))? $this->eventDetails[$key] : $this->eventDetails;
    }

    /**
     * Finds all listeners in the database for this event and adds them as recipients. The returned set
     * of listeners are filtered by the event scopes.
     *
     * @param    NotificationGateway  $gateway
     * @param    int                  $gibbonNotificationEventID
     * @param    array                $scopes
     * @return int Listener count
     */
    protected function addEventListeners(NotificationGateway $gateway, $gibbonNotificationEventID, $scopes)
    {
        $result = $gateway->selectNotificationListenersByScope($gibbonNotificationEventID, $scopes);

        if ($result && $result->rowCount() > 0) {
            while ($listener = $result->fetch()) {
                $this->addRecipient($listener['gibbonPersonID']);
            }
        }

        return $result->rowCount();
    }
}
