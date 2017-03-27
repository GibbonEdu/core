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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\NotificationGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/notificationSettings_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Manage Notification Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonNotificationEventID = (isset($_GET['gibbonNotificationEventID']))? $_GET['gibbonNotificationEventID'] : null;

    if (empty($gibbonNotificationEventID)) {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $gateway = new NotificationGateway($pdo);
        $result = $gateway->selectNotificationEventByID($gibbonNotificationEventID);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $event = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/notificationSettings_manage_editProcess.php');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonNotificationEventID', $gibbonNotificationEventID);

            $row = $form->addRow();
                $row->addLabel('event', __('Event'));
                $row->addTextField('event')->setValue($event['moduleName'].': '.$event['event'])->readOnly();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->selected($event['active']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            echo '<h3>';
            echo __('Notification Recipients');
            echo '</h3>';

            if ($event['active'] == 'N') {
                echo "<div class='warning'>";
                echo __('This notification event is not active. The following recipients will not receive any notifications until the event is set to active.');
                echo '</div>';
            }

            $gateway = new NotificationGateway($pdo);
            $result = $gateway->selectAllNotificationListeners($gibbonNotificationEventID);

            if ($result->rowCount() == 0) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                echo '<table class="colorOddEven fullWidth" cellspacing="0">';
                echo '<tr class="head">';
                echo '<th>';
                echo __('Name');
                echo '</th>';
                echo '<th>';
                echo __('Scope');
                echo '</th>';
                echo '<th style="width: 80px;">';
                echo __('Actions');
                echo '</th>';
                echo '</tr>';

                while ($listener = $result->fetch()) {
                    echo '<tr>';
                    echo '<td>';
                    echo formatName($listener['title'], $listener['preferredName'], $listener['surname'], 'Staff', false, true);
                    echo '</td>';
                    echo '<td>';
                    echo $listener['scopeType'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/notificationSettings_manage_listener_deleteProcess.php?gibbonNotificationEventID=".$listener['gibbonNotificationEventID']."&gibbonNotificationListenerID=".$listener['gibbonNotificationListenerID']."&address=".$_SESSION[$guid]['address']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/notificationSettings_manage_listener_addProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonNotificationEventID', $gibbonNotificationEventID);

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Person'));
                $row->addSelectStaff('gibbonPersonID');

            $row = $form->addRow();
                $row->addLabel('scopeType', __('Scope'));
                $row->addSelect('scopeType')->fromArray(array('All' => __('All')));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
