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

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/messengerSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage SMS Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }


    $form = Form::create('messengerSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/messengerSettingsProcess.php' );

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('SMS Settings'));

    $row = $form->addRow()->addAlert( sprintf(__($guid, 'Gibbon is designed to use the %1$sOne Way SMS%2$s gateway to send out SMS messages. This is a paid service, not affiliated with Gibbon, and you must create your own account with them before being able to send out SMSs using the Messenger module. It is possible that completing the fields below with details from other gateways may work.'), "<a href='http://onewaysms.com' target='_blank'>", '</a>') );

    $setting = getSettingByScope($connection2, 'Messenger', 'smsUsername', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

	$setting = getSettingByScope($connection2, 'Messenger', 'smsPassword', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addPassword($setting['name'])->setValue($setting['value']);

	$setting = getSettingByScope($connection2, 'Messenger', 'smsURL', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

	$setting = getSettingByScope($connection2, 'Messenger', 'smsURLCredit', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);


	$row = $form->addRow()->addHeading(__('Message Wall Settings'));

	$setting = getSettingByScope($connection2, 'Messenger', 'messageBubbleWidthType', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addSelect($setting['name'])->fromString('Regular, Wide')->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Messenger', 'messageBubbleBGColor', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

	$setting = getSettingByScope($connection2, 'Messenger', 'messageBubbleAutoHide', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

	$setting = getSettingByScope($connection2, 'Messenger', 'enableHomeScreenWidget', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();


	$row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();

}
?>
