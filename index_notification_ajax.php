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

@session_start() ;

//Gibbon system-wide includes
include "./functions.php" ;
include "./config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

$output="" ;

//CHECK FOR SYSTEM ALARM
if (isset($_SESSION[$guid]["gibbonRoleIDCurrentCategory"])) {
	if ($_SESSION[$guid]["gibbonRoleIDCurrentCategory"]=="Staff") {
		$alarm=getSettingByScope($connection2, "System", "alarm") ;
		if ($alarm=="General" OR $alarm=="Lockdown") {
			if ($alarm=="General") {
				$output.="<audio loop autoplay>
					<source src=\"./audio/alarm_general.mp3\" type=\"audio/mpeg\">
				</audio>" ; 
				$output.="<script>alert('" . _('General Alarm!') . "') ;</script>" ;
			}
			else {
				$output.="<audio loop autoplay>
					<source src=\"./audio/alarm_lockdown.mp3\" type=\"audio/mpeg\">
				</audio>" ; 
				$output.="<script>alert('" . _('Lockdown Alarm!') . "') ;</script>" ;
			}
		}
	}
}

//GET & SHOW NOTIFICATIONS
try {
	$dataNotifications=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
	$sqlNotifications="(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID)
	UNION
	(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2)
	ORDER BY timestamp DESC, source, text" ;
	$resultNotifications=$connection2->prepare($sqlNotifications);
	$resultNotifications->execute($dataNotifications); 
}
catch(PDOException $e) { $return.="<div class='error'>" . $e->getMessage() . "</div>" ; }

if ($resultNotifications->rowCount()>0) {
	if (is_file($_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_on.png")) {
		$output.=" . <a title='" . _('Notifications') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=notifications.php'>" . $resultNotifications->rowCount() . " x " . "<img style='margin-left: 2px; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_on.png'></a>" ;
	}
	else {
		$output.=" . <a title='" . _('Notifications') . "' href='./index.php?q=notifications.php'>" . $resultNotifications->rowCount() . " x " . "<img style='margin-left: 2px; vertical-align: -75%' src='./themes/Default/img/notifications_on.png'></a>" ;
	}
}
else {
	if (is_file($_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_off.png")) {
		$output.=" . 0 x " . "<img style='margin-left: 2px; opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_off.png'>" ;
	}
	else {
		$output.=" . 0 x " . "<img style='margin-left: 2px; opacity: 0.8; vertical-align: -75%' src='./themes/Default/img/notifications_off.png'>" ;
	}
}

print $output ;
?>