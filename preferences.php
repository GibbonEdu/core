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

if (!isset($_SESSION[$guid]["username"])) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>Preferences</div>";
    echo '</div>';

    $return = null;
    if (isset($_GET['return'])) {
        $return = $_GET['return'];
    }

    //Deal with force reset notification
    if (isset($_GET['forceReset'])) {
        $forceReset = $_GET['forceReset'];
    } else {
        $forceReset = null;
    }
    if ($forceReset == 'Y' AND $return != 'successa') {
        $forceResetReturnMessage = '<b><u>'.__($guid, 'Your account has been flagged for a password reset. You cannot continue into the system until you change your password.').'</b></u>';
        echo "<div class='error'>";
        echo $forceResetReturnMessage;
        echo '</div>';
    }

    $returns = array();
    $returns['errora'] = sprintf(__($guid, 'Your account status could not be updated, and so you cannot continue to use the system. Please contact %1$s if you have any questions.'), "<a href='mailto:".$_SESSION[$guid]['organisationAdministratorEmail']."'>".$_SESSION[$guid]['organisationAdministratorName'].'</a>');
    $returns['successa'] = __($guid, 'Your account has been successfully updated. You can now continue to use the system as per normal.');
    $returns['error4'] = __($guid, 'Your request failed due to non-matching passwords.');
    $returns['error3'] = __($guid, 'Your request failed due to incorrect current password.');
    $returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['error7'] = __($guid, 'Your request failed because your new password is the same as your current password.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    try {
        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }
    if ($result->rowCount() == 1) {
        $values = $result->fetch();
    }

    $form = Form::create('resetPassword', $_SESSION[$guid]['absoluteURL'].'/preferencesPasswordProcess.php');

    $form->addRow()->addHeading(__('Reset Password'));

    $policy = getPasswordPolicy($guid, $connection2);
    if ($policy != false) {
        $form->addRow()->addAlert($policy, 'warning');
    }

    $row = $form->addRow();
        $row->addLabel('password', __('Current Password'));
        $row->addPassword('password')
            ->isRequired()
            ->maxLength(30);

    $row = $form->addRow();

        $row->addLabel('passwordNew', __('New Password'));
        $row->addPassword('passwordNew')
            ->addPasswordPolicy($pdo)
            ->addGeneratePasswordButton($form)
            ->isRequired()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordConfirm', __('Confirm New Password'));
        $row->addPassword('passwordConfirm')
            ->addConfirmation('passwordNew')
            ->isRequired()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($forceReset != 'Y') {
        $staff = false;
        foreach ($_SESSION[$guid]['gibbonRoleIDAll'] as $role) {
            $roleCategory = getRoleCategory($role[0], $connection2);
            $staff = $staff || ($roleCategory == 'Staff');
        }

        $form = Form::create('preferences', $_SESSION[$guid]['absoluteURL'].'/preferencesProcess.php');

        $form->addRow()->addHeading(__('Settings'));

        $row = $form->addRow();
            $row->addLabel('calendarFeedPersonal', __('Personal Google Calendar ID'))->description(__('Google Calendar ID for your personal calendar.').'<br/>'.__($guid, 'Only enables timetable integration when logging in via Google.'));
            $password = $row->addTextField('calendarFeedPersonal');

        $personalBackground = getSettingByScope($connection2, 'User Admin', 'personalBackground');
        if ($personalBackground == 'Y') {
            $row = $form->addRow();
                $row->addLabel('personalBackground', __('Personal Background'))->description(__('Set your own custom background image.').'<br/>'.__($guid, 'Please provide URL to image.'));
                $password = $row->addURL('personalBackground');
        }

        $data = array();
        $sql = "SELECT gibbonThemeID as value, (CASE WHEN active='Y' THEN CONCAT(name, ' (', '".__('System Default')."', ')') ELSE name END) AS name FROM gibbonTheme ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('gibbonThemeIDPersonal', __('Personal Theme'))->description(__('Override the system theme.'));
            $row->addSelect('gibbonThemeIDPersonal')->fromQuery($pdo, $sql, $data)->placeholder();


        $data = array();
        $sql = "SELECT gibboni18nID as value, (CASE WHEN systemDefault='Y' THEN CONCAT(name, ' (', '".__('System Default')."', ')') ELSE name END) AS name FROM gibboni18n WHERE active='Y' ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('gibboni18nIDPersonal', __('Personal Language'))->description(__('Override the system default language.'));
            $row->addSelect('gibboni18nIDPersonal')->fromQuery($pdo, $sql, $data)->placeholder();

        $row = $form->addRow();
            $row->addLabel('receiveNotificationEmails', __('Receive Email Notifications?'))->description(__('Notifications can always be viewed on screen.'));
            $row->addYesNo('receiveNotificationEmails');

        if ($staff) {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT smartWorkflowHelp FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID";
            $result = $pdo->executeQuery($data, $sql);

            if ($result && $result->rowCount() > 0) {
                $smartWorkflowHelp = $result->fetchColumn(0);

                $row = $form->addRow();
                    $row->addLabel('smartWorkflowHelp', __('Enable Smart Workflow Help?'));
                    $row->addYesNo('smartWorkflowHelp')->selected($smartWorkflowHelp);
            }
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();
    }
}
?>
