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
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > </div><div class='trailEnd'>" . _("Notifications") . "</div>" ;
	print "</div>" ;

	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="fail1") {
			$deleteReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($deleteReturn=="fail2") {
			$deleteReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($deleteReturn=="success0") {
			$deleteReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	}

	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	}

	//Get notifications
	try {
		$dataNotifications=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
		$sqlNotifications="(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID)
		UNION
		(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2)
		ORDER BY timestamp DESC, source, text" ;
		$resultNotifications=$connection2->prepare($sqlNotifications);
		$resultNotifications->execute($dataNotifications); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<h2>" ;
		print _("Notifications") . " <span style='font-size: 65%; font-style: italic; font-weight: normal'> x" . $resultNotifications->rowCount() . "</span>" ;
	print "</h2>" ;
	print "<div class='linkTop'>" ; 
		print "<a onclick='return confirm(\"Are you sure you want to delete these records.\")' title='" . _('Delete All Notifications') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsDeleteAllProcess.php'><img style='opacity: 0.8; vertical-align: -75%' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/notifications_on_dark.png'>" ;
	print "</div>" ;
	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th>" ;
				print _("Source") ;
			print "</th>" ;
			print "<th>" ;
				print _("Date") ;
			print "</th>" ;
			print "<th>" ;
				print _("Message") ;
			print "</th>" ;
			print "<th>" ;
				print _("Count") ;
			print "</th>" ;
			print "<th style='width: 100px'>" ;
				print _("Actions") ;
			print "</th>" ;
		print "</tr>" ;
	
		$count=0;
		$rowNum="odd" ;
		if ($resultNotifications->rowCount()<1) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=5>" ;
					print _("There are no records to display.") ;
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
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsActionProcess.php?action=" . urlencode($row["actionLink"]) . "&gibbonNotificationID=" . $row["gibbonNotificationID"] . "'><img title='" . _('Action & Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/notificationsDeleteProcess.php?gibbonNotificationID=" . $row["gibbonNotificationID"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		}
	print "</table>" ;
}
?>