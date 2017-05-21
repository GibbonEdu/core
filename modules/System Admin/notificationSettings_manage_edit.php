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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/notificationSettings_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/notificationSettings.php'>".__($guid, 'Notification Settings')."</a> > </div><div class='trailEnd'>".__('Edit Notification Event').'</div>';
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
                $row->addLabel('permission', __('Permission Required'));
                $row->addTextField('permission')->setValue($event['actionName'])->readOnly();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->selected($event['active']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

            echo '<h3>';
            echo __('Edit Subscribers');
            echo '</h3>';

            if ($event['active'] == 'N') {
                echo "<div class='warning'>";
                echo __('This notification event is not active. The following subscribers will not receive any notifications until the event is set to active.');
                echo '</div>';
            }

            if ($event['type'] == 'CLI') {
                echo "<div class='message'>";
                echo __('This is a CLI notification event. It will only run if the corresponding CLI script has been setup on the server.');
                echo '</div>';
            }

            $gateway = new NotificationGateway($pdo);
            $result = $gateway->selectAllNotificationListeners($gibbonNotificationEventID, false);

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
                echo '<th style="width: 120px;" title="'.__('Notifications can always be viewed on screen.').'">';
                echo __('Receive Email Notifications?');
                echo '</th>';
                echo '<th>';
                echo __('Scope');
                echo '</th>';
                echo '<th style="width: 80px;">';
                echo __('Actions');
                echo '</th>';
                echo '</tr>';

                while ($listener = $result->fetch()) {
                    echo '<tr class="'.(($listener['receiveNotificationEmails'] == 'N')? 'warning' : '').'">';
                    echo '<td>';
                    echo formatName($listener['title'], $listener['preferredName'], $listener['surname'], 'Staff', false, true);
                    echo '</td>';
                    echo '<td>';
                    echo ynExpander($guid, $listener['receiveNotificationEmails']);
                    echo '</td>';
                    echo '<td>';

                    if ($listener['scopeType'] == 'All') {
                        echo __('All');
                    } else {
                        switch($listener['scopeType']) {
                            case 'gibbonPersonIDStudent':   $data = array('gibbonPersonID' => $listener['scopeID']);
                                                            $sql = "SELECT 'Student' as scopeTypeName, CONCAT(surname, ' ', preferredName) as scopeIDName FROM gibbonperson WHERE gibbonPersonID=:gibbonPersonID";
                                                            break;

                            case 'gibbonYearGroupID':       $data = array('gibbonYearGroupID' => $listener['scopeID']);
                                                            $sql = "SELECT 'Year Group' as scopeTypeName, name as scopeIDName FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID";
                                                            break;

                            default:                        $data = array();
                                                            $sql = "SELECT 'Scope' as scopeTypeName, 'Unknown' as scopeIDName";
                        }

                        $resultScope = $pdo->executeQuery($data, $sql);
                        if ($resultScope && $resultScope->rowCount() > 0) {
                            $scopeDetails = $resultScope->fetch();
                            echo __($scopeDetails['scopeTypeName']).' - '.$scopeDetails['scopeIDName'];
                        }
                    }

                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/notificationSettings_manage_listener_deleteProcess.php?gibbonNotificationEventID=".$listener['gibbonNotificationEventID']."&gibbonNotificationListenerID=".$listener['gibbonNotificationListenerID']."&address=".$_SESSION[$guid]['address']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }

            // Filter users who can have permissions for the notification event action
            $staffMembers = array();
            $data=array( 'action' => $event['actionName']);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonRole.name as roleName
                    FROM gibbonPerson
                    JOIN gibbonPermission ON (gibbonPerson.gibbonRoleIDPrimary=gibbonPermission.gibbonRoleID OR gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonPermission.gibbonRoleID, '%'))
                    JOIN gibbonAction ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                    JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID)
                    WHERE status='Full'
                    AND (gibbonAction.name=:action)
                    GROUP BY gibbonPerson.gibbonPersonID
                    ORDER BY gibbonRole.gibbonRoleID, surname, preferredName" ;
            $resultSelect=$pdo->executeQuery($data, $sql);

            if ($resultSelect && $resultSelect->rowCount() > 0) {
                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/notificationSettings_manage_listener_addProcess.php');
                $form->setFactory(DatabaseFormFactory::create($pdo));

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonNotificationEventID', $gibbonNotificationEventID);

                while ($rowSelect = $resultSelect->fetch()) {
                    $staffMembers[$rowSelect['roleName']][$rowSelect['gibbonPersonID']] = formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Staff", true, true);
                }

                $row = $form->addRow();
                    $row->addLabel('gibbonPersonID', __('Person'))->description(__('Available only to users with the required permission.'));
                    $row->addSelect('gibbonPersonID')->fromArray($staffMembers)->placeholder(__('Please select...'))->isRequired();

                if ($event['scopes'] == 'All') {
                    $form->addHiddenValue('scopeType', 'All');
                } else {
                    $allScopes = array(
                        'All'                   => __('All'),
                        'gibbonPersonIDStudent' => __('Student'),
                        'gibbonPersonIDStaff'   => __('Staff'),
                        'gibbonYearGroupID'     => __('Year Group'),
                    );

                    $eventScopes = array_combine(explode(',', $event['scopes']), explode(',', trim($event['scopes'])));
                    $availableScopes = array_intersect_key($allScopes, $eventScopes);

                    $row = $form->addRow();
                        $row->addLabel('scopeType', __('Scope'))->description(__('Apply an optional filter to notifications received.'));
                        $row->addSelect('scopeType')->fromArray($availableScopes);

                    $form->toggleVisibilityByClass('scopeTypeStudent')->onSelect('scopeType')->when('gibbonPersonIDStudent');
                    $row = $form->addRow()->addClass('scopeTypeStudent');
                        $row->addLabel('gibbonPersonIDStudent', __('Student'));
                        $row->addSelectStudent('gibbonPersonIDStudent')->isRequired()->placeholder();

                    $form->toggleVisibilityByClass('scopeTypeStaff')->onSelect('scopeType')->when('gibbonPersonIDStaff');
                    $row = $form->addRow()->addClass('scopeTypeStaff');
                        $row->addLabel('gibbonPersonIDStaff', __('Student'));
                        $row->addSelectStaff('gibbonPersonIDStaff')->isRequired()->placeholder();

                    $form->toggleVisibilityByClass('scopeTypeYearGroup')->onSelect('scopeType')->when('gibbonYearGroupID');
                    $row = $form->addRow()->addClass('scopeTypeYearGroup');
                        $row->addLabel('gibbonYearGroupID', __('Year Group'));
                        $row->addSelectYearGroup('gibbonYearGroupID')->isRequired()->placeholder();
                }

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit('Add');

                echo $form->getOutput();
            }
        }
    }
}
