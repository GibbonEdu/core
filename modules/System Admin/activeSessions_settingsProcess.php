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

use Gibbon\Domain\System\SessionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/activeSessions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/activeSessions.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $partialFail = false;
    $sessionGateway = $container->get(SessionGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'System Admin' => [
            'maintenanceMode' => 'required',
            'maintenanceModeMessage' => 'skip-hidden',
        ],
    ];

    // Update fields
    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name => $property) {
            $value = $_POST[$name] ?? '';

            if ($property == 'skip-hidden' && !isset($_POST[$name])) continue;

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    // Logout all non-administrator users when maintenance mode is active
    if (!empty($_POST['maintenanceMode']) && $_POST['maintenanceMode'] == 'Y') {
        $sessionGateway->logoutAllNonAdministratorUsers();
    }
   
    $URL .= $partialFail
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
