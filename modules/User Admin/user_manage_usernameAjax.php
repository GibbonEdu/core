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

use Gibbon\Data\UsernameGenerator;

//Gibbon system-wide include
include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_add.php') == false) {
    die( __($guid, 'Your request failed because you do not have access to this action.') );
} else {
    $gibbonRoleID = isset($_POST['gibbonRoleID'])? $_POST['gibbonRoleID'] : '';
    $preferredName = isset($_POST['preferredName'])? $_POST['preferredName'] : '';
    $surname = isset($_POST['surname'])? $_POST['surname'] : '';

    if (empty($gibbonRoleID) || $gibbonRoleID == 'Please select...' || empty($preferredName) || empty($surname)) {
        echo '0';
    } else {
        $generator = new UsernameGenerator($pdo);

        $generator->addToken('[preferredNameInitial]', strtolower(substr($preferredName, 0, 1)));
        $generator->addToken('[preferredName]', $preferredName);
        $generator->addToken('[surname]', $surname);

        echo $generator->generateByRole($gibbonRoleID);
    }
}
