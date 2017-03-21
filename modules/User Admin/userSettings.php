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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage User Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('userSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/userSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('Field Values'));

    $setting = getSettingByScope($connection2, 'User Admin', 'nationality', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'User Admin', 'ethnicity', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'User Admin', 'religions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'User Admin', 'residencyStatus', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'User Admin', 'departureReasons', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('Privacy Options'));

    $setting = getSettingByScope($connection2, 'User Admin', 'privacy', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('privacy')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'User Admin', 'privacyBlurb', true);
    $row = $form->addRow()->addClass('privacy');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'User Admin', 'privacyOptions', true);
    $row = $form->addRow()->addClass('privacy');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('User Interface Options'));

    $setting = getSettingByScope($connection2, 'User Admin', 'personalBackground', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $row = $form->addRow()->addHeading(__('Day-Type Options'));

    $setting = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'User Admin', 'dayTypeText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
