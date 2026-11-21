<?php

use Gibbon\Domain\System\SettingGateway;
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
use Gibbon\Auth\OIDC\OIDCDiscoveryClient;
use Gibbon\Data\Validator;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['authorizeEndpoint' => 'URL', 'tokenEndpoint' => 'URL', 'userEndpoint' => 'URL', 'issuer' => 'URL', 'jwksUri' => 'URL']);

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
        'enabled'            => $_POST['enabled'] ?? 'N',
        'clientName'         => $_POST['clientName'] ?? '',
        'clientID'           => $_POST['clientID'] ?? '',
        'clientSecret'       => $_POST['clientSecret'] ?? '',
        'developerKey'       => $_POST['developerKey'] ?? '',
        'authorizeEndpoint'  => $_POST['authorizeEndpoint'] ?? '',
        'tokenEndpoint'      => $_POST['tokenEndpoint'] ?? '',
        'userEndpoint'       => $_POST['userEndpoint'] ?? '',
        'scopes'             => $_POST['scopes'] ?? '',
        'usernameAttribute'  => $_POST['usernameAttribute'] ?? '',
    ];

    if ($sso == 'OIDC') {
        $data['issuer'] = is_scalar($_POST['issuer'] ?? null) ? trim((string) $_POST['issuer']) : '';
        $data['subjectAttribute'] = is_scalar($_POST['subjectAttribute'] ?? null) ? trim((string) $_POST['subjectAttribute']) : '';
        if ($data['subjectAttribute'] === '') {
            $data['subjectAttribute'] = 'sub';
        }

        $accountMatching = is_scalar($_POST['accountMatching'] ?? null) ? (string) $_POST['accountMatching'] : 'manual';
        $data['accountMatching'] = in_array($accountMatching, ['manual', 'auto', 'email', 'username'], true) ? $accountMatching : 'manual';
        $data['requireVerifiedEmail'] = ($_POST['requireVerifiedEmail'] ?? 'Y') === 'Y' ? 'Y' : 'N';
        $data['matchAttribute'] = is_scalar($_POST['matchAttribute'] ?? null) ? trim((string) $_POST['matchAttribute']) : '';
        if ($data['matchAttribute'] === '') {
            $data['matchAttribute'] = 'preferred_username';
        }
        $data['fallbackToManual'] = ($_POST['fallbackToManual'] ?? 'N') === 'Y' ? 'Y' : 'N';
        $data['matchClaims'] = is_scalar($_POST['matchClaims'] ?? null) ? trim((string) $_POST['matchClaims']) : '';
        if ($data['matchClaims'] === '') {
            $data['matchClaims'] = $data['accountMatching'] === 'username'
                ? 'username='.$data['matchAttribute']
                : 'email=email';
        }
        $data['jwksUri'] = is_scalar($_POST['jwksUri'] ?? null) ? trim((string) $_POST['jwksUri']) : '';
        $data['autoDiscovery'] = ($_POST['autoDiscovery'] ?? 'Y') === 'Y' ? 'Y' : 'N';
    }

    if ($sso == 'OIDC' && $data['autoDiscovery'] === 'Y' && $data['issuer'] !== '') {
        try {
            $metadata = $container->get(OIDCDiscoveryClient::class)->discover($data['issuer']);
            $data['issuer'] = $metadata['issuer'] ?? $data['issuer'];
            $data['authorizeEndpoint'] = $metadata['authorization_endpoint'];
            $data['tokenEndpoint'] = $metadata['token_endpoint'];
            $data['userEndpoint'] = $metadata['userinfo_endpoint'];
            $data['jwksUri'] = $metadata['jwks_uri'] ?? '';
            if ($data['scopes'] === '') {
                $data['scopes'] = 'openid profile email';
            }
        } catch (\Throwable $e) {
            $URL .= '&return=error12';
            header("Location: {$URL}");
            exit;
        }
    }

    $calendarFeed = $_POST['calendarFeed'] ?? null;

    if ($data['enabled'] == 'Y' && (empty($data['clientID']) || empty($data['clientSecret']))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($sso == 'OIDC' && $data['enabled'] == 'Y' && (empty($data['clientName']) || empty($data['issuer']) || empty($data['authorizeEndpoint']) || empty($data['tokenEndpoint']) || empty($data['userEndpoint']))) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($sso == 'OIDC' && $data['enabled'] == 'Y') {
        foreach (['issuer', 'authorizeEndpoint', 'tokenEndpoint', 'userEndpoint', 'jwksUri'] as $endpoint) {
            if (!empty($data[$endpoint]) && !OIDCDiscoveryClient::isSecureUrl((string) $data[$endpoint])) {
                $URL .= '&return=error13';
                header("Location: {$URL}");
                exit;
            }
        }
    }

    if ($data['enabled'] == 'N') {
        $data = array_filter($data);
    }

    $settingGateway->updateSettingByScope('System Admin', 'sso'.$sso, json_encode(array_merge($values, $data)));
    if ($calendarFeed !== null) {
        $settingGateway->updateSettingByScope('System', 'calendarFeed', $calendarFeed);
    }

    // Update all the system settings that are stored in the session
    getSystemSettings($guid, $connection2);
    $session->set('pageLoads', null);

    $URL .= '&return=success0';
    header("Location: {$URL}");
}
