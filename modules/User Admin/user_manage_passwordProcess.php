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

include '../../gibbon.php';

$gibbonPersonID = $_GET['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/user_manage_password.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search'];

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_password.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if person specified
    if ($gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            $passwordNew = $_POST['passwordNew'];
            $passwordConfirm = $_POST['passwordConfirm'];
            $passwordForceReset = $_POST['passwordForceReset'];

            //Validate Inputs
            if ($passwordNew == '' or $passwordConfirm == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check strength of password
                $passwordMatch = doesPasswordMatchPolicy($connection2, $passwordNew);

                if ($passwordMatch == false) {
                    $URL .= '&return=error6';
                    header("Location: {$URL}");
                } else {
                    //Check new passwords match
                    if ($passwordNew != $passwordConfirm) {
                        $URL .= '&return=error5';
                        header("Location: {$URL}");
                    } else {
                        $salt = getSalt();
                        $passwordStrong = hash('sha256', $salt.$passwordNew);

                        //Write to database
                        try {
                            $data = array('passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'passwordForceReset' => $passwordForceReset, 'gibbonPersonID' => $gibbonPersonID);
                            $sql = "UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, passwordForceReset=:passwordForceReset, failCount=0 WHERE gibbonPersonID=:gibbonPersonID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        $URL .= '&return=success0';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
