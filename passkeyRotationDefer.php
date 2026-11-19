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

use Gibbon\Auth\Passkey\PasskeyService;

require_once __DIR__ . '/gibbon.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!$session->has('gibbonPersonID') || !$session->has('gibbonRoleIDCurrent')) {
    http_response_code(401);
    echo json_encode(['error' => __('You must be logged in.')]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$csrfToken = is_array($payload) ? (string) ($payload['csrftoken'] ?? '') : '';

if (!hash_equals((string) $session->get('csrftoken'), $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => __('Invalid request token.')]);
    exit;
}

$container->get(PasskeyService::class)->deferRotation((int) $session->get('gibbonPersonID'));

echo json_encode(['success' => true]);
