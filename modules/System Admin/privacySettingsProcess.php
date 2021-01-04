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

use Gibbon\Domain\System\SettingGateway;

include '../../gibbon.php';
include '../../config.php';

// Module includes
include './moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/privacySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/privacySettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'System' => [
            'passwordPolicyMinLength' => 'required',
            'passwordPolicyAlpha' => 'required',
            'passwordPolicyNumeric' => 'required',
            'passwordPolicyNonAlphaNumeric' => 'required',
            'sessionDuration' => 'required',
        ],
        'System Admin' => [
            'cookieConsentEnabled' => 'required',
            'cookieConsentText' => 'skip-empty',
            'privacyPolicy' => '',
        ],
    ];

    // Validate required fields
    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name => $property) {
            if ($property == 'required' && empty($_POST[$name])) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }
        }
    }

    // Update fields
    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name => $property) {
            $value = $_POST[$name] ?? '';
            if ($property == 'skip-empty' && empty($value)) continue;

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    // Update all the system settings that are stored in the session
    getSystemSettings($guid, $connection2);
    $_SESSION[$guid]['pageLoads'] = null;

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
