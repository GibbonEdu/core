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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/settings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    // FORM
    $form = Form::create('settings', $gibbon->session->get('absoluteURL').'/modules/Reports/settingsProcess.php');
    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $form->addRow()->addHeading('General Options', __('General Options'));

    $setting = $settingGateway->getSettingByScope('Reports', 'archiveInformation', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $form->addRow()->addHeading('System Settings', __('System Settings'));

    $setting = $settingGateway->getSettingByScope('Reports', 'customAssetPath', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->required()->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Reports', 'debugMode', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
