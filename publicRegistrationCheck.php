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

include './gibbon.php';

$username = (isset($_POST['username']))? $_POST['username'] : '';
$currentUsername = (isset($_POST['currentUsername']))? $_POST['currentUsername'] : '';

if (!empty($currentUsername) && $currentUsername == $username) {
    echo '0';
} else if (!empty($username)) {
    $generator = new UsernameGenerator($pdo);
    echo $generator->isUsernameUnique($username)? '0' : '1';
}

$email = (isset($_POST['email']))? $_POST['email']: '';

if (!empty($email)) {
    $data = array('email' => $email);
    $sql = "SELECT COUNT(*) FROM gibbonPerson WHERE email=:email";
    $result = $pdo->executeQuery($data, $sql);

    echo ($result && $result->rowCount() == 1)? $result->fetchColumn(0) : -1;
}
