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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/dataUpdaterSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Data Updater Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>'.__($guid, 'Required Fields for Personal Updates').'</h2>';
	echo '<p>'.__($guid, 'These required field settings apply to all users, except those who hold the ability to submit a data update request for all users in the system (generally just admins).').'</p>';

    $form = Form::create('dataUpdaterSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/dataUpdaterSettingsProcess.php');
    
    $form->setClass('fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    
    // Default settings
    $settingDefaults = array('title' => 'N', 'surname' => 'Y', 'firstName' => 'N', 'preferredName' => 'Y', 'officialName' => 'Y', 'nameInCharacters' => 'N', 'dob' => 'N', 'email' => 'N', 'emailAlternate' => 'N', 'phone1' => 'N', 'phone2' => 'N', 'phone3' => 'N', 'phone4' => 'N', 'languageFirst' => 'N', 'languageSecond' => 'N', 'languageThird' => 'N', 'countryOfBirth' => 'N', 'ethnicity' => 'N', 'citizenship1' => 'N', 'citizenship1Passport' => 'N', 'citizenship2' => 'N', 'citizenship2Passport' => 'N', 'religion' => 'N', 'nationalIDCardNumber' => 'N', 'residencyStatus' => 'N', 'visaExpiryDate' => 'N', 'profession' => 'N', 'employer' => 'N', 'jobTitle' => 'N', 'emergency1Name' => 'N', 'emergency1Number1' => 'N', 'emergency1Number2' => 'N', 'emergency1Relationship' => 'N', 'emergency2Name' => 'N', 'emergency2Number1' => 'N', 'emergency2Number2' => 'N', 'emergency2Relationship' => 'N', 'vehicleRegistration' => 'N');

    //Get setting and unserialize
    $settings = unserialize(getSettingByScope($connection2, 'User Admin', 'personalDataUpdaterRequiredFields'));
    $required = array();

    foreach ($settingDefaults as $name => $defaultValue) {
        $required[$name] = (isset($settings[$name]))? $settings[$name] : $defaultValue;
    }

    $row = $form->addRow()->setClass('break heading');
    	$row->addContent(__('Field')); $row->addContent(__('Required'));
    
    $row = $form->addRow();
    	$row->addLabel('title', __('Title'));
    	$row->addCheckbox('title')->setValue('Y')->checked($required['title'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('surname', __('Surname'));
    	$row->addCheckbox('surname')->setValue('Y')->checked($required['surname'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('firstName', __('First Name'));
    	$row->addCheckbox('firstName')->setValue('Y')->checked($required['firstName'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('preferredName', __('Preferred Names'));
    	$row->addCheckbox('preferredName')->setValue('Y')->checked($required['preferredName'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('officialName', __('Official Name'));
    	$row->addCheckbox('officialName')->setValue('Y')->checked($required['officialName'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('nameInCharacters', __('Name In Characters'));
    	$row->addCheckbox('nameInCharacters')->setValue('Y')->checked($required['nameInCharacters'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('dob', __('Date of Birth'));
    	$row->addCheckbox('dob')->setValue('Y')->checked($required['dob'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('email', __('Email'));
    	$row->addCheckbox('email')->setValue('Y')->checked($required['email'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emailAlternate', __('Alternate Email'));
    	$row->addCheckbox('emailAlternate')->setValue('Y')->checked($required['emailAlternate'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('', __('Address 1'));
    	$row->addContent('<i>'.__('This field cannot be required').'</i>.');

    $row = $form->addRow();
    	$row->addLabel('', __('Address 1 District'));
    	$row->addContent('<i>'.__('This field cannot be required').'</i>.');

    $row = $form->addRow();
    	$row->addLabel('', __('Address 1 Country'));
    	$row->addContent('<i>'.__('This field cannot be required').'</i>.');

    $row = $form->addRow();
    	$row->addLabel('', __('Address 2'));
    	$row->addContent('<i>'.__('This field cannot be required').'</i>.');

    $row = $form->addRow();
    	$row->addLabel('', __('Address 2 District'));
    	$row->addContent('<i>'.__('This field cannot be required').'</i>.');

    $row = $form->addRow();
    	$row->addLabel('', __('Address 2 Country'));
    	$row->addContent('<i>'.__('This field cannot be required').'</i>.');

    for ($i = 1; $i < 5; ++$i) {
    	$row = $form->addRow();
    	$row->addLabel('phone'.$i, sprintf(__('Phone %1$s'), $i));
    	$row->addCheckbox('phone'.$i)->setValue('Y')->checked($required['phone'.$i])->setClass();
    }

    $row = $form->addRow();
    	$row->addLabel('languageFirst', __('First Language'));
    	$row->addCheckbox('languageFirst')->setValue('Y')->checked($required['languageFirst'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('languageSecond', __('Second Language'));
    	$row->addCheckbox('languageSecond')->setValue('Y')->checked($required['languageSecond'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('languageThird', __('Third Language'));
    	$row->addCheckbox('languageThird')->setValue('Y')->checked($required['languageThird'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('countryOfBirth', __('Country of Birth'));
    	$row->addCheckbox('countryOfBirth')->setValue('Y')->checked($required['countryOfBirth'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('ethnicity', __('Ethnicity'));
    	$row->addCheckbox('ethnicity')->setValue('Y')->checked($required['ethnicity'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('citizenship1', __('Citizenship 1'));
    	$row->addCheckbox('citizenship1')->setValue('Y')->checked($required['citizenship1'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('citizenship1Passport', __('Citizenship 1 Passport'));
    	$row->addCheckbox('citizenship1Passport')->setValue('Y')->checked($required['citizenship1Passport'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('citizenship2', __('Citizenship 2'));
    	$row->addCheckbox('citizenship2')->setValue('Y')->checked($required['citizenship2'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('citizenship2Passport', __('Citizenship 2 Passport'));
    	$row->addCheckbox('citizenship2Passport')->setValue('Y')->checked($required['citizenship2Passport'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('religion', __('Religion'));
    	$row->addCheckbox('religion')->setValue('Y')->checked($required['religion'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('nationalIDCardNumber', __('National ID Card Number'));
    	$row->addCheckbox('nationalIDCardNumber')->setValue('Y')->checked($required['nationalIDCardNumber'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('residencyStatus', __('Residency Status'));
    	$row->addCheckbox('residencyStatus')->setValue('Y')->checked($required['residencyStatus'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('visaExpiryDate', __('Visa Expiry Date'));
    	$row->addCheckbox('visaExpiryDate')->setValue('Y')->checked($required['visaExpiryDate'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('profession', __('Profession'));
    	$row->addCheckbox('profession')->setValue('Y')->checked($required['profession'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('employer', __('Employer'));
    	$row->addCheckbox('employer')->setValue('Y')->checked($required['employer'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('jobTitle', __('Job Title'));
    	$row->addCheckbox('jobTitle')->setValue('Y')->checked($required['jobTitle'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency1Name', __('Emergency 1 Name'));
    	$row->addCheckbox('emergency1Name')->setValue('Y')->checked($required['emergency1Name'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency1Number1', __('Emergency 1 Number 1'));
    	$row->addCheckbox('emergency1Number1')->setValue('Y')->checked($required['emergency1Number1'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency1Number2', __('Emergency 1 Number 2'));
    	$row->addCheckbox('emergency1Number2')->setValue('Y')->checked($required['emergency1Number2'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency1Relationship', __('Emergency 1 Relationship'));
    	$row->addCheckbox('emergency1Relationship')->setValue('Y')->checked($required['emergency1Relationship'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency2Name', __('Emergency 2 Name'));
    	$row->addCheckbox('emergency2Name')->setValue('Y')->checked($required['emergency2Name'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency2Number1', __('Emergency 2 Number 1'));
    	$row->addCheckbox('emergency2Number1')->setValue('Y')->checked($required['emergency2Number1'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency2Number2', __('Emergency 2 Number 2'));
    	$row->addCheckbox('emergency2Number2')->setValue('Y')->checked($required['emergency2Number2'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('emergency2Relationship', __('Emergency 2 Relationship'));
    	$row->addCheckbox('emergency2Relationship')->setValue('Y')->checked($required['emergency2Relationship'])->setClass();

    $row = $form->addRow();
    	$row->addLabel('vehicleRegistration', __('Vehicle Registration'));
    	$row->addCheckbox('vehicleRegistration')->setValue('Y')->checked($required['vehicleRegistration'])->setClass();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();

}
