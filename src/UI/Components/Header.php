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

use Gibbon\Services\Module\Resource;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Http\Url;

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
    protected $messengerGateway;
    protected $settingGateway;

    public function __construct(
        Connection $db,
        Session $session,
        NotificationGateway $notificationGateway,
        MessengerGateway $messengerGateway,
        SettingGateway $settingGateway
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->notificationGateway = $notificationGateway;
        $this->messengerGateway = $messengerGateway;
        $this->settingGateway = $settingGateway;
    }

    public function getStatusTray()
    {
        if (!$this->session->has('username')) return [];

        $tray = [];
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        // Message Wall
        if (isActionAccessible($guid, $connection2, Resource::fromRoute('Messenger', 'messageWall_view'))) {
            $tray['messageWall'] = [
                'url'      => Url::fromModuleRoute('Messenger', 'messageWall_view'),
                'messages' => count($this->session->get('messageWallArray', [])),
            ];
        }

        // Notifications
        $criteria = $this->notificationGateway->newQueryCriteria();
        $notifications = $this->notificationGateway->queryNotificationsByPerson($criteria, $this->session->get('gibbonPersonID'), 'New');

        $tray['notifications'] = [
            'url'      => Url::fromRoute('notifications')->withQueryParam('sidebar', 'false'),
            'count'    => $notifications->count(),
            'interval' => $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'? 10000 : 60000,
        ];

        // Alarm
        $tray['alarm'] = $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'
            ? $this->settingGateway->getSettingByScope('System', 'alarm')
            : false;

        return $tray;
    }

    public function getMinorLinks()
    {
        $links = [];

        // Links for logged in users
        if ($this->session->has('username')) {

            $links['logout'] = [
                'name' => __('Logout'),
                'url'  => $this->session->get('absoluteURL').'/logout.php',
            ];

            $links['preferences'] = [
                'name' => __('Preferences'),
                'url'  => Url::fromRoute('preferences'),
            ];

            if ($this->session->has('emailLink')) {
                $links['email'] = [
                    'name'   => __('Email'),
                    'url'    => $this->session->get('emailLink'),
                    'target' => '_blank',
                ];
            }

            if ($this->session->has('webLink')) {
                $links['webLink'] = [
                    'name'   => $this->session->get('organisationNameShort').' '.__('Website'),
                    'url'    => $this->session->get('webLink'),
                    'target' => '_blank',
                ];
            }

            if ($this->session->has('website')) {
                $links['website'] = [
                    'name'   => __('My Website'),
                    'url'    => $this->session->get('website'),
                    'target' => '_blank',
                ];
            }
        }

        // Add a link to go back to the system/personal default language, if we're not using it
        if (!empty($this->session->get(['i18n','default','code'])) && !empty($this->session->get(['i18n','code']))) {
            if ($this->session->get(['i18n','default','code']) != $this->session->get(['i18n','code'])) {
                $links['i18n'] = [
                    'name' => trim(strstr($this->session->get(['i18n','default','name']), '-', true)),
                    'url' => $this->session->get('absoluteURL')."?i18n=".$this->session->get(['i18n','default','code']),
                ];
            }
        }

        // Display the school's web link for non-logged in visitors
        if (!$this->session->has('username') && $this->session->has('webLink')) {
            $links['webLink'] = [
                'name' => $this->session->get('organisationNameShort').' '.__('Website'),
                'url' => $this->session->get('webLink'),
                'target' => '_blank',
                'prepend' => __('Return to'),
            ];
        }

        return $links;
    }

    public function getUserDetails()
    {
        if (!$this->session->has('username')) return [];

        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        $roleCategory = $this->session->get('gibbonRoleIDCurrentCategory');

        if ($roleCategory == 'Student' && isActionAccessible($guid, $connection2, Resource::fromRoute('Students', 'student_view_details'))) {
            $profileURL = Url::fromModuleRoute('Students', 'student_view_details')
                ->withQueryParam('gibbonPersonID', $this->session->get('gibbonPersonID'));
        }

        if ($roleCategory == 'Staff' && isActionAccessible($guid, $connection2, Resource::fromRoute('Staff', 'staff_view_details'))) {
            $profileURL = Url::fromModuleRoute('Staff', 'staff_view_details')
                ->withQueryParam('gibbonPersonID', $this->session->get('gibbonPersonID'));
        }

        $messageWallLatestPost = $this->messengerGateway->getRecentMessageWallTimestamp();

        return [
            'url'           => $profileURL ?? '',
            'name'          => $this->session->get('preferredName').' '.$this->session->get('surname'),
            'username'      => $this->session->get('username'),
            'roleCategory'  => $this->session->get('gibbonRoleIDCurrentCategory'),
            'image_240'     => $this->session->get('image_240'),
            'houseName'     => $this->session->get('gibbonHouseIDName'),
            'houseLogo'     => $this->session->get('gibbonHouseIDLogo'),
            'messengerRead' => strtotime((string) $this->session->get('messengerLastRead')) >= $messageWallLatestPost,
        ];
    }
}
