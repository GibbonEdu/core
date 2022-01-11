<?php

use Gibbon\Domain\System\SettingGateway;
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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$sso = $_POST['sso'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/thirdPartySettings_ssoEdit.php&sso=$sso";

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);

    if (empty($sso)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $values = $settingGateway->getSettingByScope('System Admin', 'sso'.$sso);
    $values = json_decode($values, true) ?? [];

    $data = [
        'enabled'           => $_POST['enabled'] ?? 'N',
        'clientName'        => $_POST['clientName'] ?? '',
        'clientID'          => $_POST['clientID'] ?? '',
        'clientSecret'      => $_POST['clientSecret'] ?? '',
        'developerKey'      => $_POST['developerKey'] ?? '',
        'authorizeEndpoint' => $_POST['authorizeEndpoint'] ?? '',
        'tokenEndpoint'     => $_POST['tokenEndpoint'] ?? '',
        'userEndpoint'      => $_POST['userEndpoint'] ?? '',
    ];

    $calendarFeed = $_POST['calendarFeed'] ?? '';

    if ($data['enabled'] == 'Y' && (empty($data['clientID']) || empty($data['clientSecret']))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($data['enabled'] == 'N') {
        $data = array_filter($data);
    }

    $settingGateway->updateSettingByScope('System Admin', 'sso'.$sso, json_encode(array_merge($values, $data)));
    $settingGateway->updateSettingByScope('System', 'calendarFeed', $calendarFeed);

    // Update all the system settings that are stored in the session
    getSystemSettings($guid, $connection2);
    $session->set('pageLoads', null);

    $URL .= '&return=success0';
    header("Location: {$URL}");
}
