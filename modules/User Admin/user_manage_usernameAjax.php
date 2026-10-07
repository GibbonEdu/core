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

use Gibbon\Data\UsernameGenerator;
use Gibbon\Data\Validator;

//Gibbon system-wide include
require_once __DIR__ . '/../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_add.php') == false) {
    die( __('Your request failed because you do not have access to this action.') );
} else {
    $validator = $container->get(Validator::class);
    $gibbonRoleID = isset($_POST['gibbonRoleID'])? $_POST['gibbonRoleID'] : '003';
    $preferredName = $validator->sanitizeName($_POST['preferredName'] ?? '');
    $firstName = $validator->sanitizeName($_POST['firstName'] ?? '');
    $surname = $validator->sanitizeName($_POST['surname'] ?? '');

    if (empty($gibbonRoleID) || $gibbonRoleID == 'Please select...' || empty($preferredName) || empty($firstName) || empty($surname)) {
        echo '0';
    } else {
        $generator = new UsernameGenerator($pdo);
        $generator->addToken('preferredName', $preferredName);
        $generator->addToken('firstName', $firstName);
        $generator->addToken('surname', $surname);

        echo $generator->generateByRole($gibbonRoleID);
    }
}
