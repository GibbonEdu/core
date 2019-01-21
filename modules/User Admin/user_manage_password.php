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
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_password.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__('Manage Users'), 'user_manage.php')
         ->add(__('Reset User Password'));

    $returns = array();
    $returns['error5'] = __('Your request failed because your passwords did not match.');
    $returns['error6'] = __('Your request failed because your password to not meet the minimum requirements for strength.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $roleGateway = $container->get(RoleGateway::class);
            $role = $roleGateway->getRoleByID($values['gibbonRoleIDPrimary']);
            $userRoles = $roleGateway->selectAllRolesByPerson($_SESSION[$guid]['gibbonPersonID'])->fetchGroupedUnique();

            // Acess denied for users changing a password if they do not have system access to this role
            if ( ($role['restriction'] == 'Admin Only' && !isset($userRoles['001']) ) 
              || ($role['restriction'] == 'Same Role' && !isset($userRoles[$role['gibbonRoleID']]) )) {
                echo "<div class='error'>";
                echo __('You do not have access to this action.');
                echo '</div>';
                return;
            }

            $search = (isset($_GET['search']))? $_GET['search'] : '';
            if (!empty($search)) {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage.php&search='.$search."'>".__('Back to Search Results').'</a>';
                echo '</div>';
            }

            $policy = getPasswordPolicy($guid, $connection2);
            if ($policy != false) {
                echo "<div class='warning'>";
                echo $policy;
                echo '</div>';
            }

            $form = Form::create('resetUserPassword', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/user_manage_passwordProcess.php?gibbonPersonID='.$gibbonPersonID.'&search='.$search);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('username', __('Username'));
                $row->addTextField('username')->isRequired()->readOnly()->setValue($values['username']);

            $row = $form->addRow();
                $row->addLabel('passwordNew', __('Password'));
                $row->addPassword('passwordNew')
                    ->addPasswordPolicy($pdo)
                    ->addGeneratePasswordButton($form)
                    ->isRequired()
                    ->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('passwordConfirm', __('Confirm Password'));
                $row->addPassword('passwordConfirm')
                    ->addConfirmation('passwordNew')
                    ->isRequired()
                    ->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('passwordForceReset', __('Force Reset Password?'))->description(__('User will be prompted on next login.'));
                $row->addYesNo('passwordForceReset')->isRequired()->selected('N');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
