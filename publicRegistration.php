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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;

if (isset($_SESSION[$guid]['username']) == false) {
    $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $proceed = true;
    }
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add($_SESSION[$guid]['organisationNameShort'].' '.__('Public Registration'));

    $publicRegistrationMinimumAge = getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge');

    $returns = array();
    $returns['fail5'] = sprintf(__('Your request failed because you do not meet the minimum age for joining this site (%1$s years of age).'), $publicRegistrationMinimumAge);
    $returns['fail7'] = __('Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['success1'] = __('Your registration was successfully submitted and is now pending approval. Our team will review your registration and be in touch in due course.');
    $returns['success0'] = __('Your registration was successfully submitted, and you may now log into the system using your new username and password.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Get intro
    $intro = getSettingByScope($connection2, 'User Admin', 'publicRegistrationIntro');
    if ($intro != '') {
        echo '<h3>';
        echo __('Introduction');
        echo '</h3>';
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    $form = Form::create('publicRegistration', $_SESSION[$guid]['absoluteURL'].'/publicRegistrationProcess.php');

    $form->setClass('smallIntBorder fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('Account Details'));

    $row = $form->addRow();
        $row->addLabel('surname', __('Surname'));
        $row->addTextField('surname')->required()->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('firstName', __('First Name'));
        $row->addTextField('firstName')->required()->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('email', __('Email'));
        $email = $row->addEmail('email')->required();

    $uniqueEmailAddress = getSettingByScope($connection2, 'User Admin', 'uniqueEmailAddress');
    if ($uniqueEmailAddress == 'Y') {
        $email->uniqueField('./publicRegistrationCheck.php');
    }

    $row = $form->addRow();
        $row->addLabel('gender', __('Gender'));
        $row->addSelectGender('gender')->required();

    $row = $form->addRow();
        $row->addLabel('dob', __('Date of Birth'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dob')->required();

    $row = $form->addRow();
        $row->addLabel('usernameCheck', __('Username'));
        $row->addTextField('usernameCheck')
            ->maxLength(20)
            ->required()
            ->uniqueField('./publicRegistrationCheck.php', array('fieldName' => 'username'));

    $policy = getPasswordPolicy($guid, $connection2);
    if ($policy != false) {
        $form->addRow()->addAlert($policy, 'warning');
    }

    $row = $form->addRow();
        $row->addLabel('passwordNew', __('Password'));
        $row->addPassword('passwordNew')
            ->addPasswordPolicy($pdo)
            ->addGeneratePasswordButton($form)
            ->required()
            ->maxLength(30);
    
    // CUSTOM FIELDS
    $resultFields = getCustomFields($connection2, $guid, null, null, null, null, null, null, true);
    if ($resultFields->rowCount() > 0) {
        $heading = $form->addRow()->addHeading(__('Other Information'));

        while ($rowFields = $resultFields->fetch()) {
            $name = 'custom'.$rowFields['gibbonPersonFieldID'];
            $row = $form->addRow();
                $row->addLabel($name, $rowFields['name'])->description($rowFields['description']);
                $row->addCustomField($name, $rowFields);
        }
    }

    $privacyStatement = getSettingByScope($connection2, 'User Admin', 'publicRegistrationPrivacyStatement');
    if ($privacyStatement != '') {
        $form->addRow()->addHeading(__('Privacy Statement'));
        $form->addRow()->addContent($privacyStatement);
    }

    $agreement = getSettingByScope($connection2, 'User Admin', 'publicRegistrationAgreement');
    if ($agreement != '') {
        $form->addRow()->addHeading(__('Agreement'));
        $form->addRow()->addContent($agreement);

        $row = $form->addRow();
            $row->addLabel('agreement', __('Do you agree to the above?'));
            $row->addCheckbox('agreement')->required()->prepend('Yes');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    //Get postscrript
    $postscript = getSettingByScope($connection2, 'User Admin', 'publicRegistrationPostscript');
    if ($postscript != '') {
        echo '<h2>';
        echo __('Further Information');
        echo '</h2>';
        echo "<p style='padding-bottom: 15px'>";
        echo $postscript;
        echo '</p>';
    }
}
