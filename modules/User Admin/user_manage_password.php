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
use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_password.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__('Manage Users'), 'user_manage.php')
         ->add(__('Reset User Password'));

    $returns = array();
    $returns['error5'] = __('Your request failed because your passwords did not match.');
    $returns['error6'] = __('Your request failed because your password does not meet the minimum requirements for strength.');
    $page->return->addReturns($returns);

    //Check if gibbonPersonID specified
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    if ($gibbonPersonID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $userGateway = $container->get(UserGateway::class);
        $values = $userGateway->getByID($gibbonPersonID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $roleGateway = $container->get(RoleGateway::class);
            $role = $roleGateway->getRoleByID($values['gibbonRoleIDPrimary']);
            $userRoles = $roleGateway->selectAllRolesByPerson($session->get('gibbonPersonID'))->fetchGroupedUnique();

            // Acess denied for users changing a password if they do not have system access to this role
            if ( ($role['restriction'] == 'Admin Only' && !isset($userRoles['001']) )
              || ($role['restriction'] == 'Same Role' && !isset($userRoles[$role['gibbonRoleID']]) && !isset($userRoles['001']) )) {
                $page->addError(__('You do not have access to this action.'));
                return;
            }

            $search = $_GET['search'] ?? '';
            if ($search != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('User Admin', 'user_manage.php')->withQueryParam('search', $search));
            }

            /** @var PasswordPolicy */
            $policies = $container->get(PasswordPolicy::class);
            if (($policiesHTML = $policies->describeHTML()) !== '') {
                echo "<div class='warning'>";
                echo $policiesHTML;
                echo '</div>';
            }

            $form = Form::create('resetUserPassword', $session->get('absoluteURL').'/modules/'.$session->get('module').'/user_manage_passwordProcess.php?gibbonPersonID='.$gibbonPersonID.'&search='.$search);

            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('username', __('Username'));
                $row->addTextField('username')->required()->readOnly()->setValue($values['username']);

            $row = $form->addRow();
                $row->addLabel('passwordNew', __('Password'));
                $row->addPassword('passwordNew')
                    ->addPasswordPolicy($pdo)
                    ->addGeneratePasswordButton($form)
                    ->required()
                    ->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('passwordConfirm', __('Confirm Password'));
                $row->addPassword('passwordConfirm')
                    ->addConfirmation('passwordNew')
                    ->required()
                    ->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('passwordForceReset', __('Force Reset Password?'))->description(__('User will be prompted on next login.'));
                $row->addYesNo('passwordForceReset')->required()->selected('N');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
            echo '<br/>';

            // LOGIN TROUBLESHOOTING
            $trueIcon = "<img title='" . __('Yes'). "' src='".$session->get('absoluteURL')."/themes/".$session->get('gibbonThemeName')."/img/iconTick.png' class='w-5 h-5 mr-4 float-right' />";
            $falseIcon = "<img title='" . __('No'). "' src='".$session->get('absoluteURL')."/themes/".$session->get('gibbonThemeName')."/img/iconCross.png' class='w-5 h-5 mr-4 float-right' />";

            $form = Form::create('loginAccess', "")->setClass('smallIntBorder w-full');
            $form->setTitle(__('Login Troubleshooting'));

            $statusFull = $values['status'] == 'Full';
            $canLoginUser = $values['canLogin'] == 'Y';
            $canLoginRole = $role['canLoginRole'] == 'Y';
            $failedLogins = $values['failCount'] < 3;
            $emailUnique = $userGateway->unique($values, ['email'], $gibbonPersonID);

            $row = $form->addRow();
                $row->addLabel('statusLabel', __('User').': '.__('Status'));
                $row->addTextField('status')->setValue(__($values['status']))->readonly();
                $row->addContent($statusFull? $trueIcon : $falseIcon);

            $row = $form->addRow();
                $row->addLabel('failedLoginsLabel', __('User').': '.__('Failed Logins'));
                $row->addTextField('failedLogins')->setValue($values['failCount'])->readonly();
                $row->addContent($failedLogins? $trueIcon : $falseIcon);

            $row = $form->addRow();
                $row->addLabel('canLoginLabel', __('User').': '.__('Can Login'));
                $row->addTextField('canLogin')->setValue($canLoginUser ? __('Yes') : __('No'))->readonly();
                $row->addContent($canLoginUser? $trueIcon : $falseIcon);

            $row = $form->addRow();
                $row->addLabel('canLoginRoleLabel', __('Role').': '.__('Can Login'));
                $row->addTextField('canLoginRole')->setValue(($canLoginRole ? __('Yes') : __('No')).' - '.__($role['name']))->readonly();
                $row->addContent($canLoginRole? $trueIcon : $falseIcon);

            $row = $form->addRow();
                $row->addLabel('canLoginRoleLabel', __('Email').': '.__('Must be unique'));
                $row->addTextField('canLoginRole')->setValue($values['email'])->setClass('w-64')->readonly();
                $row->addContent($emailUnique? $trueIcon : $falseIcon);

            $row = $form->addRow();
                $row->addLabel('lastTimestampLabel', __('Last Login: Time'));
                $row->addTextField('lastTimestamp')->setValue(Format::dateTimeReadable($values['lastTimestamp']))->setClass('w-64')->readonly();
                $row->addContent(!empty($values['lastTimestamp'])? $trueIcon : $falseIcon);

            $row = $form->addRow();
                $row->addLabel('lastIPAddressLabel', __('Last Login: IP'));
                $row->addTextField('lastIPAddress')->setValue($values['lastIPAddress'])->setClass('w-64')->readonly();
                $row->addContent(!empty($values['lastIPAddress'])? $trueIcon : $falseIcon);

            echo $form->getOutput();
        }
    }
}
