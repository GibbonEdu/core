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

namespace Module\User_Admin ;

use Gibbon\core\post ;
use Gibbon\Record\schoolYear ;

if (! $this instanceof post) die();

$syObj = new schoolYear($this);

//Check to see if academic year id variables are set, if not set them
if ($this->session->isEmpty('gibbonAcademicYearID') || $this->session->isEmpty('gibbonSchoolYearName'))
    $syObj->setCurrentSchoolYear();

//Check password address is not blank
$password = $_POST['password'];
$passwordNew = $_POST['passwordNew'];
$passwordConfirm = $_POST['passwordConfirm'];
$forceReset = empty($_POST['forceReset']) ? 'N' : ($_POST['forceReset'] != 'Y' ? 'N' : 'Y');

$URL = array('q'=>'/modules/User Admin/preferences.php', 'forceReset'=>$forceReset);

//Check passwords are not blank
if (empty($password) || empty($passwordNew) || empty($passwordConfirm)) {
    $this->insertMessage('return.error.1');
    $this->redirect($URL);
} else {
    //Check that new password is not same as old password
    if ($password == $passwordNew) {
        $this->insertMessage("Your request failed because your new password is the same as your current password.");
        $this->redirect($URL);
    } else {
        //Check strength of password
        if (! $this->getSecurity()->doesPasswordMatchPolicy($passwordNew)) {
            $this->insertMessage("Your request failed because your password does not meet the minimum requirements for strength.");
            $this->redirect($URL);
        } else {
            //Check new passwords match
            if ($passwordNew != $passwordConfirm) {
                $this->insertMessage("Your request failed due to non-matching passwords.");
                $this->redirect($URL);
            } else {
                //Check current password
				if ( ! $this->getSecurity()->verifyPassword($password, $this->session->get('passwordStrong'), $this->session->get('passwordStrongSalt') ) ) {
                    $this->insertMessage("Your request failed due to incorrect current password.");
                    $this->redirect($URL);
                } else {
                    //If answer insert fails...
                    $salt = $this->getSecurity()->getSalt();
                    $passwordStrong = $this->getSecurity()->getPasswordHash($passwordNew, $salt);
					if (! $this->getSecurity()->updatePassword($passwordStrong, $salt)) {
                        $this->insertMessage('return.error.2');
                        $this->redirect($URL);
                    }

                    //Check for forceReset and take action as the password changed successfully.
                    if ($forceReset == 'Y') {
                        //Update passwordForceReset field
						$pObj = $this->getSecurity()->getPerson($this->session->get("gibbonPersonID"));
						$pObj->setField('passwordForceReset', 'N');
						if (! $pObj->writeRecord(array('passwordForceReset'))) {
                            $this->insertMessage('Your account status could not be updated, and so you cannot continue to use the system. Please contact %1$s if you have any questions.');
                            $this->redirect($URL);
                        }
                        $this->session->set('passwordForceReset', 'N');
                        $this->session->set('passwordStrongSalt', $salt);
                        $this->session->set('passwordStrong', $passwordStrong);
                        $this->session->set('pageLoads', -1);
                        $this->insertMessage("Your account has been successfully updated. You can now continue to use the system as per normal.", 'success');
                        $this->redirect($URL);
                    }

                    $this->session->set('passwordStrongSalt', $salt);
                    $this->session->set('passwordStrong', $passwordStrong);
                    $this->session->set('pageLoads', -1);
                    $this->insertMessage("Your account has been successfully updated. You can now continue to use the system as per normal.", 'success');
                    $this->redirect($URL);
                }
            }
        }
    }
}
