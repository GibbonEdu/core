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

//Gibbon system-wide includes
include './gibbon.php';
$_SESSION[$guid]['sidebarExtra'] = '';

//Check to see if system settings are set from databases
if (empty($_SESSION[$guid]['systemSettingsSet'])) {
    getSystemSettings($guid, $connection2);
}

if (empty($_SESSION[$guid]['systemSettingsSet']) || empty($_SESSION[$guid]['gibbonPersonID'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$_SESSION[$guid]['address'] = isset($_GET['q'])? $_GET['q'] : '';
$_SESSION[$guid]['module'] = getModuleName($_SESSION[$guid]['address']);
$_SESSION[$guid]['action'] = getActionName($_SESSION[$guid]['address']);

if (empty($_SESSION[$guid]['address']) || strstr($_SESSION[$guid]['address'], '..') != false) {
    header("HTTP/1.1 403 Forbidden");
    exit;
} else {
    if (is_file('./'.$_SESSION[$guid]['address'])) {
        include './'.$_SESSION[$guid]['address'];
    } else {
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}
