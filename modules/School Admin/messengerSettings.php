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
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Messenger Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }


    $form = Form::create('messengerSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/messengerSettingsProcess.php' );

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('SMS Settings'));

    $row = $form->addRow()->addAlert(__('Gibbon can use a number of different gateways to send out SMS messages. These are paid services, not affiliated with Gibbon, and you must create your own account with them before being able to send out SMSs using the Messenger module.').' '.sprintf(__('%1$sClick here%2$s to configure SMS settings.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/thirdPartySettings.php'>", "</a>"));

	$row = $form->addRow()->addHeading(__('Message Wall Settings'));

	$setting = getSettingByScope($connection2, 'Messenger', 'messageBubbleWidthType', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addSelect($setting['name'])->fromString('Regular, Wide')->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'Messenger', 'messageBubbleBGColor', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

	$setting = getSettingByScope($connection2, 'Messenger', 'messageBubbleAutoHide', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->required();

	$setting = getSettingByScope($connection2, 'Messenger', 'enableHomeScreenWidget', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading(__('Miscellaneous'));

	$setting = getSettingByScope($connection2, 'Messenger', 'messageBcc', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(2);

    $setting = getSettingByScope($connection2, 'Messenger', 'pinnedMessagesOnHome', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->required();

	$row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();

}
