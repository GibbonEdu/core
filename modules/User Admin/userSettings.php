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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\UsernameFormatGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('User Settings'));

    $gateway = $container->get(UsernameFormatGateway::class);
    $usernameFormats = $gateway->selectUsernameFormats();

    $table = DataTable::create('usernameFormats');
    
    $table->setTitle(__('Username Formats'));
    
    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/User Admin/userSettings_usernameFormat_add.php')
        ->setIcon('page_new')
        ->displayLabel();

    $table->addColumn('roles', __('Roles'));
    $table->addColumn('format', __('Format'));
    $table->addColumn('isDefault', __('Is Default?'))->format(Format::using('yesNo', 'isDefault'));

    $table->addActionColumn()
        ->addParam('gibbonUsernameFormatID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/User Admin/userSettings_usernameFormat_edit.php');
            if ($row['isDefault'] == 'N') {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/User Admin/userSettings_usernameFormat_delete.php');
            }
        });

    echo $table->render($usernameFormats->toDataSet());

    $form = Form::create('userSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/userSettingsProcess.php');

    $form->setTitle(__('Settings'));
     
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('Field Values', __('Field Values'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('User Admin', 'nationality', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('User Admin', 'ethnicity', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('User Admin', 'religions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('User Admin', 'departureReasons', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('Privacy Options', __('Privacy Options'));

    $setting = $settingGateway->getSettingByScope('User Admin', 'privacy', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('privacy')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('User Admin', 'privacyOptions', true);
    $row = $form->addRow()->addClass('privacy');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('User Admin', 'privacyOptionVisibility', true);
    $row = $form->addRow()->addClass('privacy');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('User Admin', 'privacyBlurb', true);
    $col = $form->addRow()->addClass('privacy')->addColumn();
        $col->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $col->addEditor($setting['name'], $guid)->setRows(6)->showMedia(false)->setValue($setting['value']);

    $row = $form->addRow()->addHeading('User Data Options', __('User Data Options'));

    $setting = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading('User Interface Options', __('User Interface Options'));

    $setting = $settingGateway->getSettingByScope('User Admin', 'personalBackground', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
