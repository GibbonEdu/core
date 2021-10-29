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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Activity Settings'));

    $form = Form::create('activitySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activitySettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $settingGateway = $container->get(SettingGateway::class);

    $dateTypes = array(
        'Date' => __('Date'),
        'Term' =>  __('Term')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'dateType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($dateTypes)->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('perTerm')->onSelect($setting['name'])->when('Term');

    $setting = $settingGateway->getSettingByScope('Activities', 'maxPerTerm', true);
    $row = $form->addRow()->addClass('perTerm');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('0,1,2,3,4,5')->selected($setting['value'])->required();

    $accessTypes = array(
        'None' => __('None'),
        'View' => __('View'),
        'Register' =>  __('Register')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'access', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($accessTypes)->selected($setting['value'])->required();

    $paymentTypes = array(
        'None' => __('None'),
        'Single' => __('Single'),
        'Per Activity' =>  __('Per Activity'),
        'Single + Per Activity' =>  __('Single + Per Activity')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'payment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($paymentTypes)->selected($setting['value'])->required();

    $enrolmentTypes = array(
        'Competitive' => __('Competitive'),
        'Selection' => __('Selection')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'enrolmentType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($enrolmentTypes)->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Activities', 'backupChoice', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Activities', 'activityTypes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextarea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Activities', 'disableExternalProviderSignup', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Activities', 'hideExternalProviderCost', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
