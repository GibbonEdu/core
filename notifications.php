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

if (!isset($_SESSION[$guid]['username'])) {
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Notifications').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo "<div class='linkTop'>";
    echo "<a onclick='return confirm(\"Are you sure you want to delete these records.\")' href='".$_SESSION[$guid]['absoluteURL']."/notificationsDeleteAllProcess.php'>".__($guid, 'Delete All Notifications')." <img style='vertical-align: -25%' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'></a>";
    echo '</div>';

    //Get and show newnotifications
    try {
        $dataNotifications = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlNotifications = "(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='New')
		UNION
		(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='New')
		ORDER BY timestamp DESC, source, text";
        $resultNotifications = $connection2->prepare($sqlNotifications);
        $resultNotifications->execute($dataNotifications);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo '<h2>';
    echo __($guid, 'New Notifications')." <span style='font-size: 65%; font-style: italic; font-weight: normal'> x".$resultNotifications->rowCount().'</span>';
    echo '</h2>';

    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo "<th style='width: 18%'>";
    echo __($guid, 'Source');
    echo '</th>';
    echo "<th style='width: 12%'>";
    echo __($guid, 'Date');
    echo '</th>';
    echo "<th style='width: 51%'>";
    echo __($guid, 'Message');
    echo '</th>';
    echo "<th style='width: 7%'>";
    echo __($guid, 'Count');
    echo '</th>';
    echo "<th style='width: 12%'>";
    echo __($guid, 'Actions');
    echo '</th>';
    echo '</tr>';

    $count = 0;
    $rowNum = 'odd';
    if ($resultNotifications->rowCount() < 1) {
        echo "<tr class=$rowNum>";
        echo '<td colspan=5>';
        echo __($guid, 'There are no records to display.');
        echo '</td>';
        echo '</tr>';
    } else {
        while ($row = $resultNotifications->fetch() and $count < 20) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

                //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['source'];
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, substr($row['timestamp'], 0, 10));
            echo '</td>';
            echo '<td>';
            echo $row['text'];
            echo '</td>';
            echo '<td>';
            echo $row['count'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/notificationsActionProcess.php?action='.urlencode($row['actionLink']).'&gibbonNotificationID='.$row['gibbonNotificationID']."'><img title='".__($guid, 'Action & Archive')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/notificationsDeleteProcess.php?gibbonNotificationID='.$row['gibbonNotificationID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</table>';

    //Get and show newnotifications
    try {
        $dataNotifications = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlNotifications = "(SELECT gibbonNotification.*, gibbonModule.name AS source FROM gibbonNotification JOIN gibbonModule ON (gibbonNotification.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonPersonID=:gibbonPersonID AND status='Archived')
		UNION
		(SELECT gibbonNotification.*, 'System' AS source FROM gibbonNotification WHERE gibbonModuleID IS NULL AND gibbonPersonID=:gibbonPersonID2 AND status='Archived')
		ORDER BY timestamp DESC, source, text LIMIT 0, 50";
        $resultNotifications = $connection2->prepare($sqlNotifications);
        $resultNotifications->execute($dataNotifications);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo '<h2>';
    echo __($guid, 'Archived Notifications');
    echo '</h2>';
    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo "<th style='width: 18%'>";
    echo __($guid, 'Source');
    echo '</th>';
    echo "<th style='width: 12%'>";
    echo __($guid, 'Date');
    echo '</th>';
    echo "<th style='width: 51%'>";
    echo __($guid, 'Message');
    echo '</th>';
    echo "<th style='width: 7%'>";
    echo __($guid, 'Count');
    echo '</th>';
    echo "<th style='width: 12%'>";
    echo __($guid, 'Actions');
    echo '</th>';
    echo '</tr>';

    $count = 0;
    $rowNum = 'odd';
    if ($resultNotifications->rowCount() < 1) {
        echo "<tr class=$rowNum>";
        echo '<td colspan=5>';
        echo __($guid, 'There are no records to display.');
        echo '</td>';
        echo '</tr>';
    } else {
        while ($row = $resultNotifications->fetch() and $count < 20) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

			//COLOR ROW BY STATUS!
			echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['source'];
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, substr($row['timestamp'], 0, 10));
            echo '</td>';
            echo '<td>';
            echo $row['text'];
            echo '</td>';
            echo '<td>';
            echo $row['count'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/notificationsActionProcess.php?action='.urlencode($row['actionLink']).'&gibbonNotificationID='.$row['gibbonNotificationID']."'><img title='".__($guid, 'Action')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/notificationsDeleteProcess.php?gibbonNotificationID='.$row['gibbonNotificationID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</table>';
}
