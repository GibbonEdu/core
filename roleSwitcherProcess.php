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

use Gibbon\Url;

// Gibbon system-wide include
require_once './gibbon.php';

$role = $_GET['gibbonRoleID'] ?? '';
$role = str_pad(intval($role), 3, '0', STR_PAD_LEFT);

$gibbon->session->set('pageLoads', null);

//Check for parameter
if (empty(intval($role))) {
    $URL = Url::fromRoute()->withReturn('error0');
    header("Location: {$URL}");
    exit;
} else {
    //Check for access to role
    try {
        $data = array('username' => $gibbon->session->get('username'), 'gibbonRoleID' => $role);
        $sql = 'SELECT gibbonPerson.gibbonPersonID
                FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE (gibbonPerson.username=:username) AND gibbonRole.gibbonRoleID=:gibbonRoleID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL = Url::fromRoute()->withReturn('error2');
        header("Location: {$URL}");
        exit;
    }

    if ($result->rowCount() != 1) {
        $URL = Url::fromRoute()->withReturn('error1');
        header("Location: {$URL}");
        exit;
    } else {
        //Make the switch
        $gibbon->session->set('gibbonRoleIDCurrent', $role);

        // Reload cached FF actions
        $gibbon->session->cacheFastFinderActions($role);

        // Clear the main menu from session cache
        $gibbon->session->forget('menuMainItems');

        $URL = Url::fromRoute()->withReturn('success0');
        header("Location: {$URL}");
        exit;
    }
}
