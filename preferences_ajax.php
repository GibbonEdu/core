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
use Gibbon\Domain\User\UserGateway;

include './gibbon.php';

if (!$session->has('gibbonPersonID') || !$session->has('gibbonRoleIDCurrent')) {
    exit;
}

$validator = $container->get(Validator::class);
$userGateway = $container->get(UserGateway::class);

$preferenceScope = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['scope'] ?? '');
$preferenceKey = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['key'] ?? '');
$preferenceValue = $validator->sanitizePlainText($_GET[$preferenceKey] ?? $_GET['default'] ?? '');

if (empty($preferenceScope) || empty($preferenceKey)) {
    return;
}

$userGateway->setUserPreferenceByScope($session->get('gibbonPersonID'), $preferenceScope, $preferenceKey, $preferenceValue);
