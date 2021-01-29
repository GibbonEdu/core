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

namespace Gibbon\UI\Components;

use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\NotificationGateway;

/**
 * Header View Composer
 *
 * @version  v18
 * @since    v18
 */
class Header
{
    protected $db;
    protected $session;
    protected $notificationGateway;

    public function __construct(Connection $db, Session $session, NotificationGateway $notificationGateway)
    {
        $this->db = $db;
        $this->session = $session;
        $this->notificationGateway = $notificationGateway;
    }

    public function getStatusTray()
    {
        if (!$this->session->has('username')) return [];

        $tray = [];
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        
        // Message Wall
        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
            $tray['messageWall'] = [
                'url'      => $this->session->get('absoluteURL').'/index.php?q=/modules/Messenger/messageWall_view.php',
                'messages' => count($this->session->get('messageWallArray', [])),
            ];
        }

        // Notifications
        $criteria = $this->notificationGateway->newQueryCriteria();
        $notifications = $this->notificationGateway->queryNotificationsByPerson($criteria, $this->session->get('gibbonPersonID'), 'New');

        $tray['notifications'] = [
            'url'      => $this->session->get('absoluteURL').'/index.php?q=/notifications.php&sidebar=false',
            'count'    => $notifications->count(),
            'interval' => $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'? 10000 : 120000,
        ];

        // Alarm
        $tray['alarm'] = $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'
            ? getSettingByScope($connection2, 'System', 'alarm')
            : false;

        return $tray;
    }

    public function getMinorLinks($cacheLoad)
    {
        $links = [];

        // Links for logged in users
        if ($this->session->has('username')) {
            if ($this->session->get('gibbonRoleIDCurrentCategory') == 'Student' && isActionAccessible($this->session->get('guid'), $this->db->getConnection(), '/modules/Students/student_view_details.php')) {
                $nameURL = $this->session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$this->session->get('gibbonPersonID');
            }

            $links[] = [
                'name' => $this->session->get('preferredName').' '.$this->session->get('surname'),
                'url' => $nameURL ?? '',
                'class' => 'link-white',
            ];
            
            $links[] = [
                'name' => __('Logout'),
                'url' => $this->session->get('absoluteURL').'/logout.php',
                'class' => 'link-white',
            ];

            $links[] = [
                'name' => __('Preferences'),
                'url' => $this->session->get('absoluteURL').'/index.php?q=preferences.php',
                'class' => 'link-white',
            ];

            if ($this->session->has('emailLink')) {
                $links[] = [
                    'name' => __('Email'),
                    'url' => $this->session->get('emailLink'),
                    'class' => 'link-white hidden sm:inline',
                    'target' => '_blank',
                ];
            }

            if ($this->session->has('webLink')) {
                $links[] = [
                    'name' => $this->session->get('organisationNameShort').' '.__('Website'),
                    'url' => $this->session->get('webLink'),
                    'class' => 'link-white hidden sm:inline',
                    'target' => '_blank',
                ];
            }

            if ($this->session->has('website')) {
                $links[] = [
                    'name' => __('My Website'),
                    'url' => $this->session->get('website'),
                    'class' => 'link-white hidden sm:inline',
                    'target' => '_blank',
                ];
            }
        }

        // Add a link to go back to the system/personal default language, if we're not using it
        if (!empty($this->session->get('i18n')['default']['code']) && !empty($this->session->get('i18n')['code'])) {
            if ($this->session->get('i18n')['code'] != $this->session->get('i18n')['default']['code']) {
                $links[] = [
                    'name' => trim(strstr($this->session->get('i18n')['default']['name'], '-', true)),
                    'url' => $this->session->get('absoluteURL')."?i18n=".$this->session->get('i18n')['default']['code'],
                ];
            }
        }

        if ($this->session->has('username')) {
            // Check for and display house logo
            if ($this->session->has('gibbonHouseIDLogo') and $this->session->has('gibbonHouseIDName')) {
                $links[] = [
                    'name' => "<img class='ml-1 w-10 h-10 sm:w-12 sm:h-12 lg:w-16 lg:h-16' title='".$this->session->get('gibbonHouseIDName')."' style='vertical-align: -75%;' src='".$this->session->get('absoluteURL').'/'.$this->session->get('gibbonHouseIDLogo')."'/>",
                ];
            }
        } else {
            // Display the school's web link for non-logged in visitors
            if ($this->session->has('webLink')) {
                $links[] = [
                    'name' => $this->session->get('organisationNameShort').' '.__('Website'),
                    'url' => $this->session->get('webLink'),
                    'class' => 'link-white mr-2',
                    'target' => '_blank',
                    'prepend' => __('Return to'),
                ];
            }
        }

        return $links;
    }
}
