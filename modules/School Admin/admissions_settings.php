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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/admissions_settings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Admissions Settings'));

    $form = Form::create('settings', $session->get('absoluteURL').'/modules/School Admin/admissions_settingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('General Options', __('General Options'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('Application Form', 'publicApplications', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();
        
    $setting = $settingGateway->getSettingByScope('Admissions', 'admissionsEnabled', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'], $guid)->selected($setting['value']);

    $form->toggleVisibilityByClass('admissions')->onSelect('admissionsEnabled')->when('Y');

    $setting = $settingGateway->getSettingByScope('Admissions', 'admissionsLinkName', true);
    $row = $form->addRow()->addClass('admissions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'], $guid)->setValue($setting['value'])->maxLength(90);

    $setting = $settingGateway->getSettingByScope('Admissions', 'admissionsLinkText', true);
    $row = $form->addRow()->addClass('admissions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'], $guid)->setValue($setting['value'])->setRows(3)->maxLength(255);

    $setting = $settingGateway->getSettingByScope('Admissions', 'welcomeHeading', true);
    $row = $form->addRow()->addClass('admissions');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'], $guid)->setValue($setting['value'])->maxLength(90);

    $setting = $settingGateway->getSettingByScope('Admissions', 'welcomeText', true);
    $col = $form->addRow()->addClass('admissions')->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(8);

    $setting = $settingGateway->getSettingByScope('Application Form', 'milestones', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Application Form', 'howDidYouHear', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
