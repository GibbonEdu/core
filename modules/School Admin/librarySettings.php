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
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Library Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('librarySettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/librarySettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading('Descriptors');

    $settingByScope = getSettingByScope($connection2, 'Library', 'defaultLoanLength', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])->fromString('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31')->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Library', 'browseBGColor', true);
	$row = $form->addRow();
    	$row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
		$row->addTextField($settingByScope['name'])->setValue($settingByScope['value'])->maxLength(6);

    $settingByScope = getSettingByScope($connection2, 'Library', 'browseBGImage', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextField($settingByScope['name'])->setValue($settingByScope['value'])->maxLength(6);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
