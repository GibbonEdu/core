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
use Gibbon\Http\Url;
use Gibbon\Session\TokenHandler;

require_once __DIR__ . '/gibbon.php';

$URL = Url::fromRoute('preferences');

if (!$session->has('gibbonPersonID') || !$session->has('gibbonRoleIDCurrent')) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
}

if (!$container->get(TokenHandler::class)->validateCsrfToken()) {
    header("Location: {$URL->withReturn('error1')}");
    exit;
}

$gibbonPersonPasskeyID = (int) ($_POST['gibbonPersonPasskeyID'] ?? 0);
$gibbonPersonID = (int) $session->get('gibbonPersonID');

if ($gibbonPersonPasskeyID <= 0) {
    header("Location: {$URL->withReturn('error1')}");
    exit;
}

$deleted = $container->get(PasskeyService::class)->deletePasskey($gibbonPersonPasskeyID, $gibbonPersonID);

header("Location: {$URL->withReturn($deleted ? 'success0' : 'error2')}");
exit;
