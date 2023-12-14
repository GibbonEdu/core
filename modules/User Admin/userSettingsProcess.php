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

use Gibbon\Data\Validator;
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['privacyBlurb' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/userSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/userSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $partialFail = false;
    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'User Admin' => [
            'ethnicity'               => 'comma-separated',
            'religions'               => 'comma-separated',
            'nationality'             => 'comma-separated',
            'departureReasons'        => 'comma-separated',
            'privacy'                 => 'required',
            'privacyBlurb'            => '',
            'privacyOptions'          => 'comma-separated',
            'privacyOptionVisibility' => '',
            'uniqueEmailAddress'      => 'required',
            'personalBackground'      => 'required',
        ],
    ];

    // Update fields
    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name => $property) {
            $value = $_POST[$name] ?? '';

            if ($property == 'required' && empty($value)) {
                $partialFail = true;
                continue;
            }

            if ($property == 'skip-empty' && empty($value)) {
                continue;
            }

            if ($property == 'comma-separated') {
                $value = implode(',', array_map('trim', explode(',', $value)));
            }

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
