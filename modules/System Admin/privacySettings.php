<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/privacySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Security & Privacy Settings'));

    $form = Form::create('privacySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/privacySettingsProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    // SECURITY SETTINGS
    $form->addRow()->addHeading('Security Settings', __('Security Settings'));
    $form->addRow()->addSubheading(__('Password Policy'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('System', 'passwordPolicyMinLength', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray(range(4, 12))->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'passwordPolicyAlpha', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'passwordPolicyNumeric', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'passwordPolicyNonAlphaNumeric', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->addRow()->addSubheading(__('Miscellaneous'));

    $setting = $settingGateway->getSettingByScope('System', 'sessionDuration', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->setValue($setting['value'])->minimum(1200)->maxLength(50)->required();

    $setting = $settingGateway->getSettingByScope('System', 'allowableIframeSources', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->maxLength(60)->setValue($setting['value']);

    // PRIVACY
    $form->addRow()->addHeading('Privacy Settings', __('Privacy Settings'));

    $setting = $settingGateway->getSettingByScope('System Admin', 'cookieConsentEnabled', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('cookieConsent')->onSelect('cookieConsentEnabled')->when('Y');
    $setting = $settingGateway->getSettingByScope('System Admin', 'cookieConsentText', true);
    $row = $form->addRow()->addClass('cookieConsent');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System Admin', 'privacyPolicy', true);
    $col = $form->addRow()->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setRows(15)->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
