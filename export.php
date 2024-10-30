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

//Gibbon system-wide includes
require_once './gibbon.php';

// Setup the Page and Session objects
$page = $container->get('page');
$session->set('sidebarExtra', '');

//Check to see if system settings are set from databases
if (empty($session->get('systemSettingsSet'))) {
    getSystemSettings($guid, $connection2);
}

if (empty($session->get('systemSettingsSet')) || empty($session->get('gibbonPersonID'))) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$address = $page->getAddress();

if (empty($address) || $page->isAddressValid($address, true) == false || stripos($address, 'modules') === false) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$session->set('address', $address);
$session->set('module', getModuleName($address));
$session->set('action', getActionName($address));

if (is_file('./'.$address)) {
    include './'.$address;
} else {
    header("HTTP/1.1 404 Not Found");
    exit;
}
