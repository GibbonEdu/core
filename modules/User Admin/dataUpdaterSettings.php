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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/dataUpdaterSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Data Updater Settings'));

    $form = Form::create('dataUpdaterSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/dataUpdaterSettingsProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('Settings', __('Settings'));

    $settingGateway = $container->get(SettingGateway::class);
    $setting = $settingGateway->getSettingByScope('Data Updater', 'requiredUpdates', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addYesNo($setting['name'])->selected($setting['value']);

    $form->toggleVisibilityByClass('requiredUpdates')->onSelect('requiredUpdates')->when('Y');

    $updateTypes = array(
        'Family' => __('Family'),
        'Personal' => __('Personal'),
        'Medical' => __('Medical'),
        'Finance' => __('Finance'),
        'Staff' => __('Staff'),
    );
    $setting = $settingGateway->getSettingByScope('Data Updater', 'requiredUpdatesByType', true);
    $row = $form->addRow()->addClass('requiredUpdates');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromArray($updateTypes)->required()->selectMultiple()->selected(explode(',', $setting['value']));

    $setting = $settingGateway->getSettingByScope('Data Updater', 'cutoffDate', true);
    $row = $form->addRow()->addClass('requiredUpdates');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addDate($setting['name'])->required()->setValue(Format::date($setting['value']));

    $sql = "SELECT DISTINCT category as value, category as name FROM gibbonRole ORDER BY category";
    $setting = $settingGateway->getSettingByScope('Data Updater', 'redirectByRoleCategory', true);
    $row = $form->addRow()->addClass('requiredUpdates');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description($setting['description']);
        $row->addSelect($setting['name'])->fromQuery($pdo, $sql)->selectMultiple()->selected(explode(',', $setting['value']));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
    

    echo '<h2>'.__('Required Fields for Personal Updates').'</h2>';
    echo '<p>'.__('These required field settings apply to all users, except those who hold the ability to submit a data update request for all users in the system (generally just admins).').'</p>';

    $form = Form::createTable('dataUpdaterSettingsFields', $session->get('absoluteURL').'/modules/'.$session->get('module').'/dataUpdaterSettingsFieldsProcess.php');
    
    $form->setClass('fullWidth rowHighlight');
    $form->addHiddenValue('address', $session->get('address'));
    
    // Default settings
    $settingDefaults = [
        'title'                  => ['label' => __('Title'), 'default' => 'required'],
        'surname'                => ['label' => __('Surname'), 'default' => 'required'],
        'firstName'              => ['label' => __('First Name'), 'default' => ''],
        'preferredName'          => ['label' =>  __('Preferred Name'), 'default' => 'required'],
        'officialName'           => ['label' => __('Official Name'), 'default' => 'required'],
        'nameInCharacters'       => ['label' => __('Name In Characters'), 'default' => ''],
        'dob'                    => ['label' => __('Date of Birth'), 'default' => ''],
        'email'                  => ['label' => __('Email'), 'default' => ''],
        'emailAlternate'         => ['label' => __('Alternate Email'), 'default' => ''],
        'address1'               => ['label' => __('Address 1'), 'default' => 'non-required'],
        'address1District'       => ['label' => __('Address 1 District'), 'default' => 'non-required'],
        'address1Country'        => ['label' => __('Address 1 Country'), 'default' => 'non-required'],
        'address2'               => ['label' => __('Address 2'), 'default' => 'non-required'],
        'address2District'       => ['label' => __('Address 2 District'), 'default' => 'non-required'],
        'address2Country'        => ['label' => __('Address 2 Country'), 'default' => 'non-required'],
        'phone1'                 => ['label' => __('Phone 1'), 'default' => ''],
        'phone2'                 => ['label' => __('Phone 2'), 'default' => ''],
        'phone3'                 => ['label' => __('Phone 3'), 'default' => ''],
        'phone4'                 => ['label' => __('Phone 4'), 'default' => ''],
        'languageFirst'          => ['label' => __('First Language'), 'default' => ''],
        'languageSecond'         => ['label' => __('Second Language'), 'default' => ''],
        'languageThird'          => ['label' => __('Third Language'), 'default' => ''],
        'countryOfBirth'         => ['label' => __('Country of Birth'), 'default' => ''],
        'ethnicity'              => ['label' => __('Ethnicity'), 'default' => ''],
        'religion'               => ['label' => __('Religion'), 'default' => ''],
        'profession'             => ['label' => __('Profession'), 'default' => ''],
        'employer'               => ['label' => __('Employer'), 'default' => ''],
        'jobTitle'               => ['label' => __('Job Title'), 'default' => ''],
        'emergency1Name'         => ['label' => __('Emergency 1 Name'), 'default' => ''],
        'emergency1Number1'      => ['label' => __('Emergency 1 Number 1'), 'default' => ''],
        'emergency1Number2'      => ['label' => __('Emergency 1 Number 2'), 'default' => ''],
        'emergency1Relationship' => ['label' => __('Emergency 1 Relationship'), 'default' => ''],
        'emergency2Name'         => ['label' => __('Emergency 2 Name'), 'default' => ''],
        'emergency2Number1'      => ['label' => __('Emergency 2 Number 1'), 'default' => ''],
        'emergency2Number2'      => ['label' => __('Emergency 2 Number 2'), 'default' => ''],
        'emergency2Relationship' => ['label' => __('Emergency 2 Relationship'), 'default' => ''],
        'vehicleRegistration'    => ['label' => __('Vehicle Registration'), 'default' => '']
    ];

    // Get setting and unserialize
    $settings = unserialize($settingGateway->getSettingByScope('User Admin', 'personalDataUpdaterRequiredFields'));

    // Convert original Y/N settings
    if (!isset($settings['Staff'])) {
        foreach ($settingDefaults as $name => $field) {
            $value = (isset($settings[$name]) && $settings[$name]=='Y')? 'required' : $field['default'];
            $settings['Staff'][$name] = $value;
            $settings['Student'][$name] = $value;
        }
    }

    $allOptions = [
        ''         => '',
        'required' => __('Required'),
        'readonly' => __('Read Only'),
        'hidden'   => __('Hidden'),
    ];

    $nonRequiredOptions = $allOptions;
    unset($nonRequiredOptions['required']);

    $row = $form->addRow()->setClass('break heading');
        $row->addContent(__('Field'));
        $row->addContent(__('Staff'));
        $row->addContent(__('Student'));
        $row->addContent(__('Parent'));
        $row->addContent(__('Other'));
    
    foreach ($settingDefaults as $id => $field) {
        $row = $form->addRow();
        $row->addLabel($id, $field['label'])->description($field['default'] == 'non-required' ? __('This field cannot be required') : '');

        $options = $field['default'] == 'non-required'
            ? $nonRequiredOptions
            : $allOptions;

        $row->addSelect("settings[Staff][{$id}]")->fromArray($options)->selected($settings['Staff'][$id] ??  $field['default'])->setClass('w-24 float-none')->setTitle(__('Staff'));
        $row->addSelect("settings[Student][{$id}]")->fromArray($options)->selected($settings['Student'][$id] ??  $field['default'])->setClass('w-24 float-none')->setTitle(__('Student'));
        $row->addSelect("settings[Parent][{$id}]")->fromArray($options)->selected($settings['Parent'][$id] ??  $field['default'])->setClass('w-24 float-none')->setTitle(__('Parent'));
        $row->addSelect("settings[Other][{$id}]")->fromArray($options)->selected($settings['Other'][$id] ??  $field['default'])->setClass('w-24 float-none')->setTitle(__('Other'));
        
    }

    $row = $form->addRow();
        $row->addSubmit();
    
    echo $form->getOutput();

}
