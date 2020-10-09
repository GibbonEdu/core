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

//Gibbon system-wide includes
include './gibbon.php';

$output = '';

$themeName = $gibbon->session->get('gibbonThemeName') ?? 'Default';

if (!isset($_SESSION[$guid]) or !$gibbon->session->exists('gibbonPersonID')) {
    $output .= "<a class='inactive' title='".__('Notifications')."' href='#'><img class='minorLinkIcon' style='margin-left: 2px; opacity: 0.2; vertical-align: -75%' src='./themes/Default/img/notifications.png'></a>";
} else {
    //CHECK FOR SYSTEM ALARM
    if ($gibbon->session->exists('gibbonRoleIDCurrentCategory')) {
        if ($gibbon->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
            $alarm = getSettingByScope($connection2, 'System', 'alarm');
            if ($alarm == 'General' or $alarm == 'Lockdown' or $alarm == 'Custom') {
                $type = 'general';
                if ($alarm == 'Lockdown') {
                    $type = 'lockdown';
                } elseif ($alarm == 'Custom') {
                    $type = 'custom';
                }
                $output .= "<script>
					if ($('#TB_window').is(':visible')==true && $('#TB_window').hasClass('alarm') == false) {
						$(\"#TB_window\").remove();
                        $(\"body\").append(\"<div id='TB_window'></div>\");
					}
					if ($('#TB_window').is(':visible')==false) {
						var url = '".$gibbon->session->get('absoluteURL').'/index_notification_ajax_alarm.php?type='.$type."&KeepThis=true&TB_iframe=true&width=1000&height=500';
						tb_show('', url);
						$('#TB_window').addClass('alarm') ;
					}
				</script>";
            } else {
                $output .= "<script>
					if ($('#TB_window').is(':visible')==true && $('#TB_window').hasClass('alarm') ) {
						tb_remove();
					}
				</script>";
            }
        }
    }

    //GET & SHOW NOTIFICATIONS
    try {
        $dataNotifications = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'gibbonPersonID2' => $gibbon->session->get('gibbonPersonID'));
        $sqlNotifications = "(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='New')
		UNION
		(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='New')
		ORDER BY timestamp DESC, source, text";
        $resultNotifications = $connection2->prepare($sqlNotifications);
        $resultNotifications->execute($dataNotifications);
    } catch (PDOException $e) {
        $return .= "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($resultNotifications->rowCount() > 0) {
        $output .= "<a class='inline-block relative mr-4' title='".__('Notifications')."' href='./index.php?q=notifications.php'><span class='badge -mr-2 right-0'>".$resultNotifications->rowCount()."</span><img style='margin-left: 2px; vertical-align: -75%' src='./themes/".$themeName."/img/notifications.png'></a>";
    } else {
        $output .= "<a class='inactive inline-block relative mr-4' title='".__('Notifications')."' href='".$gibbon->session->get('absoluteURL')."/index.php?q=notifications.php'><img class='minorLinkIcon' style='margin-left: 2px; opacity: 0.2; vertical-align: -75%' src='".$gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/notifications.png'></a>";
    }
}

echo $output;
