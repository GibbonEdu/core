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

use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;

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

    public function __construct(Connection $db, Session $session)
    {
        $this->db = $db;
        $this->session = $session;
    }

    public function getNotificationTray($cacheLoad)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        
        $return = false;

        $return .= '<div class="flex flex-row-reverse mb-1">';

        if (isset($_SESSION[$guid]['username']) != false) {
            //MESSAGE WALL!
            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php')) {
                $return .= "<div id='messageWall'>";

                require_once './modules/Messenger/moduleFunctions.php';

                $q = (isset($_GET['q'])) ? $_GET['q'] : '';

                $addReturn = null;
                if (isset($_GET['addReturn'])) {
                    $addReturn = $_GET['addReturn'];
                }
                $updateReturn = null;
                if (isset($_GET['updateReturn'])) {
                    $updateReturn = $_GET['updateReturn'];
                }
                $deleteReturn = null;
                if (isset($_GET['deleteReturn'])) {
                    $deleteReturn = $_GET['deleteReturn'];
                }
                if ($cacheLoad or ($q == '') or ($q == '/modules/Messenger/messenger_post.php' and $addReturn == 'success0') or ($q == '/modules/Messenger/messenger_postQuickWall.php' and $addReturn == 'success0') or ($q == '/modules/Messenger/messenger_manage_edit.php' and $updateReturn == 'success0') or ($q == '/modules/Messenger/messenger_manage.php' and $deleteReturn == 'success0')) {
                    $messages = getMessages($guid, $connection2, 'result');
                    $messages = unserialize($messages);
                    try {
                        $resultPosts = $connection2->prepare($messages[1]);
                        $resultPosts->execute($messages[0]);
                    } catch (PDOException $e) {
                    }

                    $_SESSION[$guid]['messageWallCount'] = 0;
                    if ($resultPosts->rowCount() > 0) {
                        $count = 0;
                        $output = array();
                        $last = '';
                        while ($rowPosts = $resultPosts->fetch()) {
                            if ($last == $rowPosts['gibbonMessengerID']) {
                                $output[($count - 1)]['source'] = $output[($count - 1)]['source'].'<br/>'.$rowPosts['source'];
                            } else {
                                $output[$_SESSION[$guid]['messageWallCount']]['photo'] = $rowPosts['image_240'];
                                $output[$_SESSION[$guid]['messageWallCount']]['subject'] = $rowPosts['subject'];
                                $output[$_SESSION[$guid]['messageWallCount']]['details'] = $rowPosts['body'];
                                $output[$_SESSION[$guid]['messageWallCount']]['author'] = formatName($rowPosts['title'], $rowPosts['preferredName'], $rowPosts['surname'], $rowPosts['category']);
                                $output[$_SESSION[$guid]['messageWallCount']]['source'] = $rowPosts['source'];
                                $output[$_SESSION[$guid]['messageWallCount']]['gibbonMessengerID'] = $rowPosts['gibbonMessengerID'];

                                ++$_SESSION[$guid]['messageWallCount'];
                                $last = $rowPosts['gibbonMessengerID'];
                                ++$count;
                            }
                        }
                        $_SESSION[$guid]['messageWallOutput'] = $output;
                    }
                }

                $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Messenger/messageWall_view.php';
                if (isset($_SESSION[$guid]['messageWallCount']) == false) {
                    $return .= "<a class='inactive inline-block relative mr-4' title='".__('Message Wall')."' href='$URL'><img class='minorLinkIcon' style='margin-left: 4px; opacity: 0.2; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/messageWall.png'></a>";
                } else {
                    if ($_SESSION[$guid]['messageWallCount'] < 1) {
                        $return .= "<a class='inactive inline-block relative mr-4' title='".__('Message Wall')."' href='$URL'><img class='minorLinkIcon' style='margin-left: 4px; opacity: 0.2; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/messageWall.png'></a>";
                    } else {
                        $return .= "<a class='inline-block relative mr-4' title='".__('Message Wall')."' href='$URL'><span class='badge -mr-2 right-0'>".$_SESSION[$guid]['messageWallCount']."</span><img class='minorLinkIcon' style='margin-left: 4px; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/messageWall.png'></a>";
                        if ($_SESSION[$guid]['pageLoads'] == 0 and ($_SESSION[$guid]['messengerLastBubble'] == null or $_SESSION[$guid]['messengerLastBubble'] < date('Y-m-d'))) {
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
                            $bubbleLeft = 755;
                            if ($messageBubbleWidthType == 'Wide') {
                                $bubbleWidth = 700;
                                $bubbleLeft = 415;
                            }
                            $return .= "<div id='messageBubbleArrow' style=\"left: 1058px; top: 162px; z-index: 9999\" class='arrow top'></div>";
                            $return .= "<div id='messageBubble' style=\"left: ".$bubbleLeft.'px; top: 178px; width: '.$bubbleWidth.'px; min-width: '.$bubbleWidth.'px; max-width: '.$bubbleWidth.'px; min-height: 100px; text-align: center; padding-bottom: 10px" class="ui-tooltip ui-widget ui-corner-all ui-widget-content" role="tooltip">';
                            $return .= '<div class="ui-tooltip-content">';
                            $return .= "<div style='font-weight: bold; font-style: italic; font-size: 120%; margin-top: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dotted rgba(255,255,255,0.5); display: block'>".__('New Messages').'</div>';
                            $test = count($output);
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
                                $return .= '<i>'.$output[$i]['author'].'</i><br/><br/>';
                            }
                            if (count($output) > 3) {
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
                                $data = array('messengerLastBubble' => date('Y-m-d'), 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                $sql = 'UPDATE gibbonPerson SET messengerLastBubble=:messengerLastBubble WHERE gibbonPersonID=:gibbonPersonID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }
                        }
                    }
                }
                $return .= "</div>";
            }

            //GET & SHOW NOTIFICATIONS
            try {
                $dataNotifications = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlNotifications = "(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='New')
                UNION
                (SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='New')
                ORDER BY timestamp DESC, source, text";
                $resultNotifications = $connection2->prepare($sqlNotifications);
                $resultNotifications->execute($dataNotifications);
            } catch (PDOException $e) { }

            //Refresh notifications every 10 seconds for staff, 120 seconds for everyone else
            $interval = 120000;
            if ($_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Staff') {
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
                if (isset($_SESSION[$guid]['gibbonRoleIDCurrentCategory'])) {
                    if ($_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Staff') {
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
                $return .= "<a class='inline-block relative mr-4' title='".__('Notifications')."' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=notifications.php'><span class='badge -mr-2 right-0'>".$resultNotifications->rowCount()."</span><img class='minorLinkIcon' style='margin-left: 2px; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/notifications.png'></a>";
            } else {
                $return .= "<a class='inactive inline-block relative mr-4' title='".__('Notifications')."' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=notifications.php'><img class='minorLinkIcon' style='margin-left: 2px; opacity: 0.2; vertical-align: -75%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/notifications.png'></a>";
            }
            $return .= '</div>';
        }

        $return .= "</div>";
        return $return;
    }

    public function getMinorLinks($cacheLoad)
    {
        
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        
        $return = '';

        // Add a link to go back to the system/personal default language, if we're not using it
        if (isset($_SESSION[$guid]['i18n']['default']['code']) && isset($_SESSION[$guid]['i18n']['code'])) {
            if ($_SESSION[$guid]['i18n']['code'] != $_SESSION[$guid]['i18n']['default']['code']) {
                $systemDefaultShortName = trim(strstr($_SESSION[$guid]['i18n']['default']['name'], '-', true));
                $languageLink = "<a class='link-white' href='".$_SESSION[$guid]['absoluteURL']."?i18n=".$_SESSION[$guid]['i18n']['default']['code']."'>".$systemDefaultShortName.'</a>';
            }
        }

        if (isset($_SESSION[$guid]['username']) == false) {
            $return .= !empty($languageLink) ? $languageLink : '';

            if ($_SESSION[$guid]['webLink'] != '') {
                $return .= !empty($languageLink) ? ' . ' : '';
                $return .= __('Return to')." <a class='link-white' style='margin-right: 12px' target='_blank' href='".$_SESSION[$guid]['webLink']."'>".$_SESSION[$guid]['organisationNameShort'].' '.__('Website').'</a>';
            }
        } else {
            $name = $_SESSION[$guid]['preferredName'].' '.$_SESSION[$guid]['surname'];
            if (isset($_SESSION[$guid]['gibbonRoleIDCurrentCategory'])) {
                if ($_SESSION[$guid]['gibbonRoleIDCurrentCategory'] == 'Student') {
                    $highestAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
                    if ($highestAction == 'View Student Profile_brief') {
                        $name = "<a class='link-white' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']."'>".$name.'</a>';
                    }
                }
            }
            $return .= $name.' . ';
            $return .= "<a class='link-white' href='./logout.php'>".__('Logout')."</a> . <a class='link-white' href='./index.php?q=preferences.php'>".__('Preferences').'</a>';
            if ($_SESSION[$guid]['emailLink'] != '') {
                $return .= " . <a class='link-white' target='_blank' href='".$_SESSION[$guid]['emailLink']."'>".__('Email').'</a>';
            }
            if ($_SESSION[$guid]['webLink'] != '') {
                $return .= " . <a class='link-white' target='_blank' href='".$_SESSION[$guid]['webLink']."'>".$_SESSION[$guid]['organisationNameShort'].' '.__('Website').'</a>';
            }
            if ($_SESSION[$guid]['website'] != '') {
                $return .= " . <a class='link-white' target='_blank' href='".$_SESSION[$guid]['website']."'>".__('My Website').'</a>';
            }

            $return .= !empty($languageLink) ? ' . '.$languageLink : '';

            //Check for house logo (needed to get bubble, below, in right spot)
            if (isset($_SESSION[$guid]['gibbonHouseIDLogo']) and isset($_SESSION[$guid]['gibbonHouseIDName'])) {
                if ($_SESSION[$guid]['gibbonHouseIDLogo'] != '') {
                    $return .= " . <img class='ml-1 h-10 sm:h-12 lg:h-16' title='".$_SESSION[$guid]['gibbonHouseIDName']."' style='vertical-align: -75%;' src='".$_SESSION[$guid]['absoluteURL'].'/'.$_SESSION[$guid]['gibbonHouseIDLogo']."'/>";
                }
            }
        }

        return $return;
    }
}
