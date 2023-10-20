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

if (!$session->has('gibbonPersonID') || !$session->has('gibbonRoleIDPrimary')) {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $gibbonApplicationFormID = $_POST['gibbonApplicationFormID'] ?? '';
    $studentID = $_POST['studentID'] ?? '';
    if (empty($gibbonApplicationFormID) || empty($studentID)) {
        die(0);
    }

    $count = 0;

    $data = ['gibbonApplicationFormID' => $gibbonApplicationFormID, 'studentID' => $studentID];
    $sql = "SELECT COUNT(*) FROM gibbonApplicationForm WHERE studentID=:studentID AND gibbonApplicationFormID<>:gibbonApplicationFormID";
    $count += $pdo->selectOne($sql, $data);

    $data = ['studentID' => $studentID];
    $sql = "SELECT COUNT(*) FROM gibbonPerson WHERE studentID=:studentID";
    $count += $pdo->selectOne($sql, $data);

    echo $count;
}
