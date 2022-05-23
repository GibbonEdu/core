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

use Gibbon\Data\Validator;
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['welcomeHeading' => 'HTML', 'welcomeText' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Admissions/settings.php';

if (isActionAccessible($guid, $connection2, '/modules/Admissions/settings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $partialFail = false;

    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'Admissions' => [
            'welcomeHeading',
            'welcomeText',
        ],
        'Application Form' => [
            'milestones',
            'howDidYouHear',
        ],
    ];

    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name) {
            $value = $_POST[$name] ?? '';

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    $availableYearsOfEntry = $_POST['availableYearsOfEntry'] ?? '';
    $availableYearsOfEntry = is_array($availableYearsOfEntry)? implode(',', $availableYearsOfEntry) : $availableYearsOfEntry;

    $updated = $settingGateway->updateSettingByScope('Application Form', 'availableYearsOfEntry', $availableYearsOfEntry);
    $partialFail &= !$updated;

    $URL .= $partialFail
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
