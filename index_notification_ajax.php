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

use Gibbon\Domain\System\NotificationGateway;

// Gibbon system-wide includes
include './gibbon.php';

$result = ['count' => 0, 'alarm' => false];

if ($gibbon->session->has('gibbonPersonID')) {
    // Check for system alarm
    if ($gibbon->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
        $alarm = getSettingByScope($connection2, 'System', 'alarm');
        $result['alarm'] = $alarm == 'General' || $alarm == 'Lockdown' || $alarm == 'Custom'
            ? strtolower($alarm)
            : false;
    }

    // Get notification count for the current user
    $notificationGateway = $container->get(NotificationGateway::class);
    $criteria = $notificationGateway->newQueryCriteria();
    $notifications = $notificationGateway->queryNotificationsByPerson($criteria, $gibbon->session->get('gibbonPersonID'), 'New');

    $result['count'] = $notifications->count();
}

echo json_encode($result);
