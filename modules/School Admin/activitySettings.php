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

    $form = Form::create('activitySettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activitySettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $setting = getSettingByScope($connection2, 'Activities', 'dateType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('Date, Term')->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('perTerm')->onSelect($setting['name'])->when('Term');

    $setting = getSettingByScope($connection2, 'Activities', 'maxPerTerm', true);
    $row = $form->addRow()->addClass('perTerm');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('0,1,2,3,4,5')->selected($setting['value'])->isRequired();


    $setting = getSettingByScope($connection2, 'Activities', 'access', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('None, View, Register')->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Activities', 'payment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('None, Singe, Per Activity, Single + Per Activity')->selected($setting['value'])->isRequired();


    $setting = getSettingByScope($connection2, 'Activities', 'enrolmentType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromString('Competitive, Selection')->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Activities', 'backupChoice', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();


    $setting = getSettingByScope($connection2, 'Activities', 'activityTypes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextarea($setting['name'])->setValue($setting['value']);

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
