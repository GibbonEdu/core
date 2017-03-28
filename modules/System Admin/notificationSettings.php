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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Domain\System\NotificationGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/notificationSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Notification Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __('Notification Events');
    echo '</h3>';

    echo '<p>';
    echo __('This section allows you to manage system-wide notifications. When a notification event occurs, any users subscribed to that event will receive a notification. Each event below can optionally be turned off to prevent all notifications of that type.');
    echo '</p>';

    $gateway = new NotificationGateway($pdo);
    $result = $gateway->selectAllNotificationEvents();

    if ($result->rowCount() == 0) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo '<table class="colorOddEven fullWidth" cellspacing="0">';
        echo '<tr class="head">';
        echo '<th>';
        echo __('Module');
        echo '</th>';
        echo '<th>';
        echo __('Name');
        echo '</th>';
        echo '<th>';
        echo __('Subscribers');
        echo '</th>';
        echo '<th>';
        echo __('Active');
        echo '</th>';
        echo '<th style="width: 80px;">';
        echo __('Actions');
        echo '</th>';
        echo '</tr>';

        while ($row = $result->fetch()) {
            echo '<tr class="'.(($row['active'] == 'N')? 'error' : '').'">';
            echo '<td>';
            echo $row['moduleName'];
            echo '</td>';
            echo '<td>';
            echo $row['event'];
            if ($row['type'] == 'CLI') {
                echo " <img title='".__('This is a CLI notification event. It will only run if the corresponding CLI script has been setup on the server.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/run.png'/ style='float: right; width:20px; height:20px;margin: -4px 0 -4px 4px;opacity: 0.6;'>";
            }
            echo '</td>';
            echo '<td>';
            echo $row['listenerCount'];
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $row['active']);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/notificationSettings_manage_edit.php&gibbonNotificationEventID='.$row['gibbonNotificationEventID']."'><img title='".__('Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
