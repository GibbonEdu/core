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

    protected $scope = array();
    protected $listeners = array();

    public function __construct($moduleName, $event)
    {
        $this->moduleName = $moduleName;
        $this->event = $event;
    }

    public function addScope($type, $id)
    {
        $scope[$type] = $id;
    }

    public function findEventListeners(NotificationGateway $gateway)
    {
        // Do stuff
        return true;
    }

    public function pushNotifications(NotificationsSender $sender)
    {
        // Do stuff
        return true;
    }
}
