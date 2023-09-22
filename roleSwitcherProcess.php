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

use Gibbon\Http\Url;
use Gibbon\Domain\User\RoleGateway;

// Gibbon system-wide include
require_once './gibbon.php';

$gibbonRoleID = $_GET['gibbonRoleID'] ?? '';
$gibbonRoleID = str_pad(intval($gibbonRoleID), 3, '0', STR_PAD_LEFT);

$session->set('pageLoads', null);

//Check for parameter
if (empty(intval($gibbonRoleID))) {
    $URL = Url::fromRoute()->withReturn('error0');
    header("Location: {$URL}");
    exit;
} else {
    // Check for access to role
    $roleGateway = $container->get(RoleGateway::class);
    $role = $roleGateway->getAvailableUserRoleByID($session->get('gibbonPersonID'), $gibbonRoleID);

    if (empty($role) || empty($role['category'])) {
        $URL = Url::fromRoute()->withReturn('error0');
        header("Location: {$URL}");
        exit;
    }

    //Make the switch
    $session->set('gibbonRoleIDCurrent', $gibbonRoleID);
    $session->set('gibbonRoleIDCurrentCategory', $role['category']);

    // Clear cached FF actions
    $session->forget('fastFinderActions');

    // Clear the main menu from session cache
    $session->forget('menuMainItems');

    $URL = Url::fromRoute()->withReturn('success0');
    header("Location: {$URL}");
    exit;
}
