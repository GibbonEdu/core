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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/emailSummarySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Email Summary Settings'));

    $form = Form::create('emailSummarySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/emailSummarySettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Weekly Summary', __('Weekly Summary'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('School Admin', 'parentWeeklyEmailSummaryIncludeBehaviour', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('School Admin', 'parentWeeklyEmailSummaryIncludeMarkbook', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->required()->selected($setting['value']);

    $form->addRow()->addHeading('Daily Summary', __('Daily Summary'));

    $setting = $settingGateway->getSettingByScope('School Admin', 'parentDailyEmailSummaryIntroduction', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(10)->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('School Admin', 'parentDailyEmailSummaryPostScript', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(10)->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
