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

if (is_null($_SESSION[$guid]["username"])) {
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > </div><div class='trailEnd'>" . __($guid, "Notifications") . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }

	print "<div class='linkTop'>" ; 
		print "<a onclick='return confirm(\"Are you sure you want to delete these records.\")' href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsDeleteAllProcess.php'>" . __($guid, 'Delete All Notifications') . " <img style='vertical-align: -25%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'></a>" ;
	print "</div>" ;
	
	//Get and show newnotifications
	try {
		$dataNotifications=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlNotifications="(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='New')
		UNION
		(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='New')
		ORDER BY timestamp DESC, source, text" ;
		$resultNotifications=$connection2->prepare($sqlNotifications);
		$resultNotifications->execute($dataNotifications); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<h2>" ;
		print __($guid, "New Notifications") . " <span style='font-size: 65%; font-style: italic; font-weight: normal'> x" . $resultNotifications->rowCount() . "</span>" ;
	print "</h2>" ;
	
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 18%'>" ;
				print __($guid, "Source") ;
			print "</th>" ;
			print "<th style='width: 12%'>" ;
				print __($guid, "Date") ;
			print "</th>" ;
			print "<th style='width: 51%'>" ;
				print __($guid, "Message") ;
			print "</th>" ;
			print "<th style='width: 7%'>" ;
				print __($guid, "Count") ;
			print "</th>" ;
			print "<th style='width: 12%'>" ;
				print __($guid, "Actions") ;
			print "</th>" ;
		print "</tr>" ;
	
		$count=0;
		$rowNum="odd" ;
		if ($resultNotifications->rowCount()<1) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=5>" ;
					print __($guid, "There are no records to display.") ;
				print "</td>" ;
			print "</tr>" ;
		}
		else {
			while ($row=$resultNotifications->fetch() AND $count<20) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$count++ ;
		
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["source"] ;
					print "</td>" ;
					print "<td>" ;
						print dateConvertBack($guid, substr($row["timestamp"],0,10)) ;
					print "</td>" ;
					print "<td>" ;
						print $row["text"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["count"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsActionProcess.php?action=" . urlencode($row["actionLink"]) . "&gibbonNotificationID=" . $row["gibbonNotificationID"] . "'><img title='" . __($guid, 'Action & Archive') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsDeleteProcess.php?gibbonNotificationID=" . $row["gibbonNotificationID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		}
	print "</table>" ;
	
	//Get and show newnotifications
	try {
		$dataNotifications=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlNotifications="(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='Archived')
		UNION
		(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='Archived')
		ORDER BY timestamp DESC, source, text LIMIT 0, 50" ;
		$resultNotifications=$connection2->prepare($sqlNotifications);
		$resultNotifications->execute($dataNotifications); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<h2>" ;
		print __($guid, "Archived Notifications") ;
	print "</h2>" ;
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th style='width: 18%'>" ;
				print __($guid, "Source") ;
			print "</th>" ;
			print "<th style='width: 12%'>" ;
				print __($guid, "Date") ;
			print "</th>" ;
			print "<th style='width: 51%'>" ;
				print __($guid, "Message") ;
			print "</th>" ;
			print "<th style='width: 7%'>" ;
				print __($guid, "Count") ;
			print "</th>" ;
			print "<th style='width: 12%'>" ;
				print __($guid, "Actions") ;
			print "</th>" ;
		print "</tr>" ;
	
		$count=0;
		$rowNum="odd" ;
		if ($resultNotifications->rowCount()<1) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=5>" ;
					print __($guid, "There are no records to display.") ;
				print "</td>" ;
			print "</tr>" ;
		}
		else {
			while ($row=$resultNotifications->fetch() AND $count<20) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				$count++ ;
		
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["source"] ;
					print "</td>" ;
					print "<td>" ;
						print dateConvertBack($guid, substr($row["timestamp"],0,10)) ;
					print "</td>" ;
					print "<td>" ;
						print $row["text"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["count"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsActionProcess.php?action=" . urlencode($row["actionLink"]) . "&gibbonNotificationID=" . $row["gibbonNotificationID"] . "'><img title='" . __($guid, 'Action') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsDeleteProcess.php?gibbonNotificationID=" . $row["gibbonNotificationID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		}
	print "</table>" ;
}
?>