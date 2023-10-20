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

//Gibbon system-wide include
include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_edit.php') == false) {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $gibbonAdmissionsApplicationID = $_POST['gibbonAdmissionsApplicationID'] ?? '';
    $username = $_POST['username'] ?? '';
    if (empty($gibbonAdmissionsApplicationID) || empty($username)) {
        die(0);
    }

    $count = 0;

    $data = ['gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'username' => $username];
    $sql = "SELECT COUNT(*) FROM gibbonAdmissionsApplication WHERE ( JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.data, '$.username'))=:username OR JSON_UNQUOTE(JSON_EXTRACT(gibbonAdmissionsApplication.result, '$.username'))=:username) AND gibbonAdmissionsApplicationID<>:gibbonAdmissionsApplicationID
    AND gibbonAdmissionsApplication.status <> 'Accepted'";
    $count += $pdo->selectOne($sql, $data);


    $data = ['username' => $username];
    $sql = "SELECT COUNT(*) FROM gibbonPerson WHERE username=:username";
    $count += $pdo->selectOne($sql, $data);

    echo $count;
}
