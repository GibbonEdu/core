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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Activity Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __('Activity Types');
    echo '</h3>';

    // Activity Types - CSV to Table Migration
    $activityTypes = getSettingByScope($connection2, 'Activities', 'activityTypes');
    
    if (!empty($activityTypes)) {
        $continue = true;
        $activityTypes = array_map(function($item) { return trim($item); }, explode(',', $activityTypes));
        $access = getSettingByScope($connection2, 'Activities', 'access');
        $enrolmentType = getSettingByScope($connection2, 'Activities', 'enrolmentType');
        $backupChoice = getSettingByScope($connection2, 'Activities', 'backupChoice');

        foreach ($activityTypes as $type) {
            $data = array('name' => $type, 'access' => $access, 'enrolmentType' => $enrolmentType, 'backupChoice' => $backupChoice);
            $sql = "INSERT INTO gibbonActivityType SET name=:name, description='', maxPerStudent=0, access=:access, enrolmentType=:enrolmentType, backupChoice=:backupChoice";
            $pdo->executeQuery($data, $sql);
            $continue = $continue && $pdo->getQuerySuccess();
        }

        if ($continue) {
            $sql = "UPDATE gibbonSetting SET value='' WHERE scope='Activities' AND name='activityTypes'";
            $pdo->executeQuery(array(), $sql);
        }
    }

    $data = array();
    $sql = 'SELECT * FROM gibbonActivityType ORDER BY name';
    $result = $pdo->executeQuery($data, $sql);

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/activitySettings_type_add.php'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Name');
        echo '</th>';
        echo '<th>';
        echo __('Access');
        echo '</th>';
        echo '<th>';
        echo __('Enrolment Type');
        echo '</th>';
        echo '<th style="width:80px;">';
        echo __('Max per Student');
        echo '</th>';
        echo '<th style="width:70px;">';
        echo __('Waiting List');
        echo '</th>';
        echo '<th style="width:70px;">';
        echo __('Backup Choice');
        echo '</th>';
        echo '<th style="width:80px;">';
        echo __('Actions');
        echo '</th>';
        echo '</tr>';

        while ($type = $result->fetch()) {
            echo "<tr>";
            echo '<td>';
            echo $type['name'];
            echo '</td>';
            echo '<td>';
            echo $type['access'];
            echo '</td>';
            echo '<td>';
            echo $type['enrolmentType'];
            echo '</td>';
            echo '<td>';
            echo $type['maxPerStudent'];
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $type['waitingList']);
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $type['backupChoice']);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activitySettings_type_edit.php&gibbonActivityTypeID='.$type['gibbonActivityTypeID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/activitySettings_type_delete.php&gibbonActivityTypeID='.$type['gibbonActivityTypeID']."&width=650&height=155'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    echo '<h3>';
    echo __(__('Settings'));
    echo '</h3>';

    $form = Form::create('activitySettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activitySettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('activityTypes', '');

    $setting = getSettingByScope($connection2, 'Activities', 'dateType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('Date, Term')->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('perTerm')->onSelect($setting['name'])->when('Term');

    $setting = getSettingByScope($connection2, 'Activities', 'maxPerTerm', true);
    $row = $form->addRow()->addClass('perTerm');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('0,1,2,3,4,5')->selected($setting['value'])->isRequired();

    $access = array('None' => __('None'), 'View' => __('View'), 'Register' => __('Register'));
    $setting = getSettingByScope($connection2, 'Activities', 'access', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromArray($access)->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Activities', 'payment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('None, Single, Per Activity, Single + Per Activity')->selected($setting['value'])->isRequired();

    $enrolmentTypes = array('Competitive' => __('Competitive'), 'Selection' => __('Selection'));
    $setting = getSettingByScope($connection2, 'Activities', 'enrolmentType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromArray($enrolmentTypes)->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Activities', 'backupChoice', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Activities', 'disableExternalProviderSignup', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Activities', 'hideExternalProviderCost', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
