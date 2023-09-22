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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Timetable Settings'));

    $form = Form::create('ttSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttSettingsProcess.php');

    $settingGateway = $container->get(SettingGateway::class);

    $form = Form::create('ttSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttSettingsProcess.php');
    $form->setTitle(__('Settings'));
    $form->addHiddenValue('address', $session->get('address'));

    $setting = $settingGateway->getSettingByScope('Timetable Admin', 'enrolmentMinDefault', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->onlyInteger(true)->minimum(1)->maximum(9999)->maxLength(4)->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Timetable Admin', 'enrolmentMaxDefault', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->onlyInteger(true)->minimum(1)->maximum(9999)->maxLength(4)->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
