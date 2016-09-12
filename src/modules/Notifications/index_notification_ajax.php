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

namespace Module\Notifications ;

use Gibbon\core\view ;
use Gibbon\core\trans;
use Gibbon\Record\notification ;

if (! $this instanceof view) die();

$output = '';

$themeName = $this->session->notEmpty('theme.Name') ? $this->session->get('theme.Name') : 'Default';

if ($this->session->isEmpty('gibbonPersonID')) {
    $output .= ' . 0 x ' . $this->renderReturn('default.minorLinks.notification_off') ;
} else {
    //CHECK FOR SYSTEM ALARM
    if ($this->session->notEmpty('gibbonRoleIDCurrentCategory')) {
        if ($this->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
            $alarm = $this->config->getSettingByScope('System', 'alarm');
            if ($alarm == 'General' || $alarm == 'Lockdown' || $alarm == 'Custom') {
                $el = new \stdClass();
				$el->type = mb_strtolower($alarm);
				$output .= $this->renderReturn('alarm.on', $el);
            } else {
				$output .= $this->renderReturn('alarm.off');
            }
        }
    }

    //GET & SHOW NOTIFICATIONS
	$nObj = new notification($this);
	$data = array('gibbonPersonID' => $this->session->get('gibbonPersonID'), 'gibbonPersonID2' => $this->session->get('gibbonPersonID'));
	$sql = "(SELECT gibbonNotification.*, gibbonModule.name AS source 
		FROM gibbonNotification 
			JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) 
		WHERE gibbonPersonID=:gibbonPersonID AND status='New')
		UNION
			(SELECT gibbonNotification.*, 'System' AS source 
				FROM gibbonNotification 
				WHERE gibbonModuleID IS NULL 
					AND gibbonPersonID=:gibbonPersonID2 
					AND status='New')
		ORDER BY timestamp DESC, source, text";
	$notice = $nObj->findAll($sql, $data, '_');

    if (count($notice) > 0) {
        $output .= " . <a title='".trans::__('Notifications')."' href='./index.php?q=/modules/Notifications/notifications.php'>".count($notice).' x ' . $this->renderReturn('default.minorLinks.notification_on') . '</a>' ;
    } else {
        $output .= ' . 0 x ' . $this->renderReturn('default.minorLinks.notification_off') ;
    }
}

echo $output;
