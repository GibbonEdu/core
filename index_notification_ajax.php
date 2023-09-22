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

use Gibbon\Domain\System\SessionGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\SettingGateway;

// Gibbon system-wide includes
include './gibbon.php';

$result = ['count' => 0, 'alarm' => false, 'timeout' => 'expire'];

if ($session->has('gibbonPersonID')) {
    // Check for system alarm
    if ($session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
        $alarm = $container->get(SettingGateway::class)->getSettingByScope('System', 'alarm');
        $result['alarm'] = $alarm == 'General' || $alarm == 'Lockdown' || $alarm == 'Custom'
            ? strtolower($alarm)
            : false;
    }

    // Get notification count for the current user
    $notificationGateway = $container->get(NotificationGateway::class);
    $criteria = $notificationGateway->newQueryCriteria();
    $notifications = $notificationGateway->queryNotificationsByPerson($criteria, $session->get('gibbonPersonID'), 'New');

    $result['count'] = $notifications->count();

    // Check for session timeout
    $sessionGateway = $container->get(SessionGateway::class);
    $sessionInfo = $sessionGateway->getByID(session_id());
    if (\SESSION_TABLE_AVAILABLE && !empty($sessionInfo)) {
        $sessionLastActive = strtotime($sessionInfo['timestampModified']);
        $sessionDuration = $session->get('sessionDuration');
        $timeDifference = time() - $sessionLastActive;
        
        if (empty($sessionInfo['gibbonPersonID']) || (isset($sessionInfo['sessionStatus']) && empty($sessionInfo['sessionStatus']))) {
            $result['timeout'] = 'force';
        } elseif ($timeDifference > $sessionDuration) {
            $result['timeout'] = $timeDifference > $sessionDuration + 300 ? 'expire' : 'warn';
        } else {
            $result['timeout'] = false;
        }
    } else {
        $result['timeout'] = false;
    }
}

echo json_encode($result);
