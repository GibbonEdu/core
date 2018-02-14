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

include 'functions.php';
include 'config.php';

@session_start();

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

$URL = './index.php';
$role = (isset($_GET['gibbonRoleID']))? $_GET['gibbonRoleID'] : '';
$role = str_pad(intval($role), 3, '0', STR_PAD_LEFT);

$_SESSION[$guid]['pageLoads'] = null;

//Check for parameter
if (empty(intval($role))) {
    $URL .= '?return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Check for access to role
    try {
        $data = array('username' => $_SESSION[$guid]['username'], 'gibbonRoleID' => $role);
        $sql = 'SELECT gibbonPerson.gibbonPersonID
                FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE (gibbonPerson.username=:username) AND gibbonRole.gibbonRoleID=:gibbonRoleID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '?return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($result->rowCount() != 1) {
        $URL .= '?return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        //Make the switch
        $gibbon->session->set('gibbonRoleIDCurrent', $role);

        // Reload cached FF actions
        $gibbon->session->cacheFastFinderActions($role);

        // Reload the cached menu
        $mainMenu = new Gibbon\MenuMain($gibbon, $pdo);
        $mainMenu->setMenu();

        $URL .= '?return=success0';
        header("Location: {$URL}");
        exit;
    }
}
