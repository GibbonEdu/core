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

use Gibbon\Auth\OIDC\OIDCDiscoveryClient;

require_once __DIR__ . '/../../gibbon.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$respond = function (array $payload, int $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
};

if (!isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php')) {
    $respond(['ok' => false, 'error' => __('You do not have access to this action.')], 403);
}

$issuer = is_scalar($_GET['issuer'] ?? null) ? trim((string) $_GET['issuer']) : '';
$discoveryUrl = is_scalar($_GET['discoveryUrl'] ?? null) ? trim((string) $_GET['discoveryUrl']) : '';

try {
    $metadata = $container->get(OIDCDiscoveryClient::class)->discover($issuer, $discoveryUrl);
    $respond(['ok' => true, 'data' => $metadata]);
} catch (\Throwable $e) {
    $respond(['ok' => false, 'error' => $e->getMessage()], 422);
}
