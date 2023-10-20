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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Library Settings'));

    $form = Form::create('librarySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/librarySettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('Descriptors', __('Descriptors'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('Library', 'defaultLoanLength', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31')->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Library', 'browseBGColor', true);
	$row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addColor($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Library', 'browseBGImage', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(255);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
