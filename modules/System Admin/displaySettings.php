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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/displaySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Display Settings'));

    $form = Form::create('displaySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/displaySettingsProcess.php');
    
    $form->addHiddenValue('address', $session->get('address'));

    $settingGateway = $container->get(SettingGateway::class);
    $setting = $settingGateway->getSettingByScope('System', 'organisationLogo', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'].'File', __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addFileUpload($setting['name'].'File')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('organisationLogo', $gibbon->session->get('absoluteURL'), $setting['value'])->required();

    $theme = getThemeManifest($gibbon->session->get('gibbonThemeName'), $guid);
    if (!empty($theme['themeColours'])) {
        $setting = $settingGateway->getSettingByScope('System', 'themeColour', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray($theme['themeColours'])->required()->selected($setting['value']);
    }

    $setting = $settingGateway->getSettingByScope('System', 'organisationBackground', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addFileUpload($setting['name'].'File')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('organisationBackground', $gibbon->session->get('absoluteURL'), $setting['value']);

    $setting = $settingGateway->getSettingByScope('System', 'mainMenuCategoryOrder', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
}

