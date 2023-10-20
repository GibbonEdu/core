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

use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Messenger\Signature;

if (!$session->exists("username")) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Preferences'));

    $return = $_GET['return'] ?? null;

    //Deal with force reset notification
    $forceReset = $_GET['forceReset'] ?? null;
    if ($forceReset == 'Y' AND $return != 'successa') {
        $page->addMessage(__('Your account has been flagged for a password reset. You cannot continue into the system until you change your password.'));
    }

    $returns = array();
    $returns['errora'] = sprintf(__('Your account status could not be updated, and so you cannot continue to use the system. Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationAdministratorEmail')."'>".$session->get('organisationAdministratorName').'</a>');
    $returns['error4'] = __('Your request failed due to non-matching passwords.');
    $returns['error3'] = __('Your request failed due to incorrect current password.');
    $returns['error6'] = __('Your request failed because your password does not meet the minimum requirements for strength.');
    $returns['error7'] = __('Your request failed because your new password is the same as your current password.');
    $returns['error8'] = __('Your request failed because your MFA code was not valid, this may have occured if your code timed-out before the form was finished processing.');
    $page->return->addReturns($returns);

    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $values = $result->fetch();
    }
    $tfa = new RobThree\Auth\TwoFactorAuth('Gibbon'); //TODO: change the name to be based on the actual value of the school's gibbon name or similar...

    //Check if there is an existing MFA Secret, so that we don't create a new one accidentally, and to have the correct values load below...
    if (!empty($values['mfaSecret'])) {
        $secret = $values['mfaSecret'];
        $secretcheck = !empty($secret) ? 'Y' : 'N';
    } else {
        $secret = $tfa->createSecret();
        $secretcheck = 'N';
    }

    $form = Form::create('resetPassword', $session->get('absoluteURL').'/preferencesPasswordProcess.php');

    $form->addRow()->addHeading('Reset Password', __('Reset Password'));

    /** @var PasswordPolicy */
    $policies = $container->get(PasswordPolicy::class);
    if (($policiesHTML = $policies->describeHTML()) !== '') {
        $form->addRow()->addAlert($policiesHTML, 'warning');
    }

    $row = $form->addRow();
        $row->addLabel('password', __('Current Password'));
        $row->addPassword('password')
            ->required()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordNew', __('New Password'));
        $row->addPassword('passwordNew')
            ->addPasswordPolicy($pdo)
            ->addGeneratePasswordButton($form)
            ->required()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordConfirm', __('Confirm New Password'));
        $row->addPassword('passwordConfirm')
            ->addConfirmation('passwordNew')
            ->required()
            ->maxLength(30);

    if ($secretcheck == 'Y') {
        $row = $form->addRow();
            $row->addLabel('mfaCode', __('Multi Factor Authentication Code'))->description(__('In order to change your password, please input the current 6 digit token'));
            $row->addNumber('mfaCode')->isRequired(); //TODO: Add visual validation that it's a 6 digit number, bit finnicky because there's the possibility of leading 0s this can't be done with max/min values... also not required for it to work.
    }

    $form->addHiddenValue('mfaSecret', $secret);
    $form->addHiddenValue('mfaEnable', $secretcheck);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($forceReset != 'Y') {
        $staff = false;

        /** @var RoleGateway */
        $roleGateway = $container->get(RoleGateway::class);

        foreach ($session->get('gibbonRoleIDAll') as $role) {
            $roleCategory = $roleGateway->getRoleCategory($role[0]);
            $staff = $staff || ($roleCategory == 'Staff');
        }

        $form = Form::create('preferences', $session->get('absoluteURL').'/preferencesProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addRow()->addHeading('Settings', __('Settings'));

        $row = $form->addRow();
            $row->addLabel('calendarFeedPersonal', __('Personal Google Calendar ID'))->description(__('Google Calendar ID for your personal calendar.').'<br/>'.__('Only enables timetable integration when logging in via Google.'));
            $password = $row->addTextField('calendarFeedPersonal');

        $personalBackground = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'personalBackground');
        if ($personalBackground == 'Y') {
            $row = $form->addRow();
                $row->addLabel('personalBackground', __('Personal Background'))->description(__('Set your own custom background image.').'<br/>'.__('Please provide URL to image.'));
                $password = $row->addURL('personalBackground');
        }

        $row = $form->addRow();
            $row->addLabel('gibbonThemeIDPersonal', __('Personal Theme'))->description(__('Override the system theme.'));
            $row->addSelectTheme('gibbonThemeIDPersonal');


        $row = $form->addRow();
            $row->addLabel('gibboni18nIDPersonal', __('Personal Language'))->description(__('Override the system default language.'));
            $row->addSelectI18n('gibboni18nIDPersonal');

        $row = $form->addRow();
            $row->addLabel('receiveNotificationEmails', __('Receive Email Notifications?'))->description(__('Notifications can always be viewed on screen.'));
            $row->addYesNo('receiveNotificationEmails');


        $form->addHiddenValue('mfaSecret', $secret);


        $row = $form->addRow();
            $row->addLabel('mfaEnable', __('Enable Multi Factor Authentication?'))->description(__('Enhance the security of your account login.'));
            $row->addYesNo('mfaEnable')->selected($secretcheck);


       //If MFA wasn't previously set, show the MFA QR code.
        if ($secretcheck == 'N') {
            $form->toggleVisibilityByClass('toggle')->onSelect('mfaEnable')->when('Y');
            $row = $form->addRow()->addClass('toggle');
                $row->addLabel('mfaCode', __('Multi Factor Authentication Code'))->description(__('Scan the below QR code in your relevant authenticator app and input the code it provides, ensuring it doesn\'t expire before you submit the form.').'<br><img src='. $tfa->getQRCodeImageAsDataUri('Login', $secret) .'>');
                $row->addNumber('mfaCode'); //TODO: Add visual validation that it's a 6 digit number, bit finnicky because there's the possibility of leading 0s this can't be done with max/min values... also not required for it to work.
        }
        //If MFA was previously set, and is being disabled
        if ($secretcheck == 'Y' && !empty($values['mfaSecret'])) {
            $form->toggleVisibilityByClass('toggle')->onSelect('mfaEnable')->when('N');
            $row = $form->addRow()->addClass('toggle');
                $row->addLabel('mfaCode', __('Multi Factor Authentication Code'))->description(__('In order to disable your Multi Factor Authentication, please input the current 6 digit token'));
                $row->addNumber('mfaCode'); //TODO: Add visual validation that it's a 6 digit number, bit finnicky because there's the possibility of leading 0s this can't be done with max/min values... also not required for it to work.
        }

        //TODO: Allow for easy reset of MFA secret, currently would need to disable and then re-enable MFA to do so

        if ($session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
            $row = $form->addRow()->addHeading('Signature', __('Signature'))->append(__('Your messenger signature can be found below. You can use it as a regular email signature by selecting and copying the following contents into your email settings.'));

            include_once($session->get('absolutePath').'/modules/Messenger/src/Signature.php');
            $signature = $container->get(Signature::class)->getSignature($session->get('gibbonPersonID'));

            $row = $form->addRow();
                $row->addContent($signature.'<br/>');
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();
    }
}
?>
