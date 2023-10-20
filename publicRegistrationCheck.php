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

include './gibbon.php';

$username = $_POST['username'] ?? '';
$currentUsername = $_POST['currentUsername'] ?? '';

if (!empty($currentUsername) && $currentUsername == $username) {
    echo '0';
} else if (!empty($username)) {
    $generator = new UsernameGenerator($pdo);
    echo $generator->isUsernameUnique($username)? '0' : '1';
}

$email = $_POST['email'] ?? '';

if (!empty($email)) {
    $data = array('email' => $email);
    $sql = "SELECT COUNT(*) FROM gibbonPerson WHERE email=:email";
    $result = $pdo->executeQuery($data, $sql);

    echo ($result && $result->rowCount() == 1)? $result->fetchColumn(0) : -1;
}
