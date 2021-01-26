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
use Gibbon\Forms\DatabaseFormFactory;

if (!$gibbon->session->exists("username")) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Preferences'));

    $return = $_GET['return'] ?? null;

    //Deal with force reset notification
    $forceReset = $_GET['forceReset'] ?? null;
    if ($forceReset == 'Y' AND $return != 'successa') {
        $forceResetReturnMessage = '<b><u>'.__('Your account has been flagged for a password reset. You cannot continue into the system until you change your password.').'</b></u>';
        echo "<div class='error'>";
        echo $forceResetReturnMessage;
        echo '</div>';
    }

    $returns = array();
    $returns['errora'] = sprintf(__('Your account status could not be updated, and so you cannot continue to use the system. Please contact %1$s if you have any questions.'), "<a href='mailto:".$gibbon->session->get('organisationAdministratorEmail')."'>".$gibbon->session->get('organisationAdministratorName').'</a>');
    $returns['error4'] = __('Your request failed due to non-matching passwords.');
    $returns['error3'] = __('Your request failed due to incorrect current password.');
    $returns['error6'] = __('Your request failed because your password does not meet the minimum requirements for strength.');
    $returns['error7'] = __('Your request failed because your new password is the same as your current password.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    
        $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'));
        $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    if ($result->rowCount() == 1) {
        $values = $result->fetch();
    }

    $form = Form::create('resetPassword', $gibbon->session->get('absoluteURL').'/preferencesPasswordProcess.php');

    $form->addRow()->addHeading(__('Reset Password'));

    $policy = getPasswordPolicy($guid, $connection2);
    if ($policy != false) {
        $form->addRow()->addAlert($policy, 'warning');
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

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($forceReset != 'Y') {
        $staff = false;
        foreach ($gibbon->session->get('gibbonRoleIDAll') as $role) {
            $roleCategory = getRoleCategory($role[0], $connection2);
            $staff = $staff || ($roleCategory == 'Staff');
        }

        $form = Form::create('preferences', $gibbon->session->get('absoluteURL').'/preferencesProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addRow()->addHeading(__('Settings'));

        $row = $form->addRow();
            $row->addLabel('calendarFeedPersonal', __('Personal Google Calendar ID'))->description(__('Google Calendar ID for your personal calendar.').'<br/>'.__('Only enables timetable integration when logging in via Google.'));
            $password = $row->addTextField('calendarFeedPersonal');

        $personalBackground = getSettingByScope($connection2, 'User Admin', 'personalBackground');
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

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();
    }
}
?>
