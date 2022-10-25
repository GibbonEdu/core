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
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = Url::fromModuleRoute('Timetable Admin', 'ttSettings');

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttSettings.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {

    $partialFail = false;
    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'Timetable Admin' => [
            'enrolmentMinDefault',
            'enrolmentMaxDefault'
        ],
    ];

    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name) {
            $value = $_POST[$name] ?? '';

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    if ($partialFail) {
        header('Location: ' . $URL->withReturn('error2'));
        exit();
    }

    header('Location: ' . $URL->withReturn('success0'));
}
