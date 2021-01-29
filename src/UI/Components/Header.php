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
            'url'      => $this->session->get('absoluteURL').'/index.php?q=/notifications.php',
            'count'    => $notifications->count(),
            'interval' => $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'? 10000 : 120000,
        ];

        // Alarm
        $tray['alarm'] = $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'
            ? getSettingByScope($connection2, 'System', 'alarm')
            : false;

        return $tray;
    }

    public function getNotificationTray($cacheLoad)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $return = false;

        $return .= '<div class="flex flex-row-reverse mb-1">';

        if ($this->session->has('username')) {
            //MESSAGE WALL!
            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
                $return .= "<div id='messageWall' class='relative'>";

                require_once './modules/Messenger/moduleFunctions.php';

                $messages = $this->session->get('messageWallArray') ?? [];

                $URL = $this->session->get('absoluteURL').'/index.php?q=/modules/Messenger/messageWall_view.php';
                if (count($messages) < 1) {
                    $return .= "<a class='inactive inline-block relative mr-4' title='".__('Message Wall')."' href='$URL'><img class='minorLinkIcon' style='margin-left: 4px; opacity: 0.2; vertical-align: -75%' src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/messageWall.png'></a>";
                } else {
                    $return .= "<a class='inline-block relative mr-4' title='".__('Message Wall')."' href='$URL'><span class='badge -mr-2 right-0'>".count($messages)."</span><img class='minorLinkIcon' style='margin-left: 4px; vertical-align: -75%' src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/messageWall.png'></a>";

                    if (!$this->session->has('pageLoads') and ($this->session->get('messengerLastBubble') == null or $this->session->get('messengerLastBubble') < date('Y-m-d'))) {
                        $messageBubbleBGColor = getSettingByScope($connection2, 'Messenger', 'messageBubbleBGColor');
                        $bubbleBG = '';
                        if ($messageBubbleBGColor != '') {
                            $bubbleBG = '; background-color: rgba('.$messageBubbleBGColor.')!important';
                            $return .= '<style>';
                            $return .= ".ui-tooltip, .arrow:after { $bubbleBG }";
                            $return .= '</style>';
                        }
                        $messageBubbleWidthType = getSettingByScope($connection2, 'Messenger', 'messageBubbleWidthType');
                        $bubbleWidth = 300;
                        if ($messageBubbleWidthType == 'Wide') {
                            $bubbleWidth = 700;
                        }
                        $return .= "<div id='messageBubbleArrow' style=\"left: 25px; top: 40px; z-index: 9999\" class='arrow top'></div>";
                        $return .= "<div id='messageBubble' style=\"right: -25px; top: 56px; width: ".$bubbleWidth.'px; min-width: '.$bubbleWidth.'px; max-width: 100vw; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip">';
                        $return .= '<div class="ui-tooltip-content">';
                        $return .= "<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'>".__('New Messages').'</div>';
                        
                        $output = array_values($messages);
                        $test = isset($output) ? count($output) : 0;
                        if ($test > 3) {
                            $test = 3;
                        }
                        for ($i = 0; $i < $test; ++$i) {
                            $return .= "<span style='font-size: 120%; font-weight: bold'>";
                            if (strlen($output[$i]['subject']) <= 30) {
                                $return .= $output[$i]['subject'];
                            } else {
                                $return .= substr($output[$i]['subject'], 0, 30).'...';
                            }

                            $return .= '</span><br/>';
                            $return .= '<i>'.Format::name('', $output[$i]['preferredName'], $output[$i]['surname'], 'Staff', false, true).'</i><br/><br/>';
                        }
                        if ($test > 3) {
                            $return .= '<i>'.__('Plus more').'...</i>';
                        }
                        $return .= '</div>';
                        $return .= "<div style='text-align: right; margin-top: 20px; color: #666'>";
                        $return .= "<a onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1)' style='text-decoration: none; color: #666' href='".$URL."'>".__('Read All').'</a> . ';
                        $return .= "<a style='text-decoration: none; color: #666' onclick='$(\"#messageBubble\").hide(\"fade\", {}, 1000); $(\"#messageBubbleArrow\").hide(\"fade\", {}, 1000)' href='#'>".__('Dismiss').'</a>';
                        $return .= '</div>';
                        $return .= '</div>';

                        $messageBubbleAutoHide = getSettingByScope($connection2, 'Messenger', 'messageBubbleAutoHide');
                        if ($messageBubbleAutoHide != 'N') {
                            $return .= '<script type="text/javascript">';
                            $return .= '$(function() {';
                            $return .= 'setTimeout(function() {';
                            $return .= "$(\"#messageBubble\").hide('fade', {}, 3000)";
                            $return .= '}, 10000);';
                            $return .= '});';
                            $return .= '$(function() {';
                            $return .= 'setTimeout(function() {';
                            $return .= "$(\"#messageBubbleArrow\").hide('fade', {}, 3000)";
                            $return .= '}, 10000);';
                            $return .= '});';
                            $return .= '</script>';
                        }

                        try {
                            $data = array('messengerLastBubble' => date('Y-m-d'), 'gibbonPersonID' => $this->session->get('gibbonPersonID'));
                            $sql = 'UPDATE gibbonPerson SET messengerLastBubble=:messengerLastBubble WHERE gibbonPersonID=:gibbonPersonID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (\PDOException $e) {
                        }
                    }
                }
                $return .= "</div>";
            }

            //GET & SHOW NOTIFICATIONS
            try {
                $dataNotifications = array('gibbonPersonID' => $this->session->get('gibbonPersonID'), 'gibbonPersonID2' => $this->session->get('gibbonPersonID'));
                $sqlNotifications = "(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='New')
                UNION
                (SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='New')
                ORDER BY timestamp DESC, source, text";
                $resultNotifications = $connection2->prepare($sqlNotifications);
                $resultNotifications->execute($dataNotifications);
            } catch (\PDOException $e) { }

            //Refresh notifications every 10 seconds for staff, 120 seconds for everyone else
            $interval = 120000;
            if ($this->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
                $interval = 10000;
            }
            $return .= '<script type="text/javascript">
                $(document).ready(function(){
                    setInterval(function() {
                        $("#notifications").load("index_notification_ajax.php");
                    }, '.$interval.');
                });
            </script>';

            $return .= "<div id='notifications'>";
                //CHECK FOR SYSTEM ALARM
                if ($this->session->has('gibbonRoleIDCurrentCategory')) {
                    if ($this->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
                        $alarm = getSettingByScope($connection2, 'System', 'alarm');
                        if ($alarm == 'General' or $alarm == 'Lockdown' or $alarm == 'Custom') {
                            $type = 'general';
                            if ($alarm == 'Lockdown') {
                                $type = 'lockdown';
                            } elseif ($alarm == 'Custom') {
                                $type = 'custom';
                            }
                            $return .= "<script>
                                $(document).ready(function() {
                                    $('#notifications').load('index_notification_ajax.php');
                                }) ;
                            </script>";
                        }
                    }
                }
            if ($resultNotifications->rowCount() > 0) {
                $return .= "<a class='inline-block relative mr-4' title='".__('Notifications')."' href='".$this->session->get('absoluteURL')."/index.php?q=notifications.php'><span class='badge -mr-2 right-0'>".$resultNotifications->rowCount()."</span><img class='minorLinkIcon' style='margin-left: 2px; vertical-align: -75%' src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/notifications.png'></a>";
            } else {
                $return .= "<a class='inactive inline-block relative mr-4' title='".__('Notifications')."' href='".$this->session->get('absoluteURL')."/index.php?q=notifications.php'><img class='minorLinkIcon' style='margin-left: 2px; opacity: 0.2; vertical-align: -75%' src='".$this->session->get('absoluteURL').'/themes/'.$this->session->get('gibbonThemeName')."/img/notifications.png'></a>";
            }
            $return .= '</div>';
        }

        $return .= "</div>";
        return $return;
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
