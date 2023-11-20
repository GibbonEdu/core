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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/publicRegistrationSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/publicRegistrationSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $partialFail = false;

    $settingsToUpdate = [
        'User Admin' => [
            'enablePublicRegistration' => 'required',
            'publicRegistrationMinimumAge' => '',
            'publicRegistrationDefaultStatus' => 'required',
            'publicRegistrationDefaultRole' => 'required',
            'publicRegistrationAllowedDomains' => '',
            'publicRegistrationIntro' => '',
            'publicRegistrationPrivacyStatement' => '',
            'publicRegistrationAgreement' => '',
            'publicRegistrationPostscript' => '',
            'publicRegistrationAlternateEmail' => 'required',
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

    // Ensure allowed domains each begin with an @ sign
    if (!empty($_POST['publicRegistrationAllowedDomains'])) {
        $_POST['publicRegistrationAllowedDomains'] = implode(',', array_map(function($domain) {
            return '@' . trim($domain, ' @');
        }, explode(',', $_POST['publicRegistrationAllowedDomains'])));
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

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
