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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Attendance Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __($guid, 'Attendance Codes');
    echo '</h3>';
    echo '<p>';
    echo __($guid, 'These codes should not be changed during an active school year. Removing an attendace code after attendance has been recorded can result in lost information.');
    echo '</p>';


    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonAttendanceCode ORDER BY sequenceNumber ASC, name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/attendanceSettings_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
        echo "<tr class='head'>";
        echo '<th style="width:30px;">';
        echo __($guid, 'Code');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Direction');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Scope');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Active');
        echo '</th>';
        echo '<th style="width:80px;">';
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;

        while ($row = $result->fetch()) {
            echo "<tr>";
            echo '<td>';
            echo $row['nameShort'];
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo ($row['direction'] == 'In')? __($guid, 'In Class') : __($guid, 'Out of Class');
            echo '</td>';
            echo '<td>';
            echo $row['scope'];
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $row['active']);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_edit.php&gibbonAttendanceCodeID='.$row['gibbonAttendanceCodeID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            if ($row['type'] != 'Core') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/attendanceSettings_manage_delete.php&gibbonAttendanceCodeID='.$row['gibbonAttendanceCodeID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    echo '<h3>';
    echo __($guid, 'Miscellaneous');
    echo '</h3>';

    $form = Form::create('attendanceSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/attendanceSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading('Reasons');

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'attendanceReasons', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value'])->isRequired();

    $row = $form->addRow()->addHeading('Pre-Fills & Pre-Fills')->append('The pre-fill settings below determine which Attendance contexts are preset by data available from other contexts. This allows, for example, for attendance taken in a class to be preset by attendance already taken in a Roll Group. The contexts for attendance include Roll Group, Class, Person, Future and Self Registration.');

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'prefillRollGroup', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'prefillClass', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'prefillPerson', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'defaultRollGroupAttendanceType', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])
            ->fromString('Present,Absent')
            ->selected($settingByScope['value'])
            ->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'defaultClassAttendanceType', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])
            ->fromString('Present,Absent')
            ->selected($settingByScope['value'])
            ->isRequired();


    $row = $form->addRow()->addHeading('Student Self Registration');

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'studentSelfRegistrationIPAddresses', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $inRange = false;
    if ($settingByScope['value'] != '' && $settingByScope['value'] != null) {
        foreach (explode(',', $settingByScope['value']) as $ipAddress) {
            if (trim($ipAddress) == $_SERVER['REMOTE_ADDR']) {
                $inRange = true ;
            }
        }
    }
    if ($inRange) { //Current address is in range
        $form->addRow()->addAlert(sprintf(__($guid, 'Your current IP address (%1$s) is included in the saved list.'), "<b>".$_SERVER['REMOTE_ADDR']."</b>"), 'success')->setClass('standardWidth');
    } else { //Current address is not in range
        $form->addRow()->addAlert(sprintf(__($guid, 'Your current IP address (%1$s) is not included in the saved list.'), "<b>".$_SERVER['REMOTE_ADDR']."</b>"), 'warning')->setClass('standardWidth');
    }

    $row = $form->addRow()->addHeading('Attendance CLI');

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'attendanceCLINotifyByRollGroup', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Attendance', 'attendanceCLINotifyByClass', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();


    $settingByScope = getSettingByScope($connection2, 'Attendance', 'attendanceCLIAdditionalUsers', true);
    $inputs = array();
    try {
        $data=array( 'action1' => '%report_rollGroupsNotRegistered_byDate.php%', 'action2' => '%report_courseClassesNotRegistered_byDate.php%' );
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonRole.name as roleName
                FROM gibbonPerson
                JOIN gibbonPermission ON (gibbonPerson.gibbonRoleIDPrimary=gibbonPermission.gibbonRoleID)
                JOIN gibbonAction ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPermission.gibbonRoleID)
                WHERE status='Full'
                AND (gibbonAction.URLList LIKE :action1 OR gibbonAction.URLList LIKE :action2)
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY gibbonRole.gibbonRoleID, surname, preferredName" ;
        $resultSelect=$connection2->prepare($sql);
        $resultSelect->execute($data);
    } catch (PDOException $e) {
    }

    $users = explode(',', $settingByScope['value']);
    $selected = array();
    while ($rowSelect=$resultSelect->fetch()) {
        if (in_array($rowSelect['gibbonPersonID'], $users) !== false) {
            array_push($selected, $rowSelect['gibbonPersonID']);
        }
        $inputs[$rowSelect["roleName"]][$rowSelect['gibbonPersonID']] = formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Staff", true, true);
    }

    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])
            ->selectMultiple()
            ->fromArray($inputs)
            ->selected($selected);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
