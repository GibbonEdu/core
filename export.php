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
$gibbon->session->set('sidebarExtra', '');

//Check to see if system settings are set from databases
if (empty($gibbon->session->get('systemSettingsSet'))) {
    getSystemSettings($guid, $connection2);
}

if (empty($gibbon->session->get('systemSettingsSet')) || empty($gibbon->session->get('gibbonPersonID'))) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$gibbon->session->set('address', $_GET['q'] ?? '');
$gibbon->session->set('module', getModuleName($gibbon->session->get('address')));
$gibbon->session->set('action', getActionName($gibbon->session->get('address')));

if (empty($gibbon->session->get('address')) || strstr($gibbon->session->get('address'), '..') != false) {
    header("HTTP/1.1 403 Forbidden");
    exit;
} else {
    if (is_file('./'.$gibbon->session->get('address'))) {
        include './'.$gibbon->session->get('address');
    } else {
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}
