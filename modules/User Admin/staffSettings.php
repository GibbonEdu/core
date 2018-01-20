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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Staff Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('staffSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staffSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('Field Values'));

    $setting = getSettingByScope($connection2, 'Staff', 'salaryScalePositions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Staff', 'responsibilityPosts', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Staff', 'jobOpeningDescriptionTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('Name Formats'))->append(__('How should staff names be formatted?').' '.__('Choose from [title], [preferredName], [surname].').' '.__('Use a colon to limit the number of letters, for example [preferredName:1] will use the first initial.'));

    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
    $sql = "SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
    $result = $pdo->executeQuery($data, $sql);
    if ($result->rowCount() > 0) {
        list($title, $preferredName, $surname) = array_values($result->fetch());
    }

    $setting = getSettingByScope($connection2, 'System', 'nameFormatStaffFormal', true);
    $settingRev = getSettingByScope($connection2, 'System', 'nameFormatStaffFormalReversed', true);

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']))->description(
            __('Example').': '.formatName($title, $preferredName, $surname, 'Staff', false, false).'<br/>'.
            __('Reversed').': '.formatName($title, $preferredName, $surname, 'Staff', true, false));
        $col = $row->addColumn($setting['name'])->addClass('stacked');
        $col->addTextField($setting['name'])->isRequired()->maxLength(60)->setValue($setting['value']);
        $col->addTextField($settingRev['name'])->isRequired()->maxLength(60)->setTitle(__('Reversed'))->setValue($settingRev['value']);

    $setting = getSettingByScope($connection2, 'System', 'nameFormatStaffInformal', true);
    $settingRev = getSettingByScope($connection2, 'System', 'nameFormatStaffInformalReversed', true);

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']))->description(
            __('Example').': '.formatName($title, $preferredName, $surname, 'Staff', false, true).'<br/>'.
            __('Reversed').': '.formatName($title, $preferredName, $surname, 'Staff', true, true));
        $col = $row->addColumn($setting['name'])->addClass('stacked right');
        $col->addTextField($setting['name'])->isRequired()->maxLength(60)->setValue($setting['value']);
        $col->addTextField($settingRev['name'])->isRequired()->maxLength(60)->setTitle(__('Reversed'))->setValue($settingRev['value']);

    $form->loadAllValuesFrom($formats);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
